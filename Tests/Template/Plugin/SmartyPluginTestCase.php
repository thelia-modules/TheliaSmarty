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

namespace TheliaSmarty\Tests\Template\Plugin;

use Psr\Container\ContainerInterface;
use Thelia\Test\IntegrationTestCase;
use TheliaSmarty\Template\SmartyParser;

/**
 * Base of the tests of the Smarty plugins: the plugin under test comes from the container of the
 * booted kernel, the parser is the one that renders the fixtures.
 *
 * @author Benjamin Perche <bperche@openstudio.fr>
 */
abstract class SmartyPluginTestCase extends IntegrationTestCase
{
    protected ContainerInterface $container;

    protected SmartyParser $smarty;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = static::getContainer();
        $this->smarty = $this->container->get(SmartyParser::class);
    }

    protected function render($template, $data = [])
    {
        return $this->internalRender('file', __DIR__.DS.'fixtures'.DS.$template, $data);
    }

    protected function renderString($template, $data = [])
    {
        return $this->internalRender('string', $template, $data);
    }

    protected function internalRender($resourceType, $resourceContent, array $data)
    {
        foreach ($data as $key => $value) {
            $this->smarty->assign($key, $value);
        }

        return $this->smarty->fetch(sprintf('%s:%s', $resourceType, $resourceContent));
    }
}
