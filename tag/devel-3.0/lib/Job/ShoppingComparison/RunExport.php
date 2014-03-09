<?php
class Job_ShoppingComparison_RunExport extends Job_Store_Abstract
{
	private $taskId;
	private $cache;
	private $exporter;
	private $controller;

	public $stepSize = 50;

	public function perform()
	{
        $_engine = getClass('ISC_ADMIN_ENGINE');
        $_engine->LoadLangFile('shoppingcomparison');

		$moduleId = $this->args['module'];
		GetModuleById('shoppingcomparison', $module, $moduleId);

		if(!$module)
			return;

		if(!isset($this->args['controller'])
			|| !($controllerId = $this->args['controller']))
		{
			error_log("No controller for export task. Aborting");
			return;
		}

		$this->initialize($module, $controllerId);
		$this->run();
	}

	protected function initialize($exporter, $controllerId)
	{
		$controller = Job_Controller::get($controllerId);

		$this->exporter = $exporter;
		$this->controller = $controller;
		$this->taskId   = $exporter->getId();
		$this->cache = Interspire_KeyStore_Mysql::instance();
	}

	public function transitions()
	{
		return array(
			'start' => array(
				array('on' => 'isNew', 'next' => 'newExport'),
				array('on' => 'isResume', 'next' => 'resumeExport')
			),
			'newExport'=> 'writeHead',
			'resumeExport' => 'writeRows',
			'writeHead' => 'writeRows',
			'writeRows' => array(
				array('on' => 'isAbort', 'next' => 'abort'),
				array('on' => 'isWait', 'next' => 'wait'),
				array('on' => 'isWriteSuccess', 'next' => 'writeFoot')
			),
			'writeFoot' => 'complete',
			'abort' => null,
			'wait' => null,
			'complete' => null);
	}

	public function run()
	{
		$state = new Interspire_StateExecutor($this);
		$state->execute($this->transitions());
	}

	public function newExport()
	{
		$file = $this->exporter->getExportFile();
		$this->output = fopen($file, 'w');
		$this->currentRow = 0;
		$this->totalRows = $this->exporter->getTotalRows();
	}

	public function resumeExport()
	{
		$file = $this->getValue('file');
		$this->output = fopen($file, 'a');
		$this->currentRow = $this->getValue('currentRow');
		$this->totalRows = $this->getValue('totalRows');
		$this->setValue('resume', false);
	}

	public function writeHead()
	{
		$head = $this->exporter->writeHead();
		fwrite($this->output, $head);
	}

	public function writeRows()
	{
		$currentrow = 0;
		$step = $this->stepSize;
		$total = $this->totalRows;

		while($currentrow < $total)
		{
			if(!($rows = $this->exporter->getExportRows($currentrow, $step))
			|| (mysql_num_rows($rows) <= 0))
				break;

			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($rows))
			{
				$buf = $this->exporter->writeRow($row);
				fwrite($this->output, $buf);

				$progress = $currentrow / (float)($total - 1);
				$progress = min(round($progress * 100, 0), 100);

				$this->controller->setProgress($progress, $this->exporter->exportProgressMessage($progress));
				$currentrow++;
			}

			mysql_free_result($rows);
		}
	}

	public function writeFoot()
	{
	}

	public function abort()
	{
	}

	public function wait()
	{
		$this->setValue('resume', true);
	}

	public function complete()
	{
		fclose($this->output);

		$this->exporter->exportEnd($this->controller);
		$this->controller->setProgress(100, $this->exporter->exportProgressMessage(100));
		$this->controller->setValue('running', false);
	}

	public function isNew()
	{
		return !$this->isResume();
	}

	public function isResume()
	{
		return $this->getValue('resume');
	}

	public function isAbort()
	{
		return false;
	}

	public function isWait()
	{
		return false;
	}

	public function isRunning()
	{
		$running = $this->getValue('running');
		return isset($running) && $running;
	}

	public function isWriteSuccess()
	{
		return true;
	}

	public function getValue($key)
	{
		$values = $this->getValues();

		if(isset($values[$key]))
			return $values[$key];

		return null;
	}

	public function setValue($key, $value)
	{
		$values = $this->getValues();
		$values[$key] = $value;

		$this->cache->set($this->taskId, $values);
	}

	private function clearValues()
	{
		$this->cache->delete($this->taskId);
	}

	private function getValues()
	{
		if($values = $this->cache->get($this->taskId))
			return $values;

		return array();
	}
}
