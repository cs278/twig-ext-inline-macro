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

use Cs278\TwigInlineOptization\Util\FunctionUtil;

class InlineOptimzationExtension extends \Twig_Extension
{
    public function __construct()
    {
        // deterministic
        $this->coreExtension = new \Twig_Extension_Core;
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return 'inline_optimization';
    }

    /** {@inheritdoc} */
    public function initRuntime(\Twig_Environment $environment)
    {
    }

    /** {@inheritdoc} */
    public function getFilters()
    {
        $filters = [];

        foreach ($this->coreExtension->getFilters() as $filter) {
            if ($filter instanceof \Twig_SimpleFilter) {
                if ($filter->needsEnvironment() || $filter->needsContext()) {
                    // These cannot be deterministic.
                    continue;
                }

                if (FunctionUtil::isFunctionDeterministic($filter->getCallable())) {
                    $filter = TwigCallable\SimpleFilter::createFromFilter($filter, true);
                }
            }

            // We must replace all filters as the extension can declare multiple
            // ones that override each other, this replicates the overriding
            // behaviour. An example of this is the upper filter that usually
            // uses `strtoupper` but if mbstring is installed uses
            // `mb_strtoupper`.
            $filters[] = $filter;
        }

        return $filters;
    }

    /** {@inheritdoc} */
    public function getFunctions()
    {
        $functions = [];

        foreach ($this->coreExtension->getFunctions() as $function) {
            if ($function instanceof \Twig_SimpleFunction) {
                if ($function->needsEnvironment() || $function->needsContext()) {
                    // These cannot be deterministic.
                    continue;
                }

                $shouldInline = null;

                if ($function->getCallable() === 'range') {
                    $shouldInline = function (array $args) {
                        return count(call_user_func_array(
                            $this->getCallable(),
                            $args
                        )) < 10;
                    };
                }

                $function = TwigCallable\SimpleFunction::createFromFunction($function, true, $shouldInline);
            }

            $functions[] = $function;
        }

        return $functions;
    }

    /** {@inheritdoc} */
    public function getNodeVisitors()
    {
        return [
            new NodeVisitor\InlineDeterministicCallables(),
        ];
    }
}
