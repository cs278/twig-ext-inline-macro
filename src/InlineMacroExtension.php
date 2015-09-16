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

class InlineMacroExtension extends \Twig_Extension
{
    private $parserMode;

    public function __construct($mode)
    {
        $this->parserMode = (int) $mode;
    }

    public function getName()
    {
        return 'inline_macro';
    }

    public function getTokenParsers()
    {
        return [
            new InlineMacroTokenParser($this->parserMode),
        ];
    }

    public function getNodeVisitors()
    {
        return [
            $macroCollector = new NodeVisitor\MacroImportCollector(),
            new NodeVisitor\MacroInliner($macroCollector),
        ];
    }
}
