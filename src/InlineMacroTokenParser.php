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

        if ($inlineMacro) {
            $this->parser->setMacro($name, new InlineMacroNode($name, new \Twig_Node_Body([$body]), $arguments, $lineno, $this->getTag()));
        } else {
            $this->parser->setMacro($name, new \Twig_Node_Macro($name, new \Twig_Node_Body([$body]), $arguments, $lineno, $this->getTag()));
        }
    }
}
