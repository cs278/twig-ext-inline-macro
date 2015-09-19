<?php

/*
 * This file is part of the Twig Inline Optimization Extension package.
 *
 * (c) Chris Smith <chris@cs278.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cs278\TwigInlineOptization;

use Cs278\TwigInlineOptization\Test\AstIntegrationTestCase;

/**
 * Integration test helper.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Karma Dordrak <drak@zikula.org>
 */
class AstIntegrationTest extends AstIntegrationTestCase
{
    protected function getExtensions()
    {
        return [
            new InlineOptimzationExtension(),
        ];
    }

    protected function getFixturesDir()
    {
        return __DIR__.'/ast-fixtures/';
    }
}
