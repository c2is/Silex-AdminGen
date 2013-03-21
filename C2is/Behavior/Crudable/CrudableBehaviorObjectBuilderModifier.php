<?php

require_once dirname(__FILE__) . '/CrudableBehaviorUtils.php';

use Symfony\Component\Filesystem\Filesystem;

class CrudableBehaviorObjectBuilderModifier
{
    protected
        $behavior,
        $table,
        $builder;

    public function __construct($behavior)
    {
        $this->behavior = $behavior;
        $this->table = $behavior->getTable();
    }

    protected function getParameter($key)
    {
        return $this->behavior->getParameter($key);
    }

    protected function getParameters()
    {
        return $this->behavior->getParameters();
    }

    protected function getTypeFileColumns()
    {
        return $this->behavior->getTypeFileColumns();
    }

    public function objectMethods($builder)
    {
        $this->builder = $builder;

        // generate confif files
        $this->generateAdminGenConfig();

        $script = $this->addSaveFromCrud($builder);

        if ($this->behavior->hasTypeFile()) {
            $script .= $this->addGetUploadDir($builder);
            $script .= $this->addGetUploadRootDir($builder);

            foreach ($this->getTypeFileColumns() as $column) {
                $script .= $this->addUploadFile($builder, $column);
            }
        }

        return $script;
    }

    public function addSaveFromCrud($builder)
    {
        $columns = array();

        if ($this->behavior->hasTypeFile()) {
            foreach ($this->getTypeFileColumns() as $column) {
                $columns[] = array(
                    'filecolumnName'     => CrudableBehaviorUtils::camelize($column),
                    'deletedColumnName'  => sprintf("%s_deleted", $column),
                    'fileColumnPeerName' => sprintf("%sPeer::%s", CrudableBehaviorUtils::camelize($this->behavior->getTable()->getName()), strtoupper($column)),
                );
            }
        }

        return $this->behavior->renderTemplate('objectSaveFromCrud', array(
            'columns' => $columns,
        ));
    }

    public function addGetUploadDir($builder)
    {
        return $this->behavior->renderTemplate('objectGetUploadDir', array(
            'dir' => sprintf("%ss", $this->behavior->getTable()->getName()),
        ));
    }

    public function addGetUploadRootDir($builder)
    {
        $absoluteModelDir = realpath(sprintf('%s/%s/%s',
            $this->builder->getTable()->getGeneratorConfig()->getBuildProperties()['phpDir'],
            str_replace('\\', '/', $this->builder->getTable()->getDatabase()->getNamespace()),
            $this->builder->getTable()->getGeneratorConfig()->getBuildProperties()['namespaceOm']
        ));

        $absoluteWebDir = realpath($this->builder->getTable()->getGeneratorConfig()->getBuildProperties()['behaviorCrudableWebDir']);

        $relativeWebDir = implode('/', array_diff_assoc(explode('/', $absoluteWebDir), explode('/', $absoluteModelDir)));
        $subLevel       = str_repeat('/..', count(array_diff_assoc(explode('/', $absoluteModelDir), explode('/', $absoluteWebDir))));

        return $this->behavior->renderTemplate('objectGetUploadRootDir', array(
            'dir' => sprintf("%s/%s/", $subLevel, $relativeWebDir),
        ));
    }

    public function addUploadFile($builder, $column)
    {
        return $this->behavior->renderTemplate('objectUploadFile', array(
            'column'         => $column,
            'columnCamelize' => CrudableBehaviorUtils::camelize($column),
        ));
    }

    protected function generateAdminGenConfig()
    {
        // crudable parameters
        $crudableParameters = $this->getParameters();

        if (!isset($crudableParameters['path'])) {
            return;
        }

        // validate the existence of the crud configuration file
        $phpConfDir   = $this->behavior->getTable()->getGeneratorConfig()->getBuildProperties()['behaviorCrudablePhpconfDir'];
        $crudFilename = sprintf("%s/admingen-conf.php", rtrim($phpConfDir, '/'));

        $modelClassname = CrudableBehaviorUtils::getModelClassname(
            $this->behavior->getTable()->getNamespace(),
            $this->behavior->getTable()->getName()
        );

        $formClassname = CrudableBehaviorUtils::getFormClassname(
            $this->behavior->getTable()->getNamespace(),
            $this->behavior->getTable()->getName()
        );

        $queryClassname = CrudableBehaviorUtils::getQueryClassname(
            $this->behavior->getTable()->getNamespace(),
            $this->behavior->getTable()->getName()
        );

        $listingClassname = CrudableBehaviorUtils::getListingClassname(
            $this->behavior->getTable()->getNamespace(),
            $this->behavior->getTable()->getName()
        );

        if (!file_exists($crudFilename)) {
            $fs = new Filesystem();
            $fs->touch($crudFilename);
            $controllersConf = array();
        }
        else {
            $controllersConf = require $crudFilename;
        }

        if (!is_array($controllersConf)) {
            $fs = new Filesystem();
            $fs->remove($crudFilename);

            throw new Exception("Error Processing CrudableBehavior::generateAdminGenConfig()", 1);
        }

        $controllersConf[str_replace('/', '::', $crudableParameters['path'])] = array(
            'name' => $this->behavior->getTable()->getName(),
            'model' => $modelClassname,
            'form' => $formClassname,
            'query' => $queryClassname,
            'listing' => $listingClassname,
        );

        // write the controllers configuration file
        file_put_contents(
            $crudFilename,
            sprintf("<?php\nreturn %s;\n", var_export($controllersConf, true))
        );
    }
}
