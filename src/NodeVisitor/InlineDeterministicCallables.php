<?php

/*
 * This file is part of the Twig Inline Optimization Extension package.
 *
 * (c) Chris Smith <chris@cs278.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cs278\TwigInlineOptization\NodeVisitor;

use Cs278\TwigInlineOptization\TwigCallable\DeterministicInterface;
use Cs278\TwigInlineOptization\Util\NodeUtil;

class InlineDeterministicCallables extends \Twig_BaseNodeVisitor
{
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function doEnterNode(\Twig_Node $node, \Twig_Environment $env)
    {
        if ($node instanceof \Twig_Node_Expression_Filter) {
            $filter = $env->getFilter($node->getNode('filter')->getAttribute('value'));
            $inputNode = $node->getNode('node');
            $argumentsNode = $node->getNode('arguments');

            if ($this->canInlineFilter($node, $env)) {
                $arguments = [];
                $arguments[] = NodeUtil::getConstantExpressionValue($inputNode);

                if (!NodeUtil::isEmpty($argumentsNode)) {
                    $arguments = array_merge($arguments, NodeUtil::getConstantExpressionValue($argumentsNode));
                }

                $filterResult = call_user_func_array(
                    $filter->getCallable(),
                    $arguments
                );

                return NodeUtil::createConstantExpression($filterResult, $node->getLine());
            }
        }

        return $node;
    }

    /**
     * {@inheritdoc}
     */
    protected function doLeaveNode(\Twig_Node $node, \Twig_Environment $env)
    {
        return $node;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 0;
    }

    private function canInlineFilter(\Twig_Node_Expression_Filter $node, \Twig_Environment $env)
    {
        $filter = $env->getFilter($node->getNode('filter')->getAttribute('value'));

        if (!$filter instanceof DeterministicInterface) {
            return false;
        }

        if (!$filter->isDeterministic()) {
            return false;
        }

        if (!NodeUtil::isConstantExpression($node->getNode('node'))) {
            return false;
        }

        if (!NodeUtil::isConstantExpression($node->getNode('arguments'))) {
            return false;
        }

        return true;
    }
}
