<?php
require_once dirname(__FILE__) . "/../classes/class.ajaxexporter.php";

/**
* Abstract class that represents an export method. An export method reads data from an ISC_ADMIN_EXPORTFILETYPE object and writes it to a file.*
*
* @author Ray Ward <ray.ward@interspire.com>
*/
abstract class ISC_ADMIN_EXPORTMETHOD extends ISC_ADMIN_AJAXEXPORTER
{
	protected $file;	// file path

	protected $filetype;			// the file type being exported by this method

	protected $method_name; 		// the name of this particular export method
	protected $method_icon;			// the icon shown next to the method name
	protected $method_help; 		// tool tip displayed when exported
	protected $method_extension; 	// the file extension to use
	protected $method_title = "";

	protected $type_name;

	public $settings = array();

	protected $has_settings = false;

	public function __construct()
	{
		// load language for this method
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('export.method.' . strtolower($this->method_name));

		$this->method_help = GetLang("MethodHelp");
		$this->method_title = GetLang("MethodTitle");

		parent::__construct();
	}

	/**
	* Initialises this method for exporting
	*
	* @param ISC_ADMIN_EXPORTOPTIONS The options to export with
	*/
	public function Init(ISC_ADMIN_EXPORTOPTIONS $options)
	{
		$filetype = $options->getFileType();

		// initialise the file type
		$filetype->Init($this, $options->getTemplateId(), $options->getWhere(), $options->getHaving());

		// set the type name
		$details = $filetype->GetTypeDetails();
		$name = $details['name'];
		if (substr($name, -1, 1) == "s") {
			$name = substr($name, 0, -1);
		}
		$this->type_name = $name;

		$this->filetype = $filetype;

		// load settings for this method
		$settings = $this->GetSettings($options->getTemplateId());
		foreach ($settings as $var => $setting) {
			$this->settings[$var] = $setting['value'];
		}

		$this->className = 'exporttemplate';
		$this->exportName = $details['title'];
		$GLOBALS['ExportName'] = $details['title'];
		$GLOBALS['ExportGenerate'] = GetLang('AjaxExportLink', array('title' => isc_strtolower($details['title']), 'type' => $this->method_name));
		$GLOBALS['ExportIntro'] = GetLang('AjaxExportIntro');
	}

	/**
	* Gets an array of details about the export method
	*
	* @return array Method Details
	*/
	public function GetMethodDetails()
	{
		$details = array(
			"name" 		=> $this->method_name,
			"icon" 		=> $this->method_icon,
			"help" 		=> $this->method_help,
			"extension"	=> $this->method_extension,
			"title"		=> $this->method_title
		);

		return $details;
	}

	/**
	* Gets the path of the file created for the export.
	*
	* @return string The exported file
	*/
	public function GetFile()
	{
		return $this->file;
	}

	protected function GetResult($generateFull = false, $start = 0)
	{
		$query = $this->filetype->query;
		$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($start, ISC_AJAX_EXPORT_PER_PAGE);

		return $GLOBALS['ISC_CLASS_DB']->Query($query);
	}

	protected function GetResultCount()
	{
		$query = $this->filetype->countquery;
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		return $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
	}

	protected function WriteRows($result)
	{
		// Export the rows
		return $this->filetype->ExportRows($result);
	}

	protected function GetExportFileName()
	{
		$details = $this->filetype->GetTypeDetails();

		return $details['name'] . "-" . isc_date("Y-m-d") . "." . $this->method_extension;
	}

	/**
	* Abstract method to write any footer data at the end of the file
	*
	*/


	/**
	* Abstract method that returns an array that defines the setting this method uses.
	*
	* @example method_settings.inc An example to define the settings
	*
	* @param int The template to load data into the settings from.
	*/
	abstract public function GetSettings($templateid);

	/**
	* Loads data for a template's settings
	*
	* @param array The array of settings
	* @param int The template to load the setting data from
	*/
	protected function LoadSettingData(&$settings, $templateid)
	{
		$query = "SELECT * FROM [|PREFIX|]export_method_settings WHERE exporttemplateid = '" . $GLOBALS['ISC_CLASS_DB']->Quote($templateid) . "'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$varname = $row['variablename'];
			$settings[$varname]['value'] = $row['variablevalue'];
		}
	}

	public function HasSettings()
	{
		return $this->has_settings;
	}
}