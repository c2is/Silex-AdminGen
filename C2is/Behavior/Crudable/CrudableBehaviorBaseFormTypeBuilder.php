<?php

require_once dirname(__FILE__) . '/CrudableBehaviorUtils.php';

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints as Assert;

class CrudableBehaviorBaseFormTypeBuilder extends OMBuilder
{
    public $overwrite = true;

    public function getBehavior()
    {
        return $this->getTable()->getBehavior('crudable');
    }

    public function getPackage()
    {
        return sprintf("%s.Base", str_replace('Model', 'Form.Type', parent::getPackage()));
    }

    public function getUnprefixedClassname()
    {
        return sprintf("Base%sType", $this->getObjectClassname());
    }

    public function getNamespace()
    {
        return str_replace("Model", "Form\\Type\\Base", parent::getNamespace());
    }

    protected function getTypeFileColumns()
    {
        return $this->getBehavior()->getTypeFileColumns();
    }

    protected function getTypeTextrichColumns()
    {
        return $this->getBehavior()->getTypeTextrichColumns();
    }

    protected function addClassOpen(&$script)
    {
        $classname = $this->getClassname();
        $name      = preg_replace('/^Base(.*)Type$/', '$1', $classname);

        $script .= "use Symfony\\Component\\Form\\AbstractType;
use Symfony\\Component\\Form\\FormBuilderInterface;
use Symfony\\Component\\OptionsResolver\\Options;
use Symfony\\Component\\OptionsResolver\\OptionsResolverInterface;
use Symfony\\Component\\Validator\\Constraints as Assert;

/**
 * {$name} form base class.
 *
 * @authors Morgan Brunot <brunot.morgan@gmail.com>
 *          Denis Roussel <denis.roussel@gmail.com>
 *
 * @package c2is.behavior.crudable
 */
class {$classname} extends AbstractType
{";
    }

    protected function addClassClose(&$script)
    {
        $classname = $this->getClassname();

        $script .= "
} // {$classname}
";
    }

    protected function addClassBody(&$script)
    {
        $this->fileFields     = $this->getTypeFileColumns();
        $this->richtextFields = $this->getTypeTextrichColumns();

        $this->addBuildForm($script);
        $this->addSetDefaultOptions($script);
        $this->addGetName($script);
    }

    private function getFieldType($column)
    {
        if ($column->getName() == 'id') {
            return 'hidden';
        }

        if (in_array($column->getName(), $this->fileFields)) {
            return 'file';
        }

        switch ($column->getType()) {
            case \PropelTypes::VARCHAR:
            case \PropelTypes::FLOAT:
                return 'text';
            case \PropelTypes::LONGVARCHAR:
                return 'textarea';
            case \PropelTypes::INTEGER:
                return 'integer';
            case \PropelTypes::BOOLEAN:
                return 'checkbox';
            case \PropelTypes::ENUM:
                return 'choice';
            case \PropelTypes::DATE:
                return 'date';
            case \PropelTypes::TIMESTAMP:
                return 'datetime';
            case CrudableBehavior::TYPE_TEXTRICH:
                $column->setType(PropelTypes::LONGVARCHAR);
                return 'textrich';
        }

        return null;
    }

    /**
     * Adding a constraints.
     *
     * @param Column $column
     * @return array
     */
    private function getConstraints(Column $column)
    {
        $constraints = array();

        if ($column->getName() == 'id') {
            return $constraints;
        }

        if ($column->getAttribute('required', false)) {
            $constraints[] = 'new Assert\NotBlank()';
        }

        return $constraints;
    }

    private function getFieldOptions($column)
    {
        $options = array();

        $options += array('required' => false);
        $options += array('label' => sprintf('%s.%s', $column->getTable()->getName(), $column->getName()));

        $constraints = $this->getConstraints($column);
        if (count($constraints)) {
            $options += array('constraints' => $constraints);
        }

        switch ($column->getType()) {
            case \PropelTypes::TIMESTAMP:
            case \PropelTypes::DATE:
                $options += array('widget' => 'single_text');
                break;
            case \PropelTypes::ENUM:
                $options += array('choices' => array_combine($column->getValueSet(), $column->getValueSet()));
                break;
        }

        return $options;
    }

    protected function addBuildForm(&$script)
    {
        $tableFields = $this->getTableFields($this->getTable()->getColumns());
        $joinTableFields = $this->getJoinTableFields();
        $tableI18nFields = $this->getTable()->hasBehavior('i18n') ? $this->getTableFields($this->getTable()->getBehavior('i18n')->getI18nColumns()) : array();

        foreach ($tableFields as $fieldname => $field) {
            $this->addFieldMethods($fieldname, $field, $script);
        }

        foreach ($joinTableFields as $fieldname => $field) {
            $this->addFieldMethods($fieldname, $field, $script);
        }

        foreach ($tableI18nFields as $fieldname => $field) {
            $this->addFieldMethods($fieldname, $field, $script);
        }

        $script .= "
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface \$builder, array \$options)
    {";

        foreach ($tableFields as $fieldname => $field) {
            $camelName = CrudableBehaviorUtils::camelize($fieldname);

            $script .= "
        \$builder->add('{$fieldname}', \$this->get{$camelName}Type(), \$this->get{$camelName}Options());";
        }

        foreach ($joinTableFields as $fieldname => $field) {
            $camelName = CrudableBehaviorUtils::camelize($fieldname);

            $script .= "
        \$builder->add('{$fieldname}', \$this->get{$camelName}Type(), \$this->get{$camelName}Options());";
        }

        if (count($tableI18nFields)) {
            $i18nTableName = sprintf('%sI18ns', $this->getTable()->getName());
            $i18nTableClassname = sprintf('%s\\%sI18n', $this->getTable()->getNamespace(), $this->getTable()->getPhpName());

            $script .= "\$builder->add('{$i18nTableName}', 'translation_collection', array(
            'i18n_class' => '{$i18nTableClassname}',
            'label' => '{$i18nTableName}',
            'required' => false,
            'languages' => array('fr', 'de'),
            'columns' => array(\n";

            foreach ($tableI18nFields as $fieldname => $field) {
                $camelName = CrudableBehaviorUtils::camelize($fieldname);

                $script .= "                '{$fieldname}' => array_merge(array('type' => \$this->get{$camelName}Type()), \$this->get{$camelName}Options()),\n";
            }


            $script .= "
            )
        ));

";
        }

        $script .= "
    }
";
    }

    protected function addFieldMethods($fieldname, $field, &$script)
    {
        $camelName = CrudableBehaviorUtils::camelize($fieldname);
        $type = $field['type'];
        $options = CrudableBehaviorUtils::formatArrayToString($field['options']);

        $script .= "
    public function get{$camelName}Type()
    {
        return '{$type}';
    }

    public function get{$camelName}Options()
    {
        return {$options};
    }
";
    }

    protected function getTableFields($columns)
    {
        $fields = array();
        $foreignKeysByTable = array();

        foreach ($columns as $column) {
            if ($column->isForeignKey()) {
                foreach ($column->getForeignKeys() as $fColumn) {
                    if (empty($foreignKeysByTable[$fColumn->getForeignTable()->getName()])) {
                        $foreignKeysByTable[$fColumn->getForeignTable()->getName()] = 0;
                    }

                    $foreignKeysByTable[$fColumn->getForeignTable()->getName()]++;
                }
            }
        }

        foreach ($columns as $column) {
            if ($column->isForeignKey()) {
                foreach ($column->getForeignKeys() as $fColumn) {
                    $columnName = $fColumn->getForeignTable()->getName();
                    if ($foreignKeysByTable[$fColumn->getForeignTable()->getName()] > 1) {
                        $columnName = sprintf("%s_related_by_%s", $columnName, $column->getName());
                    }

                    $fields[$columnName]['type'] = 'model';
                    $fields[$columnName]['options'] = array_merge($this->getFieldOptions($column), array(
                        'class' => sprintf('%s\\%s', $fColumn->getForeignTable()->getNamespace(), ucfirst($fColumn->getForeignTable()->getPhpName())),
                    ));
                }
            }
            else {
                if (in_array($column->getName(), $this->richtextFields)) {
                    $column->setType(CrudableBehavior::TYPE_TEXTRICH);
                }

                $fields[$column->getName()]['type'] = $this->getFieldType($column);
                $fields[$column->getName()]['options'] = $this->getFieldOptions($column);

                if (in_array($column->getName(), $this->fileFields)) {
                    $columnDeleted = clone $column;
                    $columnDeleted->setName($column->getName().'_deleted');
                    $fields[$columnDeleted->getName()]['type'] = 'checkbox';
                    $fields[$columnDeleted->getName()]['options'] = array_merge(array('mapped' => false), $this->getFieldOptions($columnDeleted));
                }
            }
        }

        return $fields;
    }

    protected function getJoinTableFields()
    {
        $fields = array();

        foreach ($this->getDatabase()->getTables() as $otherTable) {
            if ($otherTable->getName() == $this->getTable()->getName()) {
                continue;
            }

            foreach ($otherTable->getColumns() as $otherColumn) {
                $isForeignKey = false;
                if ($otherColumn->isForeignKey()) {
                    foreach ($otherColumn->getForeignKeys() as $otherColumnFK) {
                        if ($otherColumnFK->getForeignTable()->getName() == $this->getTable()->getName()) {
                            $isForeignKey = true;
                            break 2;
                        }
                    }
                }
            }

            if ($isForeignKey) {
                foreach ($otherTable->getColumns() as $otherColumn) {
                    foreach ($otherColumn->getForeignKeys() as $otherColumnFK) {
                        if ($otherColumnFK->getTable()->getAttribute('isCrossRef')) {
                            if ($otherColumnFK->getForeignTable()->getName() != $this->getTable()->getName()) {
                                $columnName = sprintf('%ss', $otherColumnFK->getForeignTable()->getName());
                                $fields[$columnName]['type'] = 'model';
                                $fields[$columnName]['options'] = array_merge($this->getFieldOptions($otherColumn), array(
                                    'class' => sprintf('%s\\%s', $otherTable->getNamespace(), ucfirst($otherColumnFK->getForeignTable()->getPhpName())),
                                    'multiple' => true
                                ));

                                break 2;
                            }
                        }
                    }
                }
            }
        }

        return $fields;
    }

    protected function addSetDefaultOptions(&$script)
    {
        $modelClassname = CrudableBehaviorUtils::getModelClassname(
            $this->getTable()->getNamespace(),
            $this->getTable()->getName()
        );

        $script .= "
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface \$resolver)
    {
        \$resolver->setDefaults(array(
            'data_class' => '{$modelClassname}',
        ));
    }
";
    }

    protected function addGetName(&$script)
    {
        $tableName = $this->getTable()->getName();

        $script .= "
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return '{$tableName}';
    }
";
    }
}
