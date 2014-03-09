<?php
class Interspire_Template_TokenParser_Snippet extends Twig_TokenParser
{
	public function getTag()
	{
		return 'snippet';
	}

	public function parse(Twig_Token $token)
	{
		$lineNo = $token->getLine();
		$name = $this->parser->getStream()->expect(Twig_Token::NAME_TYPE)->getValue();
		$this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
		return new Interspire_Template_Node_Snippet($name, $lineNo);
	}
}