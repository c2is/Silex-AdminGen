<?php

require_once dirname(__FILE__) . '/CrudableBehaviorUtils.php';
require_once dirname(__FILE__) . '/CrudableBehaviorObjectBuilderModifier.php';
require_once dirname(__FILE__) . '/CrudableBehaviorBaseFormTypeBuilder.php';
require_once dirname(__FILE__) . '/CrudableBehaviorFormTypeBuilder.php';
require_once dirname(__FILE__) . '/CrudableBehaviorBaseListingBuilder.php';
require_once dirname(__FILE__) . '/CrudableBehaviorListingBuilder.php';

use Symfony\Component\Filesystem\Filesystem;

class CrudableBehavior extends Behavior
{
    const TYPE_FILE     = 'type_file';
    const TYPE_TEXTRICH = 'type_textrich';

    // default parameters value
    protected $parameters = array(
        'route_mount'   => '/',
        'path'          => null,
    );

    // additional builders
    protected $additionalBuilders = array(
        'CrudableBehaviorBaseFormTypeBuilder',
        'CrudableBehaviorBaseListingBuilder',
        'CrudableBehaviorFormTypeBuilder',
        'CrudableBehaviorListingBuilder',
    );

    public function getTypeFileColumns()
    {
        $typeFileString = str_replace(' ', '', $this->getParameter('type_file'));

        return explode(',', $typeFileString);
    }

    public function getTypeTextrichColumns()
    {
        $typeTextrichString = str_replace(' ', '', $this->getParameter('type_textrich'));

        return explode(',', $typeTextrichString);
    }

    public function hasTypeFile()
    {
        return $this->getParameter('type_file') != null;
    }

    public function modifyTable()
    {
        if ($this->getTable()->containsColumn('active'))
        {
            throw new Exception(sprintf("The active column is automatically added by Crudable Behavior. Please, remove the active column on the %s table.", $this->getTable()->getName()), 1);
        }

        $this->getTable()->addColumn(array(
            'name'    => 'active',
            'type'    => 'BOOLEAN',
            'default' => true,
        ));
    }

    public function getObjectBuilderModifier()
    {
        if (is_null($this->objectBuilderModifier)) {
            $this->objectBuilderModifier = new CrudableBehaviorObjectBuilderModifier($this);
        }

        return $this->objectBuilderModifier;
    }

    public function staticMethods()
    {
        $queryClassname = CrudableBehaviorUtils::getQueryClassname(
            $this->getTable()->getNamespace(),
            $this->getTable()->getName()
        );

        $script = "
    /**
     * @return ModelCriteria The Query object used for listing
     */
    public static function getListQuery()
    {
        return $queryClassname::create();
    }
";

        return $script;
    }
}
