<?php
class Interspire_Template_TokenParser_Panel extends Twig_TokenParser
{
	public function getTag()
	{
		return 'panel';
	}

	public function parse(Twig_Token $token)
	{
		$lineNo = $token->getLine();
		$name = $this->parser->getStream()->expect(Twig_Token::NAME_TYPE)->getValue();

		$imitates = $name;
		if($this->parser->getStream()->test(Twig_Token::NAME_TYPE, 'imitates')) {
			$this->parser->getStream()->next();
			$imitates = $this->parser->getStream()->expect(Twig_Token::NAME_TYPE)->getValue();
		}

		$settings = array();
		if($this->parser->getStream()->test(Twig_Token::NAME_TYPE, 'with')) {
	      $this->parser->getStream()->next();
	      $settings = $this->parser->getExpressionParser()->parseExpression();
	    }

		$this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
		return new Interspire_Template_Node_Panel($name, $imitates, $settings, $lineNo);
	}
}