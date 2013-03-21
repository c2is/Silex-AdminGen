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

use C2is\Lib\Listing\Filler\PropelFiller;

use Silex\Application;
use Silex\ControllerProviderInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Exception;

/**
 * DashboardController.
 *
 * @author Morgan Brunot <brunot.morgan@gmail.com>
 */
class DashboardController implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $ctl = $app['controllers_factory'];

        return $ctl;
    }
}
