<?php

/**
* This class represents a rule for routing a subscriber to an email provider list. It exists primarily to standardise the structure for user-interface js and the databse.
*/
abstract class Interspire_EmailIntegration_Rule
{
	const ACTION_ADD = 1;
	const ACTION_REMOVE = 2;

	/**
	* Returns a rule class name for the given event id
	*
	* @param string $eventId
	* @return string Interspire_EmailIntegration_Rule_* class name, or false for invalid events
	*/
	public static function getClassNameForEventId($eventId)
	{
		// @todo should refactor event ids so they map exactly to Interspire_EmailIntegration_Rule_{event} class names, which would properly change event "ids" from being arbitrary strings to class name references
		$eventClassMap = array(
			'onNewsletterSubscribed' => 'Interspire_EmailIntegration_Rule_NewsletterSubscribed',
			'onOrderCompleted' => 'Interspire_EmailIntegration_Rule_OrderCompleted',
		);

		if (!isset($eventClassMap[$eventId])) {
			return false;
		}

		return $eventClassMap[$eventId];
	}

	/**
	* Delete all configured email integration rules (except built-in rules)
	*
	* @return bool
	*/
	public static function deleteAllRules()
	{
		ISC_EMAILINTEGRATION::flushRules();
		return $GLOBALS['ISC_CLASS_DB']->Query('TRUNCATE TABLE `[|PREFIX|]email_rules`');
	}

	public $eventId;

	public $id;
	public $moduleId;
	public $action;
	public $listId;
	public $fieldMap;
	public $eventCriteria;

	/**
	* This function, to be implemented by an actual rule class, must return true or false for the given subscription data based on its own event criteria data
	*
	* @return bool
	*/
	abstract public function qualifySubscription(Interspire_EmailIntegration_Subscription $subscription);

	/**
	* @param int $id email_rules.id for editing, or null to create a new rule
	* @param string $moduleId
	* @param int $action One of Interspire_EmailIntegration_Rule::ACTION_*
	* @param mixed $listId List id as provider list id
	* @param array $fieldMap array of provider field id => subscription field id
	* @param array $eventCriteria extended information specific to certain events
	* @return Interspire_EmailIntegration_Rule
	*/
	public function __construct($id, $moduleId, $action, $listId, $fieldMap = array(), $eventCriteria = array())
	{
		$this->id = (int)$id;
		$this->moduleId = $moduleId;
		$this->action = (int)$action;
		$this->listId = $listId;

		$this->fieldMap = $fieldMap;
		$this->eventCriteria = $eventCriteria;
	}

	/**
	* Returns a javascript representation of this rule
	*
	* @return string
	*/
	public function toJavaScript()
	{
		return isc_json_encode($this);
	}

	/**
	* Create an Interspire_EmailIntegration_Rule from an associative array, as a result of json_encode({posted rule string}, true) - see ISC_EMAILINTEGRATION->SaveModuleSettings
	*
	* @param array $array or false on error
	* @return Interspire_EmailIntegration_Rule
	*/
	public static function fromJSON($array)
	{
		if (!is_array($array)) {
			return false;
		}

		switch (strtolower($array['action'])) {
			case 'listadd':
				$action = self::ACTION_ADD;
				break;

			case 'listremove':
				$action = self::ACTION_REMOVE;
				break;

			default:
				return false;
		}

		$eventClass = self::getClassNameForEventId($array['event']);
		if (!$eventClass) {
			return false;
		}

		return new $eventClass($array['id'], $array['module'], $action, $array['list'], $array['fieldMap'], $array['criteria']);
	}

	/**
	* Delete this rule from the database (based on $this->id)
	*
	* @return bool
	*/
	public function delete()
	{
		$id = (int)$this->id;
		if (!$id) {
			return false;
		}

		/** @var mysqldb */
		$db = $GLOBALS['ISC_CLASS_DB'];

		return $db->DeleteQuery('email_rules', "WHERE `id` = " . $id);
	}

	/**
	* Save this rule to the database. If id is a false value, a rule will be inserted - otherwise, the rule for the given id will be updated
	*
	* @return bool
	*/
	public function save()
	{
		$row = array(
			'provider' => $this->moduleId,
			'event' => $this->eventId,
			'action' => $this->action,
			'provider_list_id' => $this->listId,
			'field_map' => serialize($this->fieldMap),
			'event_criteria' => serialize($this->eventCriteria),
		);

		/** @var mysqldb */
		$db = $GLOBALS['ISC_CLASS_DB'];

		$id = (int)$this->id;
		if ($id) {
			$result = $db->UpdateQuery('email_rules', $row, "`id` = " . $id);
		}
		else
		{
			$result = $db->InsertQuery('email_rules', $row);
			if ($result) {
				$this->id = $db->LastId();
			}
		}

		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];

		if (!$result) {
			$log->LogSystemError('emailintegration', 'Failed to save EmailIntegration_Rule.', var_export($this, true));
		}
		else
		{
			$log->LogSystemDebug('emailintegration', 'Saved EmailIntegration_Rule.', var_export($this, true));
		}

		return $result;
	}

	public static function fromDatabase($id)
	{
		return self::fromDatabaseRow($GLOBALS['ISC_CLASS_DB']->FetchRow("SELECT * FROM `[|PREFIX|]email_rules` WHERE `id` = " . (int)$id));
	}

	/**
	* Create an Interspire_EmailIntegration_Rule from an associative array, as a result of querying the email_rules table
	*
	* @param array $row
	* @return Interspire_EmailIntegration_Rule
	*/
	public static function fromDatabaseRow($row)
	{
		if (!is_array($row)) {
			return false;
		}

		$fieldMap = @unserialize($row['field_map']);
		$eventCriteria = @unserialize($row['event_criteria']);

		$eventClass = self::getClassNameForEventId($row['event']);
		if (!$eventClass) {
			return false;
		}

		return new $eventClass((int)$row['id'], $row['provider'], $row['action'], $row['provider_list_id'], $fieldMap, $eventCriteria);
	}

	/**
	* Return singular language based name of this rule
	*
	* @return string
	*/
	public function getSingularName()
	{
		return GetLang(get_class($this) . '_Name_Singular');
	}

	/**
	* Return plural language based name of this rule
	*
	* @return string
	*/
	public function getPluralName()
	{
		return GetLang(get_class($this) . '_Name_Plural');
	}
}
