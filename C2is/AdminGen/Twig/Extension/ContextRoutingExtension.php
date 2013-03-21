<?php

/*
 * This file is part of the c2is/Silex-AdminGen.
 *
 * (c) Morgan Brunot <brunot.morgan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace C2is\AdminGen\Twig\Extension;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * ContextRouting Extension
 *
 * @author Morgan Brunot <brunot.morgan@gmail.com>
 */
class ContextRoutingExtension extends \Twig_Extension
{
    private
        $generator,
        $contextParameters;

    public function __construct(UrlGeneratorInterface $generator, $contextParameters)
    {
        $this->generator = $generator;
        $this->contextParameters = $contextParameters;
    }

    public function getFunctions()
    {
        return array(
            'context_url'  => new \Twig_Function_Method($this, 'getContextUrl'),
            'context_path' => new \Twig_Function_Method($this, 'getContextPath'),
        );
    }

    public function getContextUrl($name, $parameters = array(), $relative = false)
    {
        return $this->generator->generate($name, array_merge($this->contextParameters, $parameters), $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    public function getContextPath($name, $parameters = array(), $schemeRelative = false)
    {
        return $this->generator->generate($name, array_merge($this->contextParameters, $parameters), $schemeRelative ? UrlGeneratorInterface::NETWORK_PATH : UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function getName()
    {
        return 'context_routing';
    }
}
