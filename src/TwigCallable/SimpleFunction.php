<?php

/*
 * This file is part of the Twig Inline Optimization Extension package.
 *
 * (c) Chris Smith <chris@cs278.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cs278\TwigInlineOptization\TwigCallable;

class SimpleFunction extends \Twig_SimpleFunction implements DeterministicInterface
{
    public static function createFromFunction(\Twig_SimpleFunction $function, $isDeterministic, \Closure $inlineTest = null)
    {
        return new static(
            $function->name,
            $function->callable,
            ['deterministic' => $isDeterministic, 'inline_test' => $inlineTest] + $function->options
        );
    }

    public function isDeterministic()
    {
        return isset($this->options['deterministic'])
            && $this->options['deterministic'] === true;
    }

    public function shouldInline(array $arguments)
    {
        if (!isset($this->options['inline_test'])) {
            return true;
        }

        $callback = $this->options['inline_test']->bindTo($this);

        return (bool) call_user_func($callback, $arguments);
    }
}
