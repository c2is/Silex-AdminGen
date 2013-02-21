<?php

/*
 * This file is part of the c2is/Silex-AdminGen.
 *
 * (c) Morgan Brunot <brunot.morgan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace C2is\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\ControllerProviderInterface;
use Silex\ServiceControllerResolver;

use C2is\AdminGen\Controller\RouterController;

/**
 * Silex Admin Gen provider.
 *
 * @author Morgan Brunot <brunot.morgan@gmail.com>
 */
class AdminGenServiceProvider implements ServiceProviderInterface, ControllerProviderInterface
{
    public function register(Application $app)
    {
        $app['admin_gen.controller.router'] = $app->share(function ($app) {
            return new RouterController($app['twig'], $app['url_generator'], $app['admin_gen.config_dir']);
        });

        $app['twig.loader.filesystem'] = $app->share($app->extend('twig.loader.filesystem', function ($loader, $app) {
            $loader->addPath(__DIR__.'/../AdminGen/Resources/views', 'AdminGen');

            return $loader;
        }));
    }

    public function connect(Application $app)
    {
        if (!$app['resolver'] instanceof ServiceControllerResolver) {
            // using RuntimeException crashes PHP?!
            throw new \LogicException('You must enable the ServiceController service provider to be able to use the WebProfiler.');
        }

        $controllers = $app['controllers_factory'];
        $controllers->match('/{class}', 'admin_gen.controller.router:listAction')->bind('admin_gen_list');
        $controllers->match('/{class}/create', 'admin_gen.controller.router:editAction')->bind('admin_gen_create');
        $controllers->match('/{class}/{id}/update', 'admin_gen.controller.router:updateAction')->bind('admin_gen_update');
        $controllers->match('/{class}/{id}/delete', 'admin_gen.controller.router:deleteAction')->bind('admin_gen_delete');

        return $controllers;
    }

    public function boot(Application $app)
    {

    }
}
