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

class IntegrationTest extends \Twig_Test_IntegrationTestCase
{
    protected function getExtensions()
    {
        return [
            new InlineOptimzationExtension(),
        ];
    }

    protected function getFixturesDir()
    {
        return __DIR__.'/fixtures/';
    }
}
