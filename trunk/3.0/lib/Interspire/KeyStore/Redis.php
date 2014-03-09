<?php

/**
* Redis KeyStore implementation via Rediska library
*/
class Interspire_KeyStore_Redis implements Interspire_KeyStore_Interface
{
	/**
	* Singleton reference
	*
	* @var Interspire_KeyStore_Redis
	*/
	protected static $_instance;

	/**
	* @var Rediska
	*/
	protected $redis;

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
	* Class constructor
	*
	* @return Interspire_KeyStore_Mysql
	*/
	public function __construct()
	{
		self::$_instance = $this;
		$this->rediska = new Rediska(array(
			'namespace' => GetConfig('RedisNamespace'),
			'servers' => GetConfig('RedisServers'),
		));
	}

	/**
	* Returns the current number of keys in the keystore
	*
	* @return int
	* @throws Interspire_KeyStore_Exception
	*/
	public function count()
	{
		return $this->rediska->GetKeysCount();
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
		return $this->rediska->Set($key, $value);
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
		$this->rediska->Set($keyvalues);
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
		return $this->rediska->Get($key);
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
		if (is_string($keys)) {
			$keys = $this->rediska->GetKeysByPattern($keys);
		}
		return $this->rediska->Get($keys);
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
		return $this->rediska->Exists($key);
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
		return $this->rediska->Delete($key);
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
		if (is_string($keys)) {
			$keys = $this->rediska->GetKeysByPattern($keys);
		}
		return $this->rediska->Delete($keys);
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
		return $this->rediska->GetKeysByPattern($pattern);
	}

	/**
	* Delete all values
	*
	* @return void
	* @throws Interspire_KeyStore_Exception
	*/
	public function flush()
	{
		return $this->rediska->FlushDb();
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
		return $this->rediska->Increment($key, $value);
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
		return $this->rediska->Decrement($key, $value);
	}
}
