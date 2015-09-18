<?php

/*
 * This file is part of the Twig Inline Optimization Extension package.
 *
 * (c) Chris Smith <chris@cs278.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cs278\TwigInlineOptization\Test;

/**
 * Integration test helper.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Karma Dordrak <drak@zikula.org>
 * @author Chris Smith <chris@cs278.org>
 */
abstract class AstIntegrationTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @return string
     */
    abstract protected function getFixturesDir();

    /**
     * @return Twig_ExtensionInterface[]
     */
    protected function getExtensions()
    {
        return [];
    }

    /**
     * @return Twig_SimpleFilter[]
     */
    protected function getTwigFilters()
    {
        return [];
    }

    /**
     * @return Twig_SimpleFunction[]
     */
    protected function getTwigFunctions()
    {
        return [];
    }

    /**
     * @return Twig_SimpleTest[]
     */
    protected function getTwigTests()
    {
        return [];
    }

    /**
     * @dataProvider getTests
     */
    public function testIntegration($file, $message, $condition, $templates, $exception, $config, $expected)
    {
        $this->doIntegrationTest($file, $message, $condition, $templates, $exception, $config, $expected);
    }

    public function getTests($name)
    {
        $fixturesDir = realpath($this->getFixturesDir());
        $tests = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fixturesDir), \RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
            if (!preg_match('/\.test$/', $file)) {
                continue;
            }

            $test = file_get_contents($file->getRealpath());

            if (preg_match('/--TEST--\s*(.*?)\s*(?:--CONDITION--\s*(.*))?\s*((?:--TEMPLATE--(?:.*?))+)\s*(?:--CONFIG--\s*(.*))?--EXCEPTION--\s*(.*)/sx', $test, $match)) {
                $message = $match[1];
                $condition = $match[2];
                $templates = $this->parseTemplates($match[3]);
                $config = $match[4];
                $exception = $match[5];
                $expected = '';
            } elseif (preg_match('/--TEST--\s*(.*?)\s*(?:--CONDITION--\s*(.*))?\s*((?:--TEMPLATE--(?:.*?))+)\s*(?:--CONFIG--\s*(.*))?--EXPECT--\s*(.*)/s', $test, $match)) {
                $message = $match[1];
                $condition = $match[2];
                $templates = $this->parseTemplates($match[3]);
                $exception = false;
                $config = $match[4];
                $expected = $match[5];
            } else {
                throw new \InvalidArgumentException(sprintf('Test "%s" is not valid.', str_replace($fixturesDir.'/', '', $file)));
            }

            $tests[] = [str_replace($fixturesDir.'/', '', $file), $message, $condition, $templates, $exception, $config, $expected];
        }

        return $tests;
    }

    protected function doIntegrationTest($file, $message, $condition, $templates, $exception, $config, $expected)
    {
        if ($condition) {
            eval('$ret = '.$condition.';');
            if (!$ret) {
                $this->markTestSkipped($condition);
            }
        }

        $loader = new \Twig_Loader_Array($templates);

        $config = array_merge([
            'cache' => false,
            'strict_variables' => true,
            'autoescape' => false,
        ], $config ? eval($config.';') : []);
        $twig = new \Twig_Environment($loader, $config);
        $twig->addGlobal('global', 'global');
        foreach ($this->getExtensions() as $extension) {
            $twig->addExtension($extension);
        }

        foreach ($this->getTwigFilters() as $filter) {
            $twig->addFilter($filter);
        }

        foreach ($this->getTwigTests() as $test) {
            $twig->addTest($test);
        }

        foreach ($this->getTwigFunctions() as $function) {
            $twig->addFunction($function);
        }

        // avoid using the same PHP class name for different cases
        // only for PHP 5.2+
        if (PHP_VERSION_ID >= 50300) {
            $p = new \ReflectionProperty($twig, 'templateClassPrefix');
            $p->setAccessible(true);
            $p->setValue($twig, '__TwigTemplate_'.hash('sha256', uniqid(mt_rand(), true), false).'_');
        }

        try {
            $template = $twig->loadTemplate('index.twig');
        } catch (\Exception $e) {
            if (false !== $exception) {
                $this->assertSame(trim($exception), trim(sprintf('%s: %s', get_class($e), $e->getMessage())));

                return;
            }

            if ($e instanceof \Twig_Error_Syntax) {
                $e->setTemplateFile($file);

                throw $e;
            }

            throw new \Twig_Error(sprintf('%s: %s', get_class($e), $e->getMessage()), -1, $file, $e);
        }

        try {
            $source = $loader->getSource('index.twig');
            $output = $this->normalizeAst($twig->parse($twig->tokenize($source, 'index.twig')));
        } catch (\Exception $e) {
            if (false !== $exception) {
                $this->assertSame(trim($exception), trim(sprintf('%s: %s', get_class($e), $e->getMessage())));

                return;
            }

            if ($e instanceof \Twig_Error_Syntax) {
                $e->setTemplateFile($file);
            } else {
                $e = new \Twig_Error(sprintf('%s: %s', get_class($e), $e->getMessage()), -1, $file, $e);
            }

            $output = trim(sprintf('%s: %s', get_class($e), $e->getMessage()));
        }

        if (false !== $exception) {
            list($class) = explode(':', $exception);
            $this->assertThat(null, new \PHPUnit_Framework_Constraint_Exception($class));
        }

        $expected = $this->normalizeAst($expected);

        if ($expected !== $output) {
            $source = $loader->getSource('index.twig');
            fputs(STDERR, "AST:\n");
            fputs(STDERR, $twig->parse($twig->tokenize($source, 'index.twig'))."\n");
            fputs(STDERR, "\nCompiled template:\n");
            fputs(STDERR, $twig->compile($twig->parse($twig->tokenize($source, 'index.twig'))));
        }
        $this->assertEquals($expected, $output, $message.' (in '.$file.')');
    }

    protected static function parseTemplates($test)
    {
        $templates = [];
        preg_match_all('/--TEMPLATE(?:\((.*?)\))?--(.*?)(?=\-\-TEMPLATE|$)/s', $test, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $templates[($match[1] ? $match[1] : 'index.twig')] = $match[2];
        }

        return $templates;
    }

    private function normalizeAst($ast)
    {
        $ast = trim($ast, " \n");
        $ast = explode("\n", $ast);
        $ast = array_map(function ($line) {
            return rtrim($line);
        }, $ast);
        $ast = implode("\n", $ast);

        return $ast;
    }
}
