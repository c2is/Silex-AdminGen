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

use C2is\AdminGen\Controller\DashboardController;
use C2is\AdminGen\Controller\RouterController;
use C2is\AdminGen\Twig\Extension\ContextRoutingExtension;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\ValidatorServiceProvider;

use Symfony\Component\Translation\Loader\YamlFileLoader;

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

    protected $contextParameters = array(
        'limit'  => 50,
        'widget' => false,
        'order'  => 'DESC',
    );

    public function register(Application $app)
    {
        $app->register(new TranslationServiceProvider());
        $app->register(new ValidatorServiceProvider());

        $app['twig.loader.filesystem'] = $app->share($app->extend('twig.loader.filesystem', function ($loader, $app) {
            $loader->addPath(__DIR__.'/../AdminGen/Resources/views', 'AdminGen');

            return $loader;
        }));

        $app['form.extensions'] = $app->share($app->extend('form.extensions', function ($extensions) use ($app) {
            $extensions[] = new \C2is\Form\Extension();

            return $extensions;
        }));

        $app['admingen.menu'] = $app->share(function ($app) {
            return array();
        });

        $app['admingen.context_parameters'] = $app->share(function ($app) {
            $contextParameters = array();

            foreach ($this->contextParameters as $name => $default) {
                $contextParameters[$name] = $app['request']->get($name, $default);
            }

            return $contextParameters;
        });

        $app['translator'] = $app->share($app->extend('translator',
            function($translator, $app) {
                $translator->addLoader('yaml', new YamlFileLoader());
                $translator->addResource('yaml', __DIR__.'/../AdminGen/Resources/locales/locales-fr.yml', 'fr');

                return $translator;
            }
        ));

        $app['translator']->setLocale(isset($app['admin_gen.language']) ? $app['admin_gen.language'] : 'fr');
    }

    public function boot(Application $app)
    {
        if (!isset($app['admin_gen.config_file'])) {
            throw new InvalidArgumentException(
                'Unable to guess the config file. Please, initialize the "admin_gen.config_file" parameter.'
            );
        }

        $app['twig'] = $app->share($app->extend('twig', function ($twig, $app) {
            $twig->addExtension(new ContextRoutingExtension($app['url_generator'], array_diff_assoc($app['admingen.context_parameters'], $this->contextParameters)));

            return $twig;
        }));

        $admin  = isset($app['admin_gen.mount_path']) ? trim($app['admin_gen.mount_path'], '/') : 'admin';
        $config = require_once $app['admin_gen.config_file'];

        $app->mount("/$admin", new DashboardController());

        foreach ($config as $escapedUrl => $options) {
            $url = "/$admin/".str_replace('::', '/', $escapedUrl);
            $router = new RouterController(
                $options['name'],
                $options['model'],
                $options['form'],
                $options['listing']
            );


            $app['admingen.menu'] = $app->share($app->extend('admingen.menu', function ($menu) use ($options) {
                $menu[] = array(
                    'name' => $options['name']
                );

                return $menu;
            }));


            $app->mount($url, $router);
        }
    }
}
