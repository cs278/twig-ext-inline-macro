<?php

/*
 * This file is part of the Twig Inline Optimization Extension package.
 *
 * (c) Chris Smith <chris@cs278.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cs278\TwigInlineOptization\Util;

final class NodeUtil
{
    /**
     * Turn a PHP value into Twig Nodes representing the value.
     *
     * @param mixed $value
     * @param int $line
     *
     * @return \Twig_Node_Expression_Constant
     */
    public static function createConstantExpression($value, $line)
    {
        if (null === $value || is_array($value) || is_scalar($value)) {
            return new \Twig_Node_Expression_Constant($value, $line);
        }

        throw new \LogicException(sprintf(
            'Value of type `%s` is not a constant',
            is_object($value) ? get_class($value) : gettype($value)
        ));
    }

    /**
     * Determine if the supplied nodes represent a constant value.
     *
     * @param \Twig_Node $node
     *
     * @return bool
     */
    public static function isConstantExpression(\Twig_Node $node)
    {
        if ($node instanceof \Twig_Node_Expression_Constant) {
            return true;
        }

        if ($node instanceof \Twig_Node_Expression_Array) {
            foreach ($node->getKeyValuePairs() as $item) {
                if (!self::isConstantExpression($item['key'])) {
                    return false;
                }

                if (!self::isConstantExpression($item['value'])) {
                    return false;
                }
            }

            return true;
        }

        // if ($node instanceof \Twig_Node_Expression_Binary_Range) {
        //     if (!self::isConstantExpression($node->getNode('left'))) {
        //         return false;
        //     }
        //
        //     if (!self::isConstantExpression($node->getNode('right'))) {
        //         return false;
        //     }
        //
        //     return true;
        // }

        if (self::isEmpty($node)) {
            return true;
        }

        // if ('Twig_Node' === get_class($node)) {
        //     foreach ($node as $k => $subNode) {
        //         if (!is_int($k)) {
        //             return false;
        //         }
        //
        //         if (!self::isConstantExpression($subNode)) {
        //             return false;
        //         }
        //     }
        //
        //     return true;
        // }

        return false;
    }

    /**
     * Extract the value of a constant node.
     *
     * @param \Twig_Node $node
     *
     * @return mixed
     */
    public static function getConstantExpressionValue(\Twig_Node $node)
    {
        if (!self::isConstantExpression($node)) {
            throw new \InvalidArgumentException;
        }

        if (self::isEmpty($node)) {
            return;
        }

        if ($node instanceof \Twig_Node_Expression_Constant) {
            return $node->getAttribute('value');
        }

        if ($node instanceof \Twig_Node_Expression_Array) {
            $result = [];

            foreach ($node->getKeyValuePairs() as $item) {
                $key = self::getConstantExpressionValue($item['key']);
                $value = self::getConstantExpressionValue($item['value']);

                $result[$key] = $value;
            }

            return $result;
        }

        throw new \LogicException(sprintf("Cannot extract constant value from:\n\n%s", $node));
    }

    /**
     * Test if the node is empty.
     *
     * @param \Twig_Node $node
     *
     * @return bool
     */
    public static function isEmpty(\Twig_Node $node)
    {
        if ('Twig_Node' !== get_class($node)) {
            return false;
        }

        if (0 < $node->count()) {
            return false;
        }

        if (null !== $node->getNodeTag()) {
            return false;
        }

        return true;
    }
}
