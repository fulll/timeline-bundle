<?php

namespace Spy\TimelineBundle\Twig\TokenParser;

use Spy\TimelineBundle\Twig\Node\TimelineActionThemeNode;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Provides 'timeline_action_theme' tag
 */
class TimelineActionThemeTokenParser extends AbstractTokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @param Token $token A Token instance
     *
     * @return Node A Node instance
     */
    public function parse(Token $token)
    {
        $stream = $this->parser->getStream();

        $action = $this->parser->getExpressionParser()->parseExpression();

        $resources = [];
        do {
            $resources[] = $this->parser->getExpressionParser()->parseExpression();
        } while (!$stream->test(Token::BLOCK_END_TYPE));

        $stream->expect(Token::BLOCK_END_TYPE);

        return new TimelineActionThemeNode(
            $action,
            new Node($resources),
            [],
            $token->getLine(),
            $this->getTag()
        );
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'timeline_action_theme';
    }
}
