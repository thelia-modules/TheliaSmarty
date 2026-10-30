<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TheliaSmarty\Tests\Template\Plugin\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TestController.
 *
 * @author Benjamin Perche <bperche@openstudio.fr>
 */
class TestController
{
    public function testAction()
    {
        return new Response('world');
    }

    public function testParamsAction($paramA, $paramB)
    {
        return new Response($paramA.$paramB);
    }

    public function testMethodAction(Request $currentRequest)
    {
        return $currentRequest->getMethod();
    }

    public function testQueryAction(Request $currentRequest)
    {
        return $currentRequest->query->get('foo');
    }

    public function testRequestAction(Request $currentRequest)
    {
        return $currentRequest->request->get('foo').$currentRequest->getMethod();
    }
}
