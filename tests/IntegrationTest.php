<?php

/*
 * This file is part of the Twig Inline Macro Extension package.
 *
 * (c) Chris Smith <chris@cs278.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cs278\TwigInlineMacro;

class IntegrationTest extends \Twig_Test_IntegrationTestCase
{
    public function getExtensions()
    {
        return [
            new InlineMacroExtension(InlineMacroTokenParser::MODE_STRICT),
        ];
    }

    public function getTwigFunctions()
    {
        return [
            // Dirty function to detect if it's called from inside a macro or not.
            new \Twig_SimpleFunction('isInlined', function() {
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
                $frame = array_pop($trace);

                return !empty($frame)
                    && isset($frame['class'])
                    && isset($frame['function'])
                    && strpos($frame['class'], '__TwigTemplate_') === 0
                    && $frame['function'] === 'doDisplay';
            }),
        ];
    }

    public function getFixturesDir()
    {
        return __DIR__.'/fixtures/';
    }
}
