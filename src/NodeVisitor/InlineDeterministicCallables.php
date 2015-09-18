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
        $twigCallable = $twigArguments = null;

        if ($node instanceof \Twig_Node_Expression_Filter) {
            $twigCallable = $env->getFilter($node->getNode('filter')->getAttribute('value'));
            $twigArguments = iterator_to_array($node->getNode('arguments'));

            array_unshift($twigArguments, $node->getNode('node'));
        }

        if ($node instanceof \Twig_Node_Expression_Function) {
            $twigCallable = $env->getFunction($node->getAttribute('name'));
            $twigArguments = iterator_to_array($node->getNode('arguments'));
        }

        if ($this->canInlineCallable($twigCallable, $twigArguments)) {
            return $this->inlineCallable(
                $twigCallable,
                $twigArguments,
                $node
            );
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

    /**
     * Perform the dark arts of trying to inline a function/filter.
     *
     * @param DeterministicInterface $twigCallable
     * @param array                  $arguments    Arguments to be passed to callable.
     * @param \Twig_Node             $node         Twig node to be converted.
     *
     * @return \Twig_Node
     */
    private function inlineCallable(DeterministicInterface $twigCallable, array $arguments, \Twig_Node $node)
    {
        $arguments = $this->normalizeArguments($arguments);

        if (!$twigCallable->shouldInline($arguments)) {
            // Advised against inlining this, bailout.
            return $node;
        }

        return NodeUtil::createConstantExpression(
            call_user_func_array(
                $twigCallable->getCallable(),
                $arguments
            ),
            $node->getLine()
        );
    }

    /**
     * Is it possible to inline this callable.
     *
     * @param DeterministicInterface|null $twigCallable
     * @param array|null                  $twigArguments
     *
     * @return bool
     */
    private function canInlineCallable($twigCallable = null, array $twigArguments = null)
    {
        if (null === $twigCallable || null === $twigArguments) {
            return false;
        }

        if (!$twigCallable instanceof DeterministicInterface) {
            return false;
        }

        if (!$twigCallable->isDeterministic()) {
            return false;
        }

        foreach ($twigArguments as $node) {
            if (!NodeUtil::isConstantExpression($node)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Prepare arguments into a form for PHP callables.
     *
     * @param array $arguments
     *
     * @return array
     */
    private function normalizeArguments(array $arguments)
    {
        $arguments = array_map(function ($value) {
            if ($value instanceof \Twig_Node) {
                if (NodeUtil::isEmpty($value)) {
                    return null;
                }

                return NodeUtil::getConstantExpressionValue($value);
            } else {
                return $value;
            }
        }, $arguments);

        // Discard nulls at the end of the arguments.
        // This makes a difference as it replaces the default value of arguments
        // as an example `trim(' foo ', null) === ' foo '`.
        while (null === end($arguments)) {
            array_pop($arguments);
        }

        return $arguments;
    }
}
