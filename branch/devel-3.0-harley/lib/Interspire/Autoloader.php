<?php

class Interspire_Autoloader
{
	/**
	 * @var array Array of search paths to search for autoloadable classes.
	 */
	private static $loadPaths = array();

	/**
	 * Register this autoloader in the list of PHP autoloaders.
	 */
	public static function register()
	{
		spl_autoload_register(array('Interspire_Autoloader', 'autoload'));
	}

	/**
	 * Attempt to autoload a class from the filesystem by searching through the
	 * supplied search paths until a class with a matching filename is found.
	 *
	 * @param string $className The name of the class to autoload.
	 * @return boolean True if the class was loaded successfully, false if not.
	 */
	public static function autoload($className)
	{
		// Legacy autoload
		if(substr(strtolower($className), 0, 3) == 'isc') {
			return self::autoloadLegacy($className);
		}

		$fileName = str_replace('_', '/', $className).'.php';
		foreach(self::$loadPaths as $path) {
			$classPath = $path.'/'.$fileName;
			if(is_file($classPath)) {
				require_once $classPath;
				return true;
			}
		}

		return false;
	}

	/**
	 * Provide backwards compatible autoloading for any classes named with the old
	 * naming conventions.
	 *
	 * @param string $className The name of the class to autoload.
	 * @return boolean True if the class was loaded successfully, false if not.
	 */
	public static function autoloadLegacy($className)
	{
		$className = strtolower($className);

		// Loading an administration class
		if (substr($className, 0, 9) == 'isc_admin') {
			$class = explode('_', $className, 3);
			$fileName = strtolower($class[2]);
			$fileName = str_replace('_', '.', $fileName);

			if (substr($fileName, 0, 15) == 'exportfiletype.') {
				$class = explode('.', $fileName);
				$fileName = $class[1];
				$fileName = ISC_BASE_PATH.'/admin/includes/exporter/filetypes/'.$fileName.'.php';
			}
			elseif (substr($fileName, 0, 13) == 'exportmethod.') {
				$class = explode('.', $fileName);
				$fileName = $class[1];
				$fileName = ISC_BASE_PATH.'/admin/includes/exporter/methods/'.$fileName.'.php';
			}
			else {
				$fileName = ISC_BASE_PATH.'/admin/includes/classes/class.'.$fileName.'.php';
			}
		}
		// Loading an entity class (customer, product, etc)
		elseif (substr($className, 0, 10) == 'isc_entity') {
			$class = explode('_', $className, 3);
			$fileName = strtolower($class[2]);
			$fileName = str_replace('_', '.', $fileName);
			$fileName = ISC_BASE_PATH.'/lib/entities/entity.'.$fileName.'.php';
		}
		else {
			$class = explode('_', $className, 2);
			$fileName = strtolower($class[1]);
			$fileName = str_replace('_', '.', $fileName);
			$fileName = ISC_BASE_PATH.'/includes/classes/class.'.$fileName.'.php';
		}

		if (file_exists($fileName)) {
			require_once $fileName;
			return true;
		}

		return false;
	}

	/**
	 * Add a path to the list of paths to search for classes.
	 *
	 * @param string $path The path to add.
	 * @return boolean True if the path was added successfully, false if not.
	 */
	public static function addPath($path)
	{
		if(!is_dir($path)) {
			return false;
		}

		self::$loadPaths[] = $path;
		self::$loadPaths = array_unique(self::$loadPaths);
		return true;
	}
}