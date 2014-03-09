<?php

class Interspire_Template_Node_JSLang extends Twig_Node
{
	protected $name;

	protected $vars;

	public function __construct($name, $vars, $lineNo)
	{
		parent::__construct(array(), array(), $lineNo);

		$this->name = $name;
		$this->vars = $vars;
	}

	public function compile($compiler)
	{
		$compiler
			->addDebugInfo($this)
			->write('echo Interspire_Template_Extension::jsFilter(getLang(')
			->subcompile($this->name)
		;

		if (!empty($this->vars)) {
			$compiler->raw(', ');
			$compiler->subcompile($this->vars);
		}

		$compiler->raw("), \"'\");\n");
	}
}