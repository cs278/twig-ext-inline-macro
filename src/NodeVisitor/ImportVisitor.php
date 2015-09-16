<?php

/*
 * This file is part of the Twig Inline Macro Extension package.
 *
 * (c) Chris Smith <chris@cs278.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cs278\TwigInlineMacro\NodeVisitor;

/**
 * Macro inliner node visitor.
 *
 * This extracts macro bodies from their definitions and when a call to that
 * macro is found in the AST this replaces the call with the macro body.
 */
class InlineMacroNodeVisitor implements \Twig_NodeVisitorInterface
{
    private $macros = [];
    private $insertedStack = [];

    private $macroParsing = [];

    /** {@inheritdoc} */
    public function enterNode(\Twig_NodeInterface $node, \Twig_Environment $env)
    {
        if ($node instanceof \Twig_Node_Module) {
            // Record template macros that can be inlined.
            foreach ($node->getNode('macros') as $macroNode) {
                if ($macroNode->getAttribute('inline')) {
                    $this->macros[$macroNode->getAttribute('name')] = $macroNode;
                }
            }
            fputs(STDERR, "$node\n");

            return $node;
        }

        // TODO
        //
        // Parse import/from statements:
        //
        // {% import _self as me %}
        // Twig_Node_Import(
        //     expr: Twig_Node_Expression_Name(name: '_self', is_defined_test: false, ignore_strict_check: false, always_defined: false)
        //     var: Twig_Node_Expression_AssignName(name: 'me', is_defined_test: false, ignore_strict_check: false, always_defined: false)
        // )
        //
        // {% import 'macros.twig' as helpers %}
        // Twig_Node_Import(
        //     expr: Twig_Node_Expression_Constant(value: 'macros.twig')
        //     var: Twig_Node_Expression_AssignName(name: 'helpers', is_defined_test: false, ignore_strict_check: false, always_defined: false)
        // )
        //
        // {% from 'macros.twig' import imported as aliased %}
        // Twig_Node_Import(
        //     expr: Twig_Node_Expression_Constant(value: 'macros.twig')
        //     var: Twig_Node_Expression_AssignName(name: '__internal_e2cec78610cd371ce8d380dc4fb3c52cc8fcdd94a6b06a09e0fc2001e583a63a', is_defined_test: false, ignore_strict_check: false, always_defined: true)
        // )


        return $node;
    }

    /** {@inheritdoc} */
    public function leaveNode(\Twig_NodeInterface $node, \Twig_Environment $env)
    {
        if ($this->insertedStack && $this->insertedStack[0]['body'] === $node) {
            array_shift($this->insertedStack);
        }

        return $node;
    }

    /** {@inheritdoc} */
    public function getPriority()
    {
        return -1;
    }
}
