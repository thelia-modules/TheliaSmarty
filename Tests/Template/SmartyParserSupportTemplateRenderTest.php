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

namespace TheliaSmarty\Tests\Template;

use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Core\Template\ParserContext;
use Thelia\Core\Template\TemplateDefinition;
use Thelia\Core\Template\TemplateHelperInterface;
use TheliaSmarty\Template\SmartyParser;

/**
 * The parser answers whether it can render a view. Answering yes to every name made the
 * resolver unable to report an unknown view, since Smarty is the last parser probed: the
 * decision moved to render time, where a view missing from the theme could still be served
 * from another registered directory.
 *
 * The answer therefore has to cover the same directories render() probes - the theme, the
 * themes it inherits from, the directories modules contribute to them, and everything else
 * already registered on the Smarty instance - and only then say no.
 */
class SmartyParserSupportTemplateRenderTest extends TestCase
{
    /** A theme name no install ships, so no real theme descriptor is ever read. */
    private const THEME_NAME = 'smarty-parser-test-theme';

    private static string $workingDirectory = '';

    public static function setUpBeforeClass(): void
    {
        if (!\defined('DS')) {
            \define('DS', \DIRECTORY_SEPARATOR);
        }

        self::$workingDirectory = sys_get_temp_dir().DS.'smarty-parser-support-'.uniqid('', false);

        if (!\defined('THELIA_CACHE_DIR')) {
            \define('THELIA_CACHE_DIR', self::$workingDirectory.DS.'cache');
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::removeDirectory(self::$workingDirectory);
    }

    protected function setUp(): void
    {
        self::removeDirectory(self::$workingDirectory);

        mkdir($this->themeDirectory(), 0o777, true);
        mkdir($this->moduleTemplateDirectory(), 0o777, true);
    }

    public function testAThemeTemplateIsRenderable(): void
    {
        $parser = $this->createParser();

        touch($this->themeDirectory().DS.'page.html');

        $this->assertTrue($parser->supportTemplateRender($this->themeDirectory(), 'page'));
    }

    public function testATemplateShippedByAModuleIsRenderableEvenWhenTheThemeDoesNotOverrideIt(): void
    {
        $parser = $this->createParser();
        $parser->addTemplateDirectory(
            TemplateDefinition::FRONT_OFFICE,
            self::THEME_NAME,
            $this->moduleTemplateDirectory(),
            'SomeModule'
        );

        touch($this->moduleTemplateDirectory().DS.'page.html');

        $this->assertFileDoesNotExist($this->themeDirectory().DS.'page.html');
        $this->assertTrue($parser->supportTemplateRender($this->themeDirectory(), 'page'));
    }

    /**
     * render() probes templateExists() over every directory registered on the Smarty
     * instance, which is how the back office serves a template a module contributes to it.
     * A directory reachable at render time has to keep the parser answering yes.
     */
    public function testATemplateFoundInAnyDirectoryRegisteredOnSmartyIsRenderable(): void
    {
        $parser = $this->createParser();
        $parser->addTemplateDir($this->moduleTemplateDirectory(), 'some-module');

        touch($this->moduleTemplateDirectory().DS.'admin-include.html');

        $this->assertTrue($parser->supportTemplateRender($this->themeDirectory(), 'admin-include'));
    }

    /**
     * Every caller resolving a parser sets the template definition with the fallback to the
     * default template enabled, so a template a module ships for the "default" template is
     * served even when another template is active - which is how a module template not ported
     * to the active theme keeps rendering.
     */
    public function testATemplateShippedByAModuleForTheDefaultTemplateIsRenderable(): void
    {
        $parser = $this->createParser();
        $parser->addTemplateDirectory(
            TemplateDefinition::FRONT_OFFICE,
            'default',
            $this->moduleTemplateDirectory(),
            'SomeModule'
        );

        touch($this->moduleTemplateDirectory().DS.'page.html');

        $this->assertTrue($parser->supportTemplateRender($this->themeDirectory(), 'page'));
    }

    public function testATemplateOfTheOtherHandledExtensionIsRenderable(): void
    {
        $parser = $this->createParser();

        touch($this->themeDirectory().DS.'page.tpl');

        $this->assertTrue($parser->supportTemplateRender($this->themeDirectory(), 'page'));
    }

    public function testATemplateNameAlreadyCarryingItsExtensionIsRenderable(): void
    {
        $parser = $this->createParser();

        touch($this->themeDirectory().DS.'page.html');

        $this->assertTrue($parser->supportTemplateRender($this->themeDirectory(), 'page.html'));
    }

    public function testAViewNoDirectoryHoldsIsNotRenderable(): void
    {
        $parser = $this->createParser();
        $parser->addTemplateDirectory(
            TemplateDefinition::FRONT_OFFICE,
            self::THEME_NAME,
            $this->moduleTemplateDirectory(),
            'SomeModule'
        );
        $parser->addTemplateDir($this->moduleTemplateDirectory(), 'some-module');

        $this->assertFalse($parser->supportTemplateRender($this->themeDirectory(), 'page'));
    }

    public function testATemplateShippedByAModuleForAnotherThemeIsNotRenderable(): void
    {
        $parser = $this->createParser();
        $parser->addTemplateDirectory(
            TemplateDefinition::FRONT_OFFICE,
            'some-other-theme',
            $this->moduleTemplateDirectory(),
            'SomeModule'
        );

        touch($this->moduleTemplateDirectory().DS.'page.html');

        $this->assertFalse($parser->supportTemplateRender($this->themeDirectory(), 'page'));
    }

    public function testAViewEscapingTheTemplateDirectoriesIsNotRenderable(): void
    {
        $parser = $this->createParser();

        touch(\dirname($this->themeDirectory()).DS.'outside.html');

        $this->assertFalse($parser->supportTemplateRender($this->themeDirectory(), '..'.DS.'outside'));
    }

    private function createParser(): SmartyParser
    {
        $parser = new SmartyParser(
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(ParserContext::class),
            $this->createMock(TemplateHelperInterface::class),
            'test',
            false
        );

        $parser->requestStack = new RequestStack();

        // A fresh Smarty instance searches its own default directory, which the parser
        // never uses: only the directories Thelia registers are relevant here.
        $parser->setTemplateDir([]);

        return $parser;
    }

    private function themeDirectory(): string
    {
        return self::$workingDirectory.DS.'templates'.DS.'frontOffice'.DS.self::THEME_NAME;
    }

    private function moduleTemplateDirectory(): string
    {
        return self::$workingDirectory.DS.'modules'.DS.'SomeModule'.DS.'templates';
    }

    private static function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($directory);
    }
}
