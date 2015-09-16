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
class MacroInliner implements \Twig_NodeVisitorInterface
{
    private $currentFileName;

    private $macros = [];
    private $imports = [];

    public function __construct(MacroImportCollector $collector)
    {
        $this->collector = $collector;
    }

    /** {@inheritdoc} */
    public function enterNode(\Twig_NodeInterface $node, \Twig_Environment $env)
    {
        if ($node instanceof \Twig_Node_Module) {
            $mapping = $this->collector->getMappingFor($node->getAttribute('filename'));

            var_dump($mapping); die;
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

        foreach ($this->macros[$fileName] as $node) {
            $map[sprintf('_self %s', $node->getAttribute('name'))] = $node;
        }

        return $map;
    }

    /** {@inheritdoc} */
    public function getPriority()
    {
        return -1;
    }
}
