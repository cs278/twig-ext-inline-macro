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

    /** {@inheritdoc} */
    public function enterNode(\Twig_NodeInterface $node, \Twig_Environment $env)
    {
        if ($node instanceof \Twig_Node_Module) {
            // Record template macros that can be inlined.
            foreach ($node->getNode('macros') as $macroNode) {
                if ($macroNode instanceof InlineMacroNode) {
                    $this->macros[$macroNode->getAttribute('name')] = $macroNode;
                }
            }

            return $node;
        }

        // Replace usages of the macro where the macro is printed to screen
        // using the following syntax `{{ some.macro() }}`.
        if ($node instanceof \Twig_Node_Print) {
            $exprNode = $node->getNode('expr');

            if ($exprNode instanceof \Twig_Node_Expression_MethodCall) {
                // Twig prefixes the methods with 'get'.
                $macroName = substr($exprNode->getAttribute('method'), 3);

                if (isset($this->macros[$macroName])) {
                    $macroNode = $this->macros[$macroName];
                    $macroArguments = array_keys(iterator_to_array($macroNode->getNode('arguments')));
                    $macroBody = $this->cloneNode($macroNode->getNode('body'));

                    $argumentValues = $exprNode->getNode('arguments');

                    if ($argumentValues instanceof \Twig_Node_Expression_Array) {
                        $argumentValues = $this->nodeArrayToArray($argumentValues);
                    } else {
                        $argumentValues = array_slice(iterator_to_array($argumentValues), 1);
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

        foreach ($node as $name => $childNode) {
            $newNode->setNode($name, $this->cloneNode($childNode));
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
