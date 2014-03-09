<?php

class API
{
	/**
	* @var mixed A link to the database object if one exists, otherwise false
	*/
	public $db = false;

	/**
	* @var boolean has the item been loaded from the database
	*/
	public $loaded = false;

	/**
	* @var string the full table name
	*/
	public $table = '';

	/**
	* @var string the prefix of the table
	*/
	public $tablePrefix = '';

	/**
	* @var boolean Is versioning on ? True = yes
	*/
	public $versioned = false;

	/**
	* @var integer how many versions to keep
	*/
	public $maxVersions = 5;

	/**
	* @var string the name of the field in the table which is the primary key
	*/
	public $pk = null;

	/**
	* @var string the last error which caused a return false
	*/
	public $error = '';

	/**
	* Constructor - Initialize the things that can't be defined statically
	*
	* @return void
	*/
	public function __construct()
	{
		$this->setupDatabase();
		if ($this->pk === null && !empty($this->fields)) {
			$this->pk = $this->fields[0];
		}
	}

	/**
	* Setup the default database settings in the class
	*
	* @return void
	*/
	public function setupDatabase()
	{
		$this->db = $GLOBALS['ISC_CLASS_DB'];
		$tableSuffix = str_replace('api_','',strtolower(get_class($this))).'s';
		$this->table = '[|PREFIX|]'.$tableSuffix;
		$this->tablePrefix = '[|PREFIX|]';
	}

	/**
	* Dont save any of the database stuff when we get serialized
	*
	* @return array
	*/
	public function __sleep()
	{
		return array('loaded');
	}

	/**
	* Reestablish database settings when being unserialized
	*
	* @return void
	*/
	public function __wakeup()
	{
		$this->setupDatabase();
	}

	/**
	* Create a new item in the database
	*
	* @return mixed false if failed to create, the id of the item otherwise
	*/
	public function create()
	{
		$first = true;
		$vals = array();

		// If the database connection isnt setup we can't proceed
		if (!$this->db) {
			$this->error = GetLang('apiNotConnectedToDB');
			return false;
		}

		$fields = $this->fields;
		$pk_key = array_search($this->pk, $fields);
		if ($pk_key !== false && $pk_key !== null) {
			unset($fields[$pk_key]);
			$vals[$pk_key] = 0;
		}

		// Build the query
		$query = 'INSERT INTO '.$this->table.'
			( '.implode(', ', $fields)." ) VALUES ('";

		$values = $_POST;

		// Merge post values with our defaults
		if(isset($this->defaultvalues))
			$values = array_merge($this->defaultvalues, $values);

		foreach ($fields as $field) {
			// If any of our fields arn't set its going to upset the column
			// count. Default values can be specified via $this->defaultvalues
			// @see category.api.php
			if (!isset($values[$field])) {
				$this->error = sprintf(GetLang('apiPostNotSet'), $field);
				return false;
			}

			if (method_exists($this, 'validate_'.$field)
				&& !call_user_func(array($this, 'validate_'.$field), $values[$field])) {
					if (empty($this->error)) {
						$this->error = sprintf(GetLang('apiValidateFailedFor'), $field);
					}
				return false;
			}

			if (!$first) {
				$query .= "' , '";
			} else {
				$first = false;
			}
			$query .= $this->db->Quote($values[$field]);
			$vals[] = $this->db->Quote($values[$field]);
		}
		$query .= "')";

		if ($this->db->Query($query)) {
			$newid = $this->db->LastId($this->table.'_seq');
			$vals[$pk_key] = $newid;

			// If we are versioned run the history query
			if ($this->versioned) {
				$history_query = 'INSERT INTO '.$this->table.'_history
				(`'.implode('`,`', $this->fields)."`) VALUES ('".implode("', '", $vals)."')";
				$this->db->Query($history_query);
			}
			return $newid;
		} else {
			$this->error = GetLang('apiInsertFailed').' '.$this->db->GetErrorMsg();
			return false;
		}
	}

	/**
	* Delete an item from the database
	*
	* @param integer $id If id is given and is positive delete the item $id. If $id is literally set to null, it will delete the currently loaded id.
	*
	* @return boolean True if deletion successful
	*/
	public function delete($id=0)
	{
		if ($id === null && $this->loaded) {
			$id = $this->{$this->pk};
		}

		// Stop if the id isnt an pos integer
		if (!$this->is_positive_int($id)) {
			$this->error = GetLang('apiIdNotAPosInt');
			return false;
		}

		// If the database connection isnt setup we can't proceed
		if (!$this->db) {
			$this->error = GetLang('apiNotConnectedToDB');
			return false;
		}

		// Otherwise delete the item
		$query = 'DELETE
		FROM '.$this->table.'
		WHERE '.$this->pk.' = '.($id);

		if ($this->db->Query($query)) {
			return true;
		} else {
			$this->error = GetLang('apiDeleteFailed');
			return false;
		}

	}

	/**
	* Deletes an item from a table which is managed by a nested set structure. Automatically handles deletion of child records.
	*
	* @param ISC_NESTEDSET $nestedSet An instance of ISC_NESTEDSET (or another class that extends it) for the table being managed
	* @param integer $ids If id is given and is positive delete the item $id
	* @return boolean Return true on successful deletion
	*/
	public function deleteNestedSet(ISC_NESTEDSET $nestedSet, $id=0)
	{
		return $this->multiDeleteNestedSet($nestedSet, array($id));
	}

	/**
	* Delete multiple items in one database query, useful for bulk actions
	*
	* @param $ids array The array of ids to delete.
	*
	* @return boolean Return true on successful deletion
	*/
	public function multiDelete($ids=0)
	{
		// If the database connection isnt setup we can't proceed
		if (!$this->db) {
			$this->error = GetLang('apiNotConnectedToDB');
			return false;
		}

		if (!is_array($ids)) {
			$this->error = GetLang('apiNotAnArray');
			return false;
		}

		if (empty($ids)) {
			return true;
		}

		// Make sure the array only contains integers
		foreach (array_keys($ids) as $key) {
			if (!is_numeric($key)) {
				$this->error = GetLang('apiIdNotAPosInt');
				return false;
			}
		}

		$query = 'DELETE
		FROM '.$this->table.'
		WHERE '.$this->pk.' IN ( '.implode(',', array_keys($ids)).')';

		if ($this->db->Query($query)) {
			return true;
		} else {
			$this->error = GetLang('apiDeleteFailed');
			return false;
		}

	}

	/**
	* Delete multiple items from a table which is managed by a nested set structure. Automatically handles deletion of child records.
	*
	* @param ISC_NESTEDSET $nestedSet An instance of ISC_NESTEDSET (or another class that extends it) for the table being managed
	* @param array $ids The array of ids to delete
	* @return boolean Return true on successful deletion
	*/
	public function multiDeleteNestedSet($nestedSet, $ids=0)
	{
		// If the database connection isnt setup we can't proceed
		if (!$this->db) {
			$this->error = GetLang('apiNotConnectedToDB');
			return false;
		}

		if (!is_array($ids)) {
			$this->error = GetLang('apiNotAnArray');
			return false;
		}

		if (empty($ids)) {
			return true;
		}

		// Make sure the array only contains integers
		foreach ($ids as $id) {
			if (!is_numeric($id)) {
				$this->error = GetLang('apiIdNotAPosInt');
				return false;
			}
		}

		$success = true;
		foreach ($ids as $id) {
			if (!$nestedSet->deleteNode($id)) {
				$success = false;
			}
		}

		if (!$success) {
			$this->error = GetLang('apiDeleteFailed');
			return false;
		}

		return true;
	}

	/**
	* Save the loaded object to the database if any of the fields have been
	* modified since being loaded. If the item is versioned it will save a copy
	*
	* @return boolean True if the save succeeded
	*/
	public function save()
	{
		// We can only save already loaded objects
		if (!$this->loaded) {
			$this->error = GetLang('apiNotLoaded');
			return false;
		}

		$pk = array ($this->pk);

		// remove the primary key from the field list
		$fields = array_diff($this->fields, $pk);

		foreach ($fields as $field) {
			// If the field has been modified update it
			if (isset($_POST[$field]) && $this->$field != $_POST[$field]) {
				if (!$this->updateField($field, $_POST[$field])) {
					return false;
				}
			}
		}

		if ($this->versioned) {
			$history_query = 'INSERT INTO '.$this->table.'_history
			('.implode(', ', $this->fields).')
			SELECT '.implode(', ', $this->fields).'
			FROM '.$this->table.'
			WHERE '.$this->pk.' = '.((int) $this->{$this->pk});

			$this->db->Query($history_query);

			$this->cleanupOldVersions();
		}

		return true;
	}

	/**
	* Update a database field.
	*
	* @param string $field the name of the field to update
	* @param string $value the value to update it with. This value will be put through db->Quote before saving
	*
	* @return boolean True if update was successful
	*/
	public function updateField($field, $value)
	{
		// If the database connection isnt setup we can't proceed
		if (!$this->db) {
			$this->error = GetLang('apiNotConnectedToDB');
			return false;
		}

		// We can only do an update on loaded objects
		if (!$this->loaded) {
			$this->error = GetLang('apiNotLoaded');
			return false;
		}

		if (method_exists($this, 'validate_'.$field)
			&& !call_user_func(array($this, 'validate_'.$field), $value)) {
			$this->error = sprintf(GetLang('apiValidateFailedFor'), $field);
			return false;
		}

		$query = 'UPDATE '.$this->table.'
		SET '.$field." = '".$this->db->Quote($value)."'
		WHERE ".$this->pk.' = '.((int) $this->{$this->pk});

		if ($this->db->Query($query)) {
			return true;
		} else {
			$this->error = GetLang('apiUpdateFailed');
			return false;
		}
	}

	/**
	* Update multiple database fields. If the item is versioned save a copy of
	* the update to the versioned table
	*
	* @param string $field the name of the field to update
	* @param string $value the value to update it with. This value will be put through db->Quote before saving
	* @param array $ids the ids to update
	*
	* @return boolean True if update was successful
	*/
	public function multiUpdateField($field, $value, $ids)
	{
		// If the database connection isnt setup we can't proceed
		if (!$this->db) {
			$this->error = GetLang('apiNotConnectedToDB');
			return false;
		}

		if (!is_array($ids)) {
			$this->error = GetLang('apiNotAnArray');
			return false;
		}

		if (empty($ids)) {
			return true;
		}

		// Make sure the array only contains integers
		foreach ($ids as $key => $val) {
			if (!is_numeric($key)) {
				$this->error = GetLang('apiIdNotAPosInt');
				return false;
			}
		}

		if (method_exists($this, 'validate_'.$field)
			&& !call_user_func(array($this, 'validate_'.$field), $value)) {
			$this->error = sprintf(GetLang('apiValidateFailedFor'), $field);
			return false;
		}

		$query = 'UPDATE '.$this->table.'
		SET '.$field." = '".$this->db->Quote($value)."'
		WHERE ".$this->pk.' IN ( '.implode(',', array_keys($ids)).')';

		$success = $this->db->Query($query);

		if ($this->versioned) {
			$history_query = 'INSERT INTO '.$this->table.'_history
			('.implode(', ', $this->fields).')
			SELECT '.implode(', ', $this->fields).'
			FROM '.$this->table.'
			WHERE '.$this->pk.' IN ('.implode(',', array_keys($ids)).')';

			$this->db->Query($history_query);

			$this->cleanupOldVersions();
		}

		return $success;
	}

	/**
	* Different approach to loading. Instead of using the pk to load, use another
	* field in the table which is also unique e.g. username
	*
	* @param string $field The name of the field to check
	* @param string $value The value to find in $field
	*
	* @return boolean True if the item was loaded from the database
	*/
	public function find($field, $value)
	{
		// If the database connection isnt setup we can't proceed
		if (!$this->db) {
			$this->error = GetLang('apiNotConnectedToDB');
			return false;
		}

		$query = 'SELECT *
		FROM '.$this->table.'
		WHERE '.$field." = '".$this->db->Quote($value)."'";

		$result = $this->db->Query($query);

		// If we have fetched the wrong number of rows return false
		if ($this->db->CountResult($result) != 1) {
			$this->error = GetLang('apiWrongLoadCount');
			return false;
		}

		$row = $this->db->Fetch($result);

		// Setup this object
		foreach ($this->fields as $field) {
			$this->$field = $row[$field];
		}
		$this->loaded = true;
		return true;
	}

	/**
	* Load an item from the database by its pk
	*
	* @param integer $id The id of the item
	*
	* @return boolean True if it was loaded ok
	*/
	public function load($id)
	{
		return $this->find($this->pk, $id);
	}

	/**
	* Revert this item to a previous version
	*
	* @param $versionid The id to revert to
	*
	* @return boolean True if the revert succeeded
	*/
	public function revert($versionid)
	{
		// We can't revert if we arn't versioning
		if (!$this->versioned) {
			$this->error = GetLang('apiNotVersioned');
			return false;
		}

		// We are trying to restore to an invalid version
		if (!$this->is_positive_int($versionid)) {
			$this->error = GetLang('apiIdNotAPosInt');
			return false;
		}

		$query = 'SELECT *
		FROM '.$this->table."_history
		WHERE versionid='".$GLOBALS['ISC_CLASS_DB']->Quote($versionid)."'";

		$result = $this->db->Query($query);
		if ($this->db->CountResult($result) != 1) {
			$this->error = GetLang('apiWrongLoadCount');
			return false;
		}

		$row = $this->db->Fetch($result);

		foreach ($row as $k => $v) {
			$_POST[$k] = $v;
		}
		return $this->save();
	}

	/**
	* Remove old versions of a versioned item from the database
	*
	* @return void
	*/
	public function cleanupOldVersions()
	{
		$versionsToDel = array();
		$query = 'SELECT versionid
		FROM '.$this->table.'_history
		WHERE '.$this->pk.' = '.((int) $this->{$this->pk}).'
		ORDER BY versionid DESC';
		$result = $this->db->Query($query);
		// If we have more versions then we want to keep
		if ($this->db->CountResult($result) > $this->maxVersions) {
			$count = 1;
			// Build a list of versions to delete
			while ($row = $this->db->Fetch($result)) {
				if ($count > $this->maxVersions) {
					$versionsToDel[] = $row['versionid'];
				}
				$count++;
			}

			// If we have versions to delete then delete them
			if (!empty($versionsToDel)) {
				$query = 'DELETE
				FROM '.$this->table.'_history
				WHERE versionid IN ('.implode(',', $versionsToDel).')';
				$this->db->Query($query);
			}
		}
	}

	/**
	* is_ip
	*
	* Check if a var is in a valid ipv4 format
	*
	* @param string $var
	*
	* @return bool
	*/
	public function is_ip($var)
	{
		$octets = explode('.', $var);
		if (count($octets) != 4) {
			return false;
		}

		foreach ($octets as $octet) {
			if ((int) $octet != $octet) {
				return false;
			}

			if ($octet < 0) {
				return false;
			}

			if ($octet > 255) {
				return false;
			}
		}
		return true;
	}

	/**
	* is_int
	*
	* Check if a variable is an integer
	*
	* @param string $var
	*
	* @return bool
	*/
	public function is_int($var)
	{
		return ((int) $var == $var);
	}

	/**
	* is_positive_int
	*
	* Check if a variable is a positive integer
	*
	* @param string $var
	* @param bool $include_zero
	*
	* @return bool
	*/
	public function is_positive_int($var, $include_zero=true)
	{
		if (!$this->is_int($var)) {
			return false;
		}

		if ($var < 0) {
			return false;
		}

		if ($var > 0 || $include_zero) {
			return true;
		}

		return false;
	}

	/**
	* is_standard_date
	*
	* Ensure the var is in the standard date format (Y-m-d H:i:s)
	*
	* @param string $var
	*
	* @return bool
	*/
	public function is_standard_date($var)
	{
		return preg_match('/\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{1,2}:\d{1,2}/', $var);
	}

}