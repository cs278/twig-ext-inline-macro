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
 * This collects macros and import definitions.
 */
class MacroImportCollector implements \Twig_NodeVisitorInterface
{
    private $currentFileName;

    private $macros = [];
    private $imports = [];

    /** {@inheritdoc} */
    public function enterNode(\Twig_NodeInterface $node, \Twig_Environment $env)
    {
        if ($node instanceof \Twig_Node_Module) {
            $this->currentFileName = $node->getAttribute('filename');
var_dump($this->currentFileName);
            // Record template macros that can be inlined.
            foreach ($node->getNode('macros') as $macroNode) {
                $macroName = $macroNode->getAttribute('name');

                if ($macroNode->getAttribute('inline')) {
                    if (!isset($this->macros[$this->currentFileName])) {
                        $this->macros[$this->currentFileName] = [];
                    }

                    $this->macros[$this->currentFileName][$macroName] = $macroNode;
                }
            }

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

        if ($node instanceof \Twig_Node_Import && null !== $this->currentFileName) {
            if (!isset($this->imports[$this->currentFileName])) {
                $this->imports[$this->currentFileName] = [];
            }

            $this->imports[$this->currentFileName][] = $node;
        }

        return $node;
    }

    /** {@inheritdoc} */
    public function leaveNode(\Twig_NodeInterface $node, \Twig_Environment $env)
    {
        if ($node instanceof \Twig_Node_Module) {
            $this->currentFileName = null;
        }

        return $node;
    }

    public function getMappingFor($fileName)
    {
        $map = [];

        // Macros created in the same template are available as `_self.macroName`.
        $map = $this->mapLocalMacros($fileName, '_self') + $map;

        foreach ($this->imports[$fileName] as $importNode) {
            $exprNode = $importNode->getNode('expr');
            $varNode = $importNode->getNode('var');
            $varName = $varNode->getAttribute('name');

            if ($exprNode instanceof \Twig_Node_Expression_Name && '_self' === $exprNode->getAttribute('name')) {
                // {% import _self as me %}
                $map = $this->mapLocalMacros($fileName, $varName) + $map;
            } elseif ($exprNode instanceof \Twig_Node_Expression_Constant) {
                // {% import 'macros.twig' as helpers %}
                // {% from 'macros.twig' import imported as aliased %}
                $macrosFileName = $exprNode->getAttribute('value');

                $map = $this->mapLocalMacros($macrosFileName, $varName) + $map;
            }
        }

        return $map;
    }

    private function mapLocalMacros($fileName, $variable)
    {
        $map = [];

        if (empty($this->macros[$fileName])) {
            // return [];
        }

        foreach ($this->macros[$fileName] as $macroNode) {
            $ident = sprintf(
                '%s %s',
                $variable,
                $macroNode->getAttribute('name')
            );

            $map[$ident] = $macroNode;
        }

        return $map;
    }

    /** {@inheritdoc} */
    public function getPriority()
    {
        return -2;
    }
}
