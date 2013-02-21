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
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

use Exception;
use Twig_Environment;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\urlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Cungfoo\Lib\Listing\Listing;
use Cungfoo\Lib\Listing\Filler;
use Cungfoo\Lib\Listing\Column;
use Cungfoo\Model\Metadata;
use Cungfoo\Model\MetadataQuery;
use Cungfoo\Form\Type\MetadataType;
use Cungfoo\Model\Seo;
use Cungfoo\Model\SeoQuery;
use Cungfoo\Form\Type\SeoType;
use Cungfoo\Form\Type\ContextType;

/**
 * RouterController.
 *
 * @author Morgan Brunot <brunot.morgan@gmail.com>
 */
class RouterController
{
    private $config;
    private $urlGenerator;
    private $twig;

    public function __construct(Twig_Environment $twig, urlGenerator $urlGenerator, $configFile)
    {
        if (!file_exists($configFile)) {
            throw new Exception("Admin gen config files does not exist", 1);
        }

        $this->config = require_once $configFile;
        $this->twig = $twig;
        $this->urlGenerator = $urlGenerator;
    }

    public function listAction($class)
    {
        return new Response($this->twig->render('@AdminGen/Router/list.html.twig'));
    }

    public function editAction($class)
    {
        return $this->updateAction($class);
    }

    public function updateAction($class, $id = null)
    {
        return new Response($this->twig->render('@AdminGen/Router/edit.html.twig'));
    }

    public function deleteAction($class, $id)
    {
        return new RedirectResponse($this->urlGenerator->generate('admin_gen_list', array('class' => $class)), 302);
    }
}
