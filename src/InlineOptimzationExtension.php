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
        $coreFilters = $this->coreExtension->getFilters();
        $filters = [];

        foreach ($coreFilters as $filter) {
            if ($filter instanceof \Twig_SimpleFilter) {
                if ($filter->needsEnvironment() || $filter->needsContext()) {
                    // These cannot be deterministic.
                    continue;
                }

                if ($filter->getName() === 'trim') {
                    $overloadFilter = TwigCallable\SimpleFilter::createFromFilter($filter, true);
                }
            }

            if (isset($overloadFilter)) {
                $filters[] = $overloadFilter;
                $overloadFilter = null;
            }
        }

        return $filters;
    }

    /** {@inheritdoc} */
    public function getNodeVisitors()
    {
        return [
            new NodeVisitor\InlineDeterministicCallables(),
        ];
    }
}
