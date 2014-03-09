<?php

/**
* Interspire_KeyStore interface
*
* @todo keys($pattern) must support redis keys pattern & mysql queries (such as LIKE) and return array of keys
* @todo flush() delete all keys
* @todo rename($old, $new, $overwrite = true) rename key from old to new, if $overwrite then destroying new if exists, otherwise throw exception?
*/
interface Interspire_KeyStore_Interface
{
	/**
	* Returns singleton instance
	*
	* @return Interspire_KeyStore
	* @throws Interspire_KeyStore_Exception
	*/
	public static function instance();

	/**
	* Returns the current number of keys in the keystore
	*
	* @return int
	* @throws Interspire_KeyStore_Exception
	*/
	public function count();

	/**
	* Sets a key to a string value
	*
	* @param string $key
	* @param string $value
	* @return void
	* @throws Interspire_KeyStore_Exception
	*/
	public function set($key, $value);

	/**
	* Sets values of multiple keys in as few operations as possible
	*
	* @param array $keyvalues An associative array of key => value pairs to set in one hit.
	* @return void
	* @throws Interspire_KeyStore_Exception
	*/
	public function multiSet($keyvalues);

	/**
	* Gets string value of key
	*
	* @param string $key
	* @return mixed string value or false if key doesn't exist
	* @throws Interspire_KeyStore_Exception
	*/
	public function get($key);

	/**
	* Get values of multiple keys in as few operations as possible
	*
	* @param string|array $key If provided as a string, will be treated as a pattern the same as keys() - otherwise if provided as an array, will get those keys specifically.
	* @return array Associative array of key => value
	* @throws Interspire_KeyStore_Exception
	*/
	public function multiGet($key);

	/**
	* Checks if a key exists
	*
	* @param string $key
	* @return bool True if exists or false if not exists
	* @throws Interspire_KeyStore_Exception
	*/
	public function exists($key);

	/**
	* Delete a key
	*
	* @param string $key
	* @return void
	* @throws Interspire_KeyStore_Exception
	*/
	public function delete($key);

	/**
	* Delete multiple keys in as few operations as possible
	*
	* @param string|array $key If provided as a string, will be treated as a pattern the same as keys() - otherwise if provided as an array, will delete those keys specifically.
	* @return void
	* @throws Interspire_Keystore_Exception
	*/
	public function multiDelete($key);

	/**
	* Find a list of keys by pattern
	*
	* @param string $pattern Must be redis-like, see http://code.google.com/p/redis/wiki/KeysCommand - be careful when using this with the mysql backend as using the [character] specifier will trigger a REGEXP match which will be largely unquoted.
	* @return array
	* @throws Interspire_KeyStore_Exception
	*/
	public function keys($pattern);

	/**
	* Delete all values
	*
	* @return void
	* @throws Interspire_KeyStore_Exception
	*/
	public function flush();

	/**
	* Increment a key in the keystore by the given value. If the key does not exist, it will be set to $value. An existing key's value will be treated as an int so this may destroy existing string data if used improperly
	*
	* @param string $key key to increment
	* @param int $value new value
	* @throws Interspire_KeyStore_Exception
	*/
	public function increment($key, $value = 1);

	/**
	* Decrement a key in the keystore by the given value. If the key does not exist, it will be set to $value. An existing key's value will be treated as an int so this may destroy existing string data if used improperly
	*
	* @param string $key key to decrement
	* @param int $value new value
	* @throws Interspire_KeyStore_Exception
	*/
	public function decrement($key, $value = 1);
}

