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
 * Parse the inline macro/regular macro syntax.
 *
 * {% macro foo(x, y) %}{% endmacro %}
 *
 * and
 *
 * {% macro foo(x, y) inline %}{% endmacro %}
 *
 * @todo Sort out copyright/license most of this is copied from Twig itself.
 */
class InlineMacroTokenParser extends \Twig_TokenParser_Macro
{
    const MODE_STRICT = 1;
    const MODE_AUTO = 2;

    /** @var boolean $autoInline Automatically inline any macros that can be. */
    private $autoInline = false;

    /** @var boolean $strictInline Ignore inline flag if macro cannot be inlined. */
    private $strictInline = false;

    public function __construct($mode)
    {
        $this->autoInline = ($mode & self::MODE_AUTO) === self::MODE_AUTO;
        $this->strictInline = ($mode & self::MODE_STRICT) === self::MODE_STRICT;
    }

    /** {@inheritdoc} */
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $name = $stream->expect(\Twig_Token::NAME_TYPE)->getValue();

        $arguments = $this->parser->getExpressionParser()->parseArguments(true, true);

        $inlineMacro = null !== $stream->nextIf(\Twig_Token::NAME_TYPE, 'inline');

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);
        $this->parser->pushLocalScope();

        $body = $this->parser->subparse([$this, 'decideBlockEnd'], true);
        if ($token = $stream->nextIf(\Twig_Token::NAME_TYPE)) {
            $value = $token->getValue();

            if ($value != $name) {
                throw new \Twig_Error_Syntax(sprintf('Expected endmacro for macro "%s" (but "%s" given)', $name, $value), $stream->getCurrent()->getLine(), $stream->getFilename());
            }
        }
        $this->parser->popLocalScope();
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        if ($inlineMacro && $this->strictInline) {
            // Macro is declared inline, verify it can be run inline.
            // fputs(STDERR, "$body\n");
            $this->checkBody($body);
        } elseif ($inlineMacro && !$this->strictInline) {
            // Macro is declared inline, downgrade it if it cannot be inlined.
            if (!$this->checkBody($body, false)) {
                $inlineMacro = false;
            }
        } elseif (!$inlineMacro && $this->autoInline) {
            // Macro is not inline attempt to upgrade it.
            $inlineMacro = $this->checkBody($body, false);
        }

        // fputs(STDERR, "$body\n");

        $macroNode = new \Twig_Node_Macro($name, new \Twig_Node_Body([$body]), $arguments, $lineno, $this->getTag());
        $macroNode->setAttribute('inline', $inlineMacro);
        // fputs(STDERR, "$macroNode\n");

        $this->parser->setMacro($name, $macroNode);
    }

    private function checkBody(\Twig_Node $node, $throwException = true)
    {
        if (!$throwException) {
            // Okay so yes, this is really lazy.
            try {
                $this->checkBody($node);

                return true;
            } catch (\Twig_Error_Syntax $e) {
                return false;
            }
        }

        if (!$this->isNodeAllowed($node)) {
            switch (true) {
                case $node instanceof \Twig_Node_Set:
                    $nodeType = '"set" tag';
                    break;

                case $node instanceof \Twig_Node_BlockReference:
                    $nodeType = '"block" tag';
                    break;

                case $node instanceof \Twig_Node_Embed:
                    $nodeType = '"embed" tag';
                    break;

                case $node instanceof \Twig_Node_Include:
                    $nodeType = '"include" tag without the "only" attribute';
                    break;

                case $node instanceof \Twig_Node_Expression_BlockReference:
                    $nodeType = '"block()" function or the "filter" tag';
                    break;

                default:
                    $nodeType = sprintf('"%s"', get_class($node));
            }

            throw new \Twig_Error_Syntax(
                sprintf(
                    'You cannot use the %s inside an inline macro',
                    $nodeType
                ),
                $node->getLine(),
                $this->parser->getFilename()
            );
        }

        foreach ($node as $childNode) {
            if ($childNode === null) {
                continue;
            }

            $this->checkBody($childNode);
        }
    }

    private function isNodeAllowed(\Twig_Node $node)
    {
        $nodeClass = get_class($node);

        // Check against the classname exactly, as all other nodes extend this.
        if ($nodeClass === 'Twig_Node') {
            return true;
        }

        switch (true) {
            // Blacklist some expressions.
            case $node instanceof \Twig_Node_Expression_BlockReference: // (used by filter tag)
                return false;

            case $node instanceof \Twig_Node_Text:
            case $node instanceof \Twig_Node_Print:
            case $node instanceof \Twig_Node_Expression:

            // Tags:
            case $node instanceof \Twig_Node_AutoEscape:
            case $node instanceof \Twig_Node_Do:
            case $node instanceof \Twig_Node_Flush:
            case $node instanceof \Twig_Node_For:
            case $node instanceof \Twig_Node_ForLoop:
            // @todo from tag, is this safe?
            case $node instanceof \Twig_Node_If:
            // @todo import tag, see from
            // Include is handled below.
            // @todo Sandbox, test disabled.
            case $node instanceof \Twig_Node_Spaceless:
                return true;
        }

        if ($node instanceof \Twig_Node_Include) {
            if (!$node->getAttribute('only')) {
                return false;
            }

            return true;
        }

        return false;
    }
}
