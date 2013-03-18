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

use C2is\AdminGen\Controller\RouterController;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\TranslationServiceProvider;

use Exception;
use InvalidArgumentException;

/**
 * Silex Admin Gen provider.
 *
 * @author Morgan Brunot <brunot.morgan@gmail.com>
 */
class AdminGenServiceProvider implements ServiceProviderInterface
{
    protected $config;

    public function register(Application $app)
    {
        $app['twig.loader.filesystem'] = $app->share($app->extend('twig.loader.filesystem', function ($loader, $app) {
            $loader->addPath(__DIR__.'/../AdminGen/Resources/views', 'AdminGen');

            return $loader;
        }));

        $app['form.extensions'] = $app->share($app->extend('form.extensions', function ($extensions) use ($app) {
            $extensions[] = new \C2is\Form\Extension();

            return $extensions;
        }));

        $app['admingen_menu'] = $app->share(function ($app) {
            return array();
        });

        $app->register(new TranslationServiceProvider());
    }

    public function boot(Application $app)
    {
        if (!isset($app['admin_gen.config_file'])) {
            throw new InvalidArgumentException(
                'Unable to guess the config file. Please, initialize the "admin_gen.config_file" parameter.'
            );
        }

        $admin  = isset($app['admin_gen.mount_path']) ? trim($app['admin_gen.mount_path'], '/') : 'admin';
        $config = require_once $app['admin_gen.config_file'];

        foreach ($config as $escapedUrl => $options) {
            $url = "/$admin/".str_replace('::', '/', $escapedUrl);
            $router = new RouterController(
                $options['name'],
                $options['model'],
                $options['form'],
                $options['listing']
            );


            $app['admingen_menu'] = $app->share($app->extend('admingen_menu', function ($menu) use ($escapedUrl, $url) {
                $menu[] = array(
                    'name'   => "$escapedUrl",
                    'url'    => "$url",
                );

                return $menu;
            }));


            $app->mount($url, $router);
        }
    }
}
