<?php
class Interspire_Template extends Twig_Environment
{
	static private $instances = array();

	private $assignedVars = array();

	public function __construct($templatePaths, array $options = array())
	{
		if(!is_array($templatePaths)) {
			$templatePaths = array($templatePaths);
		}

		$options['debug'] = GetConfig('DebugMode');

		$loader = new Twig_Loader_Filesystem($templatePaths);
		parent::__construct($loader, $options);
		$this->addExtension(new Interspire_Template_Extension());
		$this->addExtension(new Twig_Extension_Escaper());
	}

	/**
	* Get a named instance of the template system (e.g. 'admin')
	*
	* @param string $instance
	* @param array|string $templatePaths
	* @param array $options
	* @return Interspire_Template
	*/
	public static function getInstance($instance, $templatePaths = null, $options = array())
	{
		if(empty(self::$instances[$instance])) {
			self::$instances[$instance] = new self($templatePaths, $options);
		}
		return self::$instances[$instance];
	}

	public function assign($name, $value)
	{
		$this->assignedVars[$name] = $value;
	}

	public function getAssignedVars()
	{
		return $this->assignedVars;
	}

	public function render($template, $context=array())
	{
		$template = $this->loadTemplate($template);
		return $template->render($this->assignedVars + $context + $GLOBALS);
	}

	public function display($template, $context=array())
	{
		$template = $this->loadTemplate($template);
		$template->display($this->assignedVars + $context + $GLOBALS);
	}
}
