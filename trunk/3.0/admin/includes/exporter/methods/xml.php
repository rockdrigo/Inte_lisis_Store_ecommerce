<?php
require_once dirname(__FILE__) . "/../class.exportmethod.php";

class ISC_ADMIN_EXPORTMETHOD_XML extends ISC_ADMIN_EXPORTMETHOD
{
	protected $method_name = "XML"; // the name of this particular export method
	protected $method_icon = "exportXml.gif";	// the icon shown next to the method name
	protected $method_extension = "xml";

	protected $headers;

	protected $fields;

	protected $clean_names = array();

	public function __construct()
	{
		parent::__construct();
	}

	public function Init(ISC_ADMIN_EXPORTOPTIONS $options)
	{
		parent::Init($options);

		$headers = $this->filetype->GetHeaders(true, false);

		$this->headers = $headers;

		$this->fields = $this->filetype->FlattenFields();
	}

	// write the header
	protected function WriteHeader()
	{
		$data = "<?xml version=\"1.0\" encoding=\"" . GetConfig("CharacterSet") . "\"?>\n";
		$data .= "<" . $this->type_name . "s>\n";

		fwrite($this->handle, $data);
	}

	public function WriteRow($row)
	{
		$data = "\t<" . $this->type_name . ">\n";

		foreach ($row as $column => $value) {
			if (isset($this->fields[$column]) && isset($this->fields[$column]['ignore']) && $this->fields[$column]['ignore']) {
				continue;
			}

			$column = $this->GetColumn($column);

			if (is_array($value)) {
				$data .= "\t\t<" . $column . ">\n";

				foreach ($value as $subitem) {
					$data .= "\t\t\t<item>\n";

					foreach ($subitem as $subcol => $subval) {
						if (isset($this->fields[$subcol]) && isset($this->fields[$subcol]['ignore']) && $this->fields[$subcol]['ignore']) {
							continue;
						}

						$subcol = $this->GetColumn($subcol);

						$data .= "\t\t\t\t<" . $subcol . "><![CDATA[" . $subval . "]]></" . $subcol . ">\n";
					}

					$data .= "\t\t\t</item>\n";
				}

				$data .= "\t\t</" . $column . ">\n";
			}
			else {
				$data .= "\t\t<" . $column . "><![CDATA[" . $value . "]]></" . $column . ">\n";
			}
		}

		$data .= "\t</" . $this->type_name . ">\n";

		fwrite($this->handle, $data);
	}

	// write closing XML tags
	protected function WriteFooter()
	{
		$data = "</" . $this->type_name . "s>";

		fwrite($this->handle, $data);
	}

	public function GetSettings($templateid)
	{
		return array();
	}

	private function GetColumn($column)
	{
		// is this column cached? return it.
		if (isset($this->clean_names[$column])) {
			return $this->clean_names[$column];
		}

		$output = $column;
		if (isset($this->headers[$column])) {
			$output = $this->headers[$column];
		}

		// replace whitespace with underscore
		$output = preg_replace("/\s+/", "_", $output);

		//names starting with a digit must be prepended by an underscore
		if (preg_match("/^\d/", $output)) {
			$output = "_" . $output;
		}

		// replace bad characters with blank
		$output = preg_replace("/([^A-Z\xco-\xff0-9_:.\-]+)/i", "", $output);

		$this->clean_names[$column] = $output;

		return $output;
	}
}