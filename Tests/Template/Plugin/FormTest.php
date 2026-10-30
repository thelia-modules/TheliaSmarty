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

use Thelia\Core\Template\ParserContext;
use TheliaSmarty\Template\Plugins\Form;

/**
 * Class FormTest.
 *
 * @author Benjamin Perche <benjamin@thelia.net>
 */
class FormTest extends SmartyPluginTestCase
{
    /**
     * @var Form
     */
    protected $plugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plugin = $this->container->get(Form::class);
    }

    public function testSimpleStackedForm(): void
    {
        $parserContext = $this->getParserContext();
        $this->assertNull($parserContext->popCurrentForm());

        // First, initialize form
        // eq: {form name="thelia.empty"}
        $repeat = true;
        $this->plugin->generateForm(
            ['name' => 'thelia.empty'],
            '',
            $this->createTemplate(),
            $repeat
        );

        // Here, the current form is present
        $this->assertInstanceOf('Thelia\\Form\\EmptyForm', $parserContext->getCurrentForm());
        $this->assertInstanceOf('Thelia\\Form\\EmptyForm', $form = $parserContext->popCurrentForm());
        // But not after we have pop
        $this->assertNull($parserContext->popCurrentForm());

        // So we re-push it into the stack
        $parserContext->pushCurrentForm($form);

        // And run the ending form tag
        // eq: {/form}
        $repeat = false;
        $this->plugin->generateForm(
            ['name' => 'thelia.empty'],
            '',
            $this->createTemplate(),
            $repeat
        );

        // There is no more form in the stack
        $this->assertNull($parserContext->popCurrentForm());

        // Let's even predict an exception
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'There is currently no defined form'
        );

        $parserContext->getCurrentForm();
    }

    public function testMultipleStackedForms(): void
    {
        $parserContext = $this->getParserContext();
        $this->assertNull($parserContext->popCurrentForm());

        // First form:
        // eq: {form name="thelia.empty"}
        $repeat = true;
        $this->plugin->generateForm(
            ['name' => 'thelia.empty'],
            '',
            $this->createTemplate(),
            $repeat
        );

        $this->assertInstanceOf('Thelia\\Form\\EmptyForm', $parserContext->getCurrentForm());

        // Then next one:
        // eq: {form name="thelia.admin.login"}
        $repeat = true;
        $this->plugin->generateForm(
            ['name' => 'thelia.admin.login'],
            '',
            $this->createTemplate(),
            $repeat
        );

        $this->assertInstanceOf('Thelia\\Form\\AdminLogin', $parserContext->getCurrentForm());

        // Third form:
        // eq: {form name="thelia.empty"}
        $repeat = true;
        $this->plugin->generateForm(
            ['name' => 'thelia.empty'],
            '',
            $this->createTemplate(),
            $repeat
        );

        $this->assertInstanceOf('Thelia\\Form\\EmptyForm', $parserContext->getCurrentForm());

        // Then, Let's close forms
        // eq: {/form} {* related to {form name="thelia.empty"} *}
        $repeat = false;
        $this->plugin->generateForm(
            ['name' => 'thelia.empty'],
            '',
            $this->createTemplate(),
            $repeat
        );

        $this->assertInstanceOf('Thelia\\Form\\AdminLogin', $parserContext->getCurrentForm());

        // Then, Let's close forms
        // eq: {/form} {* related to {form name="thelia.admin.login"} *}
        $repeat = false;
        $this->plugin->generateForm(
            ['name' => 'thelia.admin.login'],
            '',
            $this->createTemplate(),
            $repeat
        );

        $this->assertInstanceOf('Thelia\\Form\\EmptyForm', $parserContext->getCurrentForm());

        // Then close the first form:
        // eq: {/form} {* related to {form name="thelia.empty"} *}
        $repeat = false;
        $this->plugin->generateForm(
            ['name' => 'thelia.empty'],
            '',
            $this->createTemplate(),
            $repeat
        );

        // The exception
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'There is currently no defined form'
        );

        $parserContext->getCurrentForm();
    }

    /**
     * @return \Thelia\Core\Template\ParserContext
     */
    protected function getParserContext()
    {
        return $this->container->get(ParserContext::class);
    }

    /**
     * A template bound to the parser, as the plugin reads the Smarty settings through it.
     */
    private function createTemplate(): \Smarty_Internal_Template
    {
        $template = $this->createStub(\Smarty_Internal_Template::class);
        $template->smarty = $this->smarty;

        return $template;
    }
}
