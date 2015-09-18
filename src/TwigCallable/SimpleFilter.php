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

class SimpleFilter extends \Twig_SimpleFilter implements DeterministicInterface
{
    public static function createFromFilter(\Twig_SimpleFilter $filter, $isDeterministic)
    {
        return new static(
            $filter->name,
            $filter->callable,
            ['deterministic' => $isDeterministic] + $filter->options
        );
    }

    public function isDeterministic()
    {
        return isset($this->options['deterministic'])
            && $this->options['deterministic'] === true;
    }

    public function shouldInline(array $arguments)
    {
        return true;
    }
}
