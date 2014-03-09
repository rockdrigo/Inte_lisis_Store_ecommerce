<?php
define("TYPE_ROOT", APP_ROOT . "/includes/exporter/filetypes/");

require_once APP_ROOT . "/includes/exporter/class.exportfiletype.php";

/**
* A factory class to get a list of available file types and to get a file type object
*
* @author Ray Ward <ray.ward@interspire.com>
*/
class ISC_ADMIN_EXPORTFILETYPE_FACTORY
{
	/**
	* Gets an Export File Type object
	*
	* @return ISC_ADMIN_EXPORTFILETYPE An Export File Type object or FALSE is type not found
	*/
	public static function GetExportFileType($type)
	{	//, $templateid, $where
		$file = TYPE_ROOT . $type . ".php";

		if (is_file($file)) {
			require_once $file;

			$className = "ISC_ADMIN_EXPORTFILETYPE_" . $type;

			return new $className;
		}

		return false;
	}

	/**
	* Gets a list of available export file types
	*
	* @return array An array of available file types in the format: filetype => type_details_array[]
	*/
	public static function GetExportFileTypeList()
	{
		$files = scandir(TYPE_ROOT);

		$types = array();

		foreach($files as $file) {
			if(!is_file(TYPE_ROOT . $file) || isc_substr($file, -3) != "php") {
				continue;
			}

			require_once TYPE_ROOT . $file;

			$file = isc_substr($file, 0, isc_strlen($file) - 4);
			/*
			$pos = isc_strrpos($file, ".");
			$typeName = isc_strtoupper(isc_substr($file, $pos + 1));
			*/
			$className = "ISC_ADMIN_EXPORTFILETYPE_" . strtoupper($file); //$typeName;
			if(!class_exists($className)) {
				continue;
			}

			$obj = new $className;
			if (!$obj->ignore) {
				$types[$file] = $obj->GetTypeDetails();
			}
		}

		return $types;
	}
}