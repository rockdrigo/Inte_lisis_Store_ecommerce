<?php
class Interspire_Template_Node_Snippet extends Twig_Node
{
	protected $name;

	public function __construct($name, $lineNo)
	{
		parent::__construct(array(), array(), $lineNo);
	}

	public function compile($compiler)
	{
		$snippetVar = '$GLOBALS[\'SNIPPETS\'][\''.$this->name.'\']';
		$compiler
			->addDebugInfo($this)
			->write('if(!empty('.$snippetVar.')) {'."\n")
			->indent()
				->write('echo '.$snippetVar.';'."\n")
				->outdent()
			->write('}'."\n")
		;
	}
}