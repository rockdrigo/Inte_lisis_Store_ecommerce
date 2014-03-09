<?php
define("METHOD_ROOT", APP_ROOT . "/includes/exporter/methods/");

require_once APP_ROOT . "/includes/exporter/class.exportmethod.php";

/**
* A factory class to get a list of available export methods and to get a specific export method object
*
* @author Ray Ward <ray.ward@interspire.com>
*/
class ISC_ADMIN_EXPORTMETHOD_FACTORY
{
	/**
	* Gets an Export Method object
	*
	* @param mixed $method
	*
	* @return ISC_ADMIN_EXPORTMETHOD An Export Method object
	*/
	public static function GetExportMethod($method)
	{
		$file = METHOD_ROOT . strtolower($method)  . ".php";

		if (is_file($file)) {
			require_once $file;

			$className = "ISC_ADMIN_EXPORTMETHOD_" . $method;

			return new $className;
		}
	}

	/**
	* Gets a list of available export methods
	*
	* @return array An array of details about available export methods. methodname => details[]
	*/
	public static function GetExportMethodList()
	{
		$files = scandir(METHOD_ROOT);

		$methods = array();

		foreach($files as $file) {
			if(!is_file(METHOD_ROOT . $file) || isc_substr($file, -3) != "php") {
				continue;
			}

			require_once METHOD_ROOT . $file;

			$file = isc_substr($file, 0, isc_strlen($file) - 4);
			$file = strtoupper($file);
			/*
			$pos = isc_strrpos($file, ".");
			$methodName = isc_strtoupper(isc_substr($file, $pos + 1));
			*/
			$className = "ISC_ADMIN_EXPORTMETHOD_" . $file; //$methodName;
			if(!class_exists($className)) {
				continue;
			}

			$obj = new $className;
			$methods[$file] = $obj->GetMethodDetails();
		}

		return $methods;
	}
}