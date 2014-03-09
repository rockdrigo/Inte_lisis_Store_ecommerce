<?php

class Interspire_Template_Node_FlashMessages extends Twig_Node
{
	public function compile($compiler)
	{
		$compiler
			->addDebugInfo($this)
			->write('echo getFlashMessageBoxes();');
	}
}