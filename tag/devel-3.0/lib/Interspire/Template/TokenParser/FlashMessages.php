<?php

class Interspire_Template_TokenParser_FlashMessages extends Twig_TokenParser
{
	public function getTag()
	{
		return 'flashMessages';
	}

	public function parse(Twig_Token $token)
	{
		$lineNo = $token->getLine();
		$this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
		return new Interspire_Template_Node_FlashMessages(array(), array(), $lineNo);
	}
}