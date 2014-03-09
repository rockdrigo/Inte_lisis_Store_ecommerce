<?php
class Job_Controller
{
	private $controllerId;

	private function __construct($id, $addGroup=null)
	{
		$this->controllerId = $id;
		$this->keystore = Interspire_KeyStore_Mysql::instance();

		if($addGroup)
			$this->addToGroup($addGroup);
	}

	private function addToGroup($group)
	{
		if(!$group || $group === "")
			return;

		$controllers = (array)json_decode($this->keystore->get('jc_'.$group));
		$controllers[$this->getId()] = true;

		$this->keystore->set('jc_'.$group,isc_json_encode($controllers));
		$this->setValue('group', $group);
	}

	private function removeFromGroup()
	{
		$group = $this->getValue('group');

		$controllers = (array)json_decode($this->keystore->get('jc_'.$group));
		unset($controllers[$this->getId()]);

		$this->keystore->set('jc_'.$group,isc_json_encode($controllers));
		$this->clearValue('group');
	}

	static public function get($controllerId)
	{
		$controller = new Job_Controller($controllerId);

		return $controller;
	}

	static public function create($group)
	{
		$id = self::generateId($group);
		$controller = new Job_Controller($id, $group);
		$controller->setValue('running', true);
		$controller->setProgress(0,'');
		return $controller;
	}

	static private function generateId($prefix)
	{
		return uniqid($prefix.rand());
	}

	public function getId()
	{
		return $this->controllerId;
	}

	public function getProgress()
	{
		$running = $this->getValue('running');
		$progress = $this->getValue('progress');
		$message = $this->getValue('progress_message');

		return array('progress' => $progress,
				'message' => $message,
				'running' => $running);
	}

	public function setProgress($value, $message=null)
	{
		$this->setValue('progress', $value);

		if($message)
			$this->setValue('progress_message', $message);
	}

	public function getValue($name)
	{
		$key = $this->key($name);
		return $this->keystore->get($key);
	}

	public function setValue($name, $value)
	{
		$key = $this->key($name);
		return $this->keystore->set($key, $value);
	}

	public function clearValue($name)
	{
		$key = $this->key($name);
		return $this->keystore->delete($key);
	}

	public function destroy()
	{
		$this->removeFromGroup();
		$this->clearValue('progress');
		$this->clearValue('progress_message');
	}

	private function key($name)
	{
		return $this->controllerId.'_'.$name;
	}
}
