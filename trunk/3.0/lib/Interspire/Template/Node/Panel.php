<?php
class Interspire_Template_Node_Panel extends Twig_Node
{
	protected $name;
	protected $imitates;
	protected $settings;

	public function __construct($name, $imitates, $settings, $lineNo)
	{
		parent::__construct(array(), array(), $lineNo);
		$this->name = $name;
		$this->imitates = $imitates;
		$this->settings = $settings;
	}

	public function compile($compiler)
	{
		$logicClass = 'ISC_'.strtoupper($this->imitates).'_PANEL';
		$logicFile = 'includes/display/'.$this->imitates.'.php';
		$panelFile = 'Panels/'.$this->name.'.html';

		$compiler
			->addDebugInfo($this)
			->write('$hidePanel = false;'."\n")
			->write('if(file_exists(ISC_BASE_PATH.\'/'.$logicFile.'\')) {'."\n")
			->indent()
				->write('require_once ISC_BASE_PATH.\'/'.$logicFile.'\';'."\n")
				->outdent()
			->write("}\n")
			->write('if(class_exists(\''.$logicClass.'\')) {'."\n")
			->indent()
				->write('$panel = new '.$logicClass.'('."\n")
		;

		if(!empty($this->settings)) {
			$compiler
				->indent()
					->subcompile($this->settings)
					->outdent()
			;
		}

		$compiler
				->write(');'."\n")
				->write('$panel->setPanelSettings();'."\n")
				->write('$hidePanel = $panel->getDontDisplay();'."\n")
				->outdent()
			->write('}'."\n")
			->write('if($hidePanel == false) {'."\n")
			->indent()
				->write('$this->getEnvironment()->display(\''.$panelFile.'\');'."\n")
				->outdent()
			->write("}\n")
		;
	}
}