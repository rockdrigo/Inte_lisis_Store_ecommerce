<?php

/**
* Mysql KeyStore implementation
*
* Note: key / value length checking is left up to mysql to report an error when data is too long for a column.
*/
class Interspire_KeyStore_Mysql implements Interspire_KeyStore_Interface
{
	/**
	* Singleton reference
	*
	* @var Interspire_KeyStore_Mysql
	*/
	protected static $_instance;

	/**
	* Db library reference
	*
	* @var mysqldb
	*/
	protected $db;

	/**
	* Returns singleton instance.
	*
	* @return Interspire_KeyStore
	* @throws Interspire_KeyStore_Exception
	*/
	public static function instance()
	{
		if (self::$_instance === null) {
			return new self;
		}
		return self::$_instance;
	}

	/**
	* A callback to give to array_walk which will db-quote all entries in the array
	*/
	protected function arrayWalkDbQuote(&$key, $index)
	{
		$key = $this->db->Quote($key);
	}

	/**
	* Convert a redis-like key pattern to mysql =, LIKE or REGEXP syntax
	*
	* @param string $pattern
	* @return string
	*/
	protected function redisPatternToMysql($pattern)
	{
		// incoming key patterns are redis-like, convert to mysql equivalent
		if ($pattern == '*') {
			// shortcut to all keys
			return '';
		}
		if (isc_strpos($pattern, '[') !== false && isc_strpos($pattern, ']') !== false) {
			// need to use regex
			$pattern = "^" .strtr($pattern, array(
				'*' => '.*',
				'?' => '.',
			)) . "$";

			$pattern = "REGEXP '" . $this->db->Quote($pattern) . "'";
		}
		else if (isc_strpos($pattern, '*') !== false || isc_strpos($pattern, '?') !== false) {
			// no [character] specifiers, can use LIKE
			$pattern = strtr($pattern, array(
				'%' => '\%',
				'_' => '\_',
				'*' => '%',
				'?' => '_',
			));
			$pattern = "LIKE '" . $this->db->Quote($pattern) . "'";
		}
		else
		{
			// no pattern specified, can use =
			$pattern = "= '" . $this->db->Quote($pattern) . "'";
		}

		return $pattern;
	}

	/**
	* Class constructor
	*
	* @return Interspire_KeyStore_Mysql
	*/
	public function __construct()
	{
		self::$_instance = $this;
		$this->db = $GLOBALS['ISC_CLASS_DB'];
	}

	/**
	* Returns the current number of keys in the keystore
	*
	* @return int
	* @throws Interspire_KeyStore_Exception
	*/
	public function count()
	{
		$count = $this->db->FetchOne("SELECT COUNT(*) as `c` FROM `[|PREFIX|]keystore`");
		if ($count === false) {
			throw new Interspire_KeyStore_Exception($this->db->GetErrorMsg());
		}
		return (int)$count;
	}

	/**
	* Sets a key to a string value
	*
	* @param string $key
	* @param string $value
	* @return bool
	* @throws Interspire_KeyStore_Exception
	*/
	public function set($key, $value)
	{
		$query = "INSERT INTO `[|PREFIX|]keystore` (`key`, `value`) VALUES ('" . $this->db->Quote($key) . "', '" . $this->db->Quote($value) . "') ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)";
		$query = $this->db->Query($query);
		if ($query === true) {
			return true;
		}
		throw new Interspire_KeyStore_Exception($this->db->GetErrorMsg());
	}

	/**
	* Sets values of multiple keys in as few operations as possible
	*
	* @param array $keyvalues An associative array of key => value pairs to set in one hit.
	* @return void
	* @throws Interspire_KeyStore_Exception
	*/
	public function multiSet($keyvalues)
	{
		$query = "INSERT INTO `[|PREFIX|]keystore` (`key`, `value`) VALUES ";

		$first = true;
		foreach ($keyvalues as $key => $value) {
			if ($first) {
				$first = false;
			}
			else
			{
				$query .= ",";
			}
			$query .= "('" . $this->db->Quote($key) . "', '" . $this->db->Quote($value) . "')";
		}
		$query .= " ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)";

		$query = $this->db->Query($query);
		if ($query === true) {
			return true;
		}
		throw new Interspire_KeyStore_Exception($this->db->GetErrorMsg());
	}

	/**
	* Gets string value of key
	*
	* @param string $key
	* @return mixed string value or false if key doesn't exist
	* @throws Interspire_KeyStore_Exception
	*/
	public function get($key)
	{
		$query = "SELECT `value` FROM `[|PREFIX|]keystore` WHERE `key` = '" . $this->db->Quote($key) . "'";
		$query = $this->db->Query($query);
		if ($query === false) {
			throw new Interspire_KeyStore_Exception($this->db->GetErrorMsg());
		}

		$query = $this->db->Fetch($query);
		if ($query === false) {
			return false;
		}

		return $query['value'];
	}

	/**
	* Get values of multiple keys in as few operations as possible
	*
	* @param string|array $keys If provided as a string, will be treated as a pattern the same as keys() - otherwise if provided as an array, will get those keys specifically.
	* @return array Associative array of key => value
	* @throws Interspire_KeyStore_Exception
	*/
	public function multiGet($keys)
	{
		$query = "SELECT * FROM `[|PREFIX|]keystore`";

		if (is_array($keys)) {
			array_walk($keys, array($this, 'arrayWalkDbQuote'));
			$query .= " WHERE `key` IN ('" . implode("','", $keys) . "')";
		}
		else
		{
			$where = $this->redisPatternToMysql($keys);
			if ($where) {
				$query .= " WHERE `key` " . $where;
			}
		}

		$query = $this->db->Query($query);
		if ($query === false) {
			throw new Interspire_KeyStore_Exception($this->db->GetErrorMsg());
		}

		$return = array();
		while ($row = $this->db->Fetch($query))
		{
			$return[$row['key']] = $row['value'];
		}
		return $return;
	}

	/**
	* Checks if a key exists
	*
	* @param string $key
	* @return bool True if exists or false if not exists
	* @throws Interspire_KeyStore_Exception
	*/
	public function exists($key)
	{
		$query = "SELECT COUNT(`key`) as `c` FROM `[|PREFIX|]keystore` WHERE `key` = '" . $this->db->Quote($key) . "'";
		$query = $this->db->Query($query);
		if ($query === false) {
			throw new Interspire_KeyStore_Exception($this->db->GetErrorMsg());
		}

		$query = $this->db->Fetch($query);
		if ($query === false) {
			return false;
		}

		return (bool)$query['c'];
	}

	/**
	* Delete a key
	*
	* @param string $key
	* @return void
	* @throws Interspire_KeyStore_Exception
	*/
	public function delete($key)
	{
		$query = "DELETE FROM `[|PREFIX|]keystore` WHERE `key` = '" . $this->db->Quote($key) . "'";
		$query = $this->db->Query($query);
		if ($query === false) {
			throw new Interspire_KeyStore_Exception($this->db->GetErrorMsg());
		}
	}

        /**
        * Delete multiple keys in as few operations as possible
        *
        * @param string|array $keys If provided as a string, will be treated as a pattern the same as keys() - otherwise if provided as an array, will delete those keys specifically.
        * @return void
        * @throws Interspire_Keystore_Exception
        */
	public function multiDelete($keys)
	{
		$query = "DELETE FROM `[|PREFIX|]keystore`";

		if (is_array($keys)) {
			array_walk($keys, array($this, 'arrayWalkDbQuote'));
			$query .= " WHERE `key` IN ('" . implode("','", $keys) . "')";
		}
		else
		{
			$where = $this->redisPatternToMysql($keys);
			if ($where) {
				$query .= " WHERE `key` " . $where;
			}
		}

		$query = $this->db->Query($query);
		if ($query === false) {
			throw new Interspire_KeyStore_Exception($this->db->GetErrorMsg());
		}
	}

	/**
	* Find a list of keys by pattern
	*
	* @param string $pattern Must be redis-like, see http://code.google.com/p/redis/wiki/KeysCommand - be careful when using this with the mysql backend as using the [character] specifier will trigger a REGEXP match which will be largely unquoted.
	* @return array
	* @throws Interspire_KeyStore_Exception
	*/
	public function keys($pattern)
	{
		$pattern = $this->redisPatternToMysql($pattern);

		if ($pattern) {
			$pattern = $this->db->Query("SELECT `key` FROM `[|PREFIX|]keystore` WHERE `key` " . $pattern);
		}
		else
		{
			$pattern = $this->db->Query("SELECT `key` FROM `[|PREFIX|]keystore`");
		}

		if ($pattern === false) {
			throw new Interspire_KeyStore_Exception($this->db->GetErrorMsg());
		}

		$return = array();
		while ($row = $this->db->Fetch($pattern))
		{
			$return[] = $row['key'];
		}
		return $return;
	}

	/**
	* Delete all values
	*
	* @return void
	* @throws Interspire_KeyStore_Exception
	*/
	public function flush()
	{
		$result = $this->db->Query("TRUNCATE TABLE `[|PREFIX|]keystore`");
		if (!$result) {
			throw new Interspire_KeyStore_Exception($this->db->GetErrorMsg());
		}
	}

	/**
	* Increment a key in the keystore by the given value. If the key does not exist, it will be set to $value. An existing key's value will be treated as an int so this may destroy existing string data if used improperly
	*
	* @param string $key key to increment
	* @param int $value new value
	* @throws Interspire_KeyStore_Exception
	*/
	public function increment($key, $value = 1)
	{
		$query = "INSERT INTO `[|PREFIX|]keystore` (`key`, `value`) VALUES ('" . $this->db->Quote($key) . "', '" . $value . "') ON DUPLICATE KEY UPDATE `value` = CAST(`value` AS SIGNED) + VALUES(`value`)";
		$result = $this->db->Query($query);
		if (!$result) {
			throw new Interspire_KeyStore_Exception($this->db->GetErrorMsg());
		}

		return (int)$this->get($key);
	}

	/**
	* Decrement a key in the keystore by the given value. If the key does not exist, it will be set to $value. An existing key's value will be treated as an int so this may destroy existing string data if used improperly
	*
	* @param string $key key to decrement
	* @param int $value new value
	* @throws Interspire_KeyStore_Exception
	*/
	public function decrement($key, $value = 1)
	{
		return $this->increment($key, 0 - (int)$value);
	}
}
