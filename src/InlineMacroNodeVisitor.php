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



        // if ($node instanceof InlineMacroNode) {
        //     // Beginning of an inline macro, test what it does.
        //     array_unshift($this->macroParsing, $node);
        //
        //     return $node;
        // }
// fputs(STDERR, $node);

        // Replace usages of the macro where the macro is printed to screen
        // using the following syntax `{{ some.macro() }}`.
        if ($node instanceof \Twig_Node_Print) {
            $exprNode = $node->getNode('expr');

            // helpers.macro()
            //
            // Twig_Node_Print(
            //   expr: Twig_Node_Expression_MethodCall(method: 'getself', safe: true
            //           node: Twig_Node_Expression_Name(name: 'me', is_defined_test: false, ignore_strict_check: false, always_defined: true)
            //           arguments: Twig_Node_Expression_Array()
            //         )
            // )
            if ($exprNode instanceof \Twig_Node_Expression_MethodCall) {
                // Twig prefixes the methods with 'get'.
                $macroName = substr($exprNode->getAttribute('method'), 3);

                if (null !== $macroBody = $this->inlineMacro($macroName, $exprNode->getNode('arguments'))) {
                    return $macroBody;
                }
            }

            // _self.macro()
            //
            // Twig_Node_Print(
            //   expr: Twig_Node_Expression_GetAttr(type: 'method', is_defined_test: false, ignore_strict_check: false, disable_c_ext: false
            //           node: Twig_Node_Expression_Name(name: '_self', is_defined_test: false, ignore_strict_check: false, always_defined: false)
            //           attribute: Twig_Node_Expression_Constant(value: 'self')
            //           arguments: Twig_Node_Expression_Array()
            //         )
            // )
            if ($exprNode instanceof \Twig_Node_Expression_GetAttr && $exprNode->getAttribute('type') === 'method') {
                $objNode = $exprNode->getNode('node');
                $methodNode = $exprNode->getNode('attribute');

                if ($objNode->getAttribute('name') === '_self') {
                    $macroName = $methodNode->getAttribute('value');

                    if (null !== $macroBody = $this->inlineMacro($macroName, $exprNode->getNode('arguments'))) {
// fputs(STDERR, "$macroBody\n");die;

                        return $macroBody;
                    }
                }
            }
        }

        if ($this->insertedStack) {
            $arguments = $this->insertedStack[0]['args'];

            if ($node instanceof \Twig_Node_Expression_Name) {
                if (isset($arguments[$node->getAttribute('name')])) {
                    return $arguments[$node->getAttribute('name')];
                }
            }
        }

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
        return 0;
    }

    private function inlineMacro($macroName, \Twig_Node $argumentsNode)
    {
        if (!isset($this->macros[$macroName])) {
            return;
        }

        $macroNode = $this->macros[$macroName];
        $macroArguments = array_keys(iterator_to_array($macroNode->getNode('arguments')));
        $macroBody = $this->cloneNode($macroNode->getNode('body'));

        if ($argumentsNode instanceof \Twig_Node_Expression_Array) {
            $argumentValues = $this->nodeArrayToArray($argumentsNode);
        } else {
            $argumentValues = array_slice(iterator_to_array($argumentsNode), 1);
        }

        $macroArguments = array_combine(
            $macroArguments,
            $argumentValues
        );

        array_unshift($this->insertedStack, [
            'body' => $macroBody,
            'args' => $macroArguments,
        ]);

        return $macroBody;
    }

    /**
     * Deep clone a Node.
     *
     * \Twig_Node does not have a __clone() method defined to sort this out.
     *
     * @return \Twig_Node
     */
    private function cloneNode(\Twig_Node $node)
    {
        $newNode = clone $node;

        foreach ($newNode as $name => $childNode) {
            $newNode->setNode(
                $name,
                null === $childNode ? null : $this->cloneNode($childNode)
            );
        }

        // Must go deeper...
        if ($newNode instanceof \Twig_Node_For) {
            $forLoopNode = $newNode->getNode('body')->getNode(1);

            if (!$forLoopNode instanceof \Twig_Node_ForLoop) {
                throw new \Twig_Error('Did not find expected node.');
            }

            $loopProp = new \ReflectionProperty($newNode, 'loop');
            $loopProp->setAccessible(true);
            $loopProp->setValue($newNode, $forLoopNode);
        }

        return $newNode;
    }

    private function nodeArrayToArray(\Twig_Node_Expression_Array $node)
    {
        $contents = [];

        foreach ($node as $i => $value) {
            if ($i & 1) {
                $contents[] = $value;
            }
        }

        return $contents;
    }
}
