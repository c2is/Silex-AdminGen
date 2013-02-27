<?php

/*
 * This file is part of the c2is/Silex-AdminGen.
 *
 * (c) Morgan Brunot <brunot.morgan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace C2is\AdminGen\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Exception;

/**
 * RouterController.
 *
 * @author Morgan Brunot <brunot.morgan@gmail.com>
 */
class RouterController implements ControllerProviderInterface
{
    private
        $name,
        $modelClassname,
        $queryClassname,
        $peerClassname,
        $formClassname;

    public function __construct($name, $modelClassname, $formClassname)
    {
        if (!class_exists($modelClassname)) {
            throw new Exception($modelClass.' model classname is undefined.');
        }

        $this->name = $name;
        $this->modelClassname = $modelClassname;
        $this->queryClassname = $modelClassname.'Query';
        $this->peerClassname = $modelClassname.'Peer';
        $this->formClassname = $formClassname;
    }

    public function connect(Application $app)
    {
        $ctl = $app['controllers_factory'];

        // convert id url to object
        $ctl->convert('object', array($this, 'idToObject'));

        // bind list action
        $ctl->get('/{pageSlug}/{page}', array($this, 'listAction'))
            ->assert('pageSlug', 'pages')
            ->value('pageSlug', 'pages')
            ->assert('page', '\d+')
            ->value('page', 1)
            ->bind($this->name.'_admingen_list');

        // bind create action
        $ctl->get('/create', array($this, 'updateAction'))
            ->bind($this->name.'_admingen_create');

        // bind update action
        $ctl->get('/{object}/update', array($this, 'updateAction'))
            ->assert('object', '\d*')
            ->bind($this->name.'_admingen_update');

        // bind delete action
        $ctl->get('/{object}/delete', array($this, 'deleteAction'))
            ->assert('object', '\d*')
            ->bind($this->name.'_admingen_delete');

        return $ctl;
    }

    function idToObject($object)
    {
        if (!$object) return;

        $modelObject = call_user_func($this->queryClassname.'::create')
            ->filterById($object)
            ->findOne()
        ;

        if (!$modelObject) {
            throw new HttpException(404, 'Page not found');
        }

        return $modelObject;
    }

    function listAction(Application $app, Request $request, $page)
    {
        return $app->renderView('@AdminGen/Router/list.html.twig');
    }

    function editAction(Application $app, Request $request)
    {
        return $this->updateAction();
    }

    function updateAction(Application $app, Request $request, $object = null)
    {
        return $app->renderView('@AdminGen/Router/edit.html.twig');
    }

    function deleteAction(Application $app, Request $request, $object)
    {
        return $app->redirect($app->path($this->name.'_admingen_list'), 302);
    }
}
