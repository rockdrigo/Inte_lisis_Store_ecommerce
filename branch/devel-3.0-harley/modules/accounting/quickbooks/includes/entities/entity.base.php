<?php

abstract class ACCOUNTING_QUICKBOOKS_ENTITY_BASE
{
	protected $spool;
	protected $accounting;
	protected $spoolNodeData;
	protected $spoolReference;
	protected $xmlWriter;

	/**
	 * Constructor
	 *
	 * Base constructor
	 *
	 * @access public
	 * @param array $spool The formatted spool array that we are working with
	 * @param object $accounting The accounting object
	 * @param bool $convertSpool TRUE to convert the $spool array to UTF-8, FASLE not to. Default is TRUE
	 * @return void
	 */
	public function __construct($spool, $accounting, $convertSpool=true)
	{
		if ($convertSpool) {
			$spool = $this->convert2UTF8($spool);
		}

		$this->spool = $spool;
		$this->accounting = $accounting;
		$this->xmlWriter = new XMLWriter();
		$this->xmlWriter->startDocument("1.0", "utf-8");
		$this->xmlWriter->openMemory();

		/**
		 * These are defined just for convenience
		 */
		$this->spoolNodeData = $this->spool["nodeData"];
		$this->spoolReferenceData = $this->spool["referenceData"];

		if (!is_array($this->spoolReferenceData) || empty($this->spoolReferenceData)) {
			$this->spoolReferenceData = false;
		}
	}

	/**
	 * Convert string or array of string to UTF-8
	 *
	 * Method will convert $str which is either a string or array of string from $fromEncoding to UTF-8. If $fromEncoding
	 * is empty then use the output from GetConfig("dbEncoding")
	 *
	 * @access protected
	 * @param mixed $str The string or array of strings to convert
	 * @param string $fromEncoding The optional from encoding. Default is GetConfig("dbEncoding")
	 * @return mixed The converted string or array of strings
	 */
	protected function convert2UTF8($str, $fromEncoding='')
	{
		if (trim($str) == '') {
			return $str;
		}

		if (trim($fromEncoding) == '') {
			$fromEncoding = GetConfig("dbEncoding");
		}

		if (trim($fromEncoding) == '') {
			return $str;
		}

		/**
		 * Convert the $fromEncoding as mb_convert_encoding doesn't understand MySQL char encoding names. Just look
		 * for the most common encodings
		 */
		switch (isc_strtolower($fromEncoding)) {
			case "utf8":
				$fromEncoding = "utf-8";
				break;

			case "latin1":
				$fromEncoding = "iso-8859-1";
				break;

			case "latin2":
				$fromEncoding = "iso-8859-2";
				break;
		}

		return isc_convert_charset($fromEncoding, "utf-8", $str);
	}

	/**
	 * Escape and write an XML node element
	 *
	 * Method will write an XML node element. Method will take care of encoding and truncating as QBWC does not
	 * fully understand CDATA
	 *
	 * @access protected
	 * @param string $name The XML node name
	 * @param string $value The XML node value
	 * @param int $maxLength The optional maximum length of the value. Default is 0 (unlimited)
	 * @return bool TRUE if the node was created, FALSE on error
	 */
	protected function writeEscapedElement($name, $value, $maxLength=0)
	{
		if (trim($name) == "") {
			return false;
		}

		$value = $this->accounting->escapeXMLNodeValue($value, $maxLength);

		return $this->writeRawElement($name, $value, 0);
	}

	/**
	 * Write an XML node element with raw value
	 *
	 * Method will write an XML node element WITHOUT encoding the value. PLEASE BE CAREFULL!!!
	 *
	 * @access protected
	 * @param string $name The XML node name
	 * @param string $value The XML node value
	 * @param int $maxLength The optional maximum length of the value. Default is 0 (unlimited)
	 * @return bool TRUE if the node was created, FALSE on error
	 */
	protected function writeRawElement($name, $value, $maxLength=0)
	{
		if (trim($name) == "") {
			return false;
		}

		if ($maxLength > 0) {
			$value = isc_substr($value, 0, $maxLength);
		}

		$this->xmlWriter->startElement($name);
		$this->xmlWriter->writeRaw($value);
		$this->xmlWriter->endElement();

		return true;
	}

	/**
	 * Build the address block
	 *
	 * Method will build the address XML block
	 *
	 * @access protected
	 * @param string $xmlTagName The parent address XML tag name
	 * @param array $address The address array
	 * @return bool TRUE if the address block was built, FALSE on error
	 */
	protected function buildAddressBlock($xmlTagName, $address)
	{
		if (!is_string($xmlTagName) || trim($xmlTagName) == '' || !is_array($address)) {
			return false;
		}

		$this->xmlWriter->startElement($xmlTagName);
		$this->writeEscapedElement("Addr1", $address["shipfirstname"] . ' ' . $address["shiplastname"], 41);
		$this->writeEscapedElement("Addr2", $address["shipaddress1"], 41);
		$this->writeEscapedElement("Addr3", $address["shipaddress2"], 41);
		$this->writeEscapedElement("City", $address["shipcity"], 31);

		if (!$this->compareClientCountry("uk")) {
			$this->writeEscapedElement("State", $address["shipstate"], 21);
		} else {
			$this->writeEscapedElement("County", $address["shipstate"], 21);
		}

		$this->writeEscapedElement("PostalCode", $address["shipzip"], 13);
		if($address["shipcountry"] != 'United States'){
			$this->writeEscapedElement("Country", $address["shipcountry"], 31);
		}
		$this->xmlWriter->endElement();
		return true;
	}

	/**
	 * Build the XML output
	 *
	 * Method will build the XML output
	 *
	 * @access protected
	 * @param bool $isQuery TRUE if this service is a query, FALSE if not. Default is FALSE
	 * @return string The XML output on success, throw an error on anything else
	 */
	protected function buildOutput($isQuery=false, $overrideRealService='')
	{
		$realService = $this->spool["realService"];

		if (trim($overrideRealService) !== '') {
			$realService = $overrideRealService;
		}

		$GLOBALS["VersionNo"] = $this->getVersionNo();
		$GLOBALS["ServiceString"] = $realService;
		$GLOBALS["XMLString"] = $this->xmlWriter->outputMemory(true);

		if ($isQuery) {
			$template = "qbxml.query";
		} else {
			$template = "qbxml";
		}

		return $this->accounting->ParseTemplate($template, true);
	}

	/**
	 * Build the XML output using empty XML template
	 *
	 * Method will build the XML output using empty XML template
	 *
	 * @access protected
	 * @return string The XML output on success, throw an error on anything else
	 */
	protected function buildOutputEmpty()
	{
		$GLOBALS["VersionNo"] = $this->getVersionNo();
		$GLOBALS["XMLString"] = $this->xmlWriter->outputMemory(true);

		return $this->accounting->ParseTemplate("qbxml.empty", true);
	}

	/**
	 * Get the XML version no
	 *
	 * Method will return the XML version no
	 *
	 * @access private
	 * @return string The XML version no
	 */
	private function getVersionNo()
	{
		$clientVersion = $this->getCompanySessionData("QBXML_VERSION");
		$clientCountry = $this->getCompanySessionData("CLIENT_COUNTRY");

		/**
		 * If this version 2-3 and where are UK/CA then we need to prepend the country code in the version
		 */
		if ((isc_strtolower($clientCountry) == "uk" || isc_strtolower($clientCountry) == "ca") && version_compare($clientVersion, "3.0") !== 1) {
			$versionNo = isc_strtoupper($clientCountry) . $clientVersion;
		} else {
			$versionNo = number_format((float)$clientVersion, 1);
		}

		return $versionNo;
	}

	/**
	 * Get a value out of the company session dtaa
	 *
	 * Method will return the value that is referenced by the array key $key in the company session data
	 *
	 * @access private
	 * @param string $key The company session data key
	 * @return mixed The value if $key exsist in the company session data array, FALSE if not
	 */
	private function getCompanySessionData($key)
	{
		if (trim($key) == '') {
			return false;
		}

		$companySessData = $this->accounting->getImportSessionValue("CompanyData");

		if (!is_array($companySessData) || !array_key_exists($key, $companySessData)) {
			return false;
		}

		return $companySessData[$key];
	}

	/**
	 * Compare qbXML version number to the version number being used
	 *
	 * Method will compare the version number $versionNo to the current qbXML version number being used
	 *
	 * @access protected
	 * @param float $versionNo The version number to compare against
	 * @return bool TRUE if the version number $versionNo is higher or equal to the current qbXML version number
	 */
	protected function compareClientVersion($versionNo)
	{
		if (trim($versionNo) == '') {
			return false;
		}

		$currentVersionNo = $this->getCompanySessionData("QBXML_VERSION");

		if (trim($currentVersionNo) == '') {
			return false;
		}

		return (version_compare($versionNo, $currentVersionNo) !== 1);
	}

	/**
	 * Compare qbXML country version to the country version being used
	 *
	 * Method will compare the country version $country to the current country version being used
	 *
	 * @access protected
	 * @param string $country The country version to compare against
	 * @return bool TRUE if the country version $country is the same as the current country version
	 */
	protected function compareClientCountry($country)
	{
		if (trim($country) == '' || strlen($country) == 2) {
			return false;
		}

		$currentCountry = $this->getCompanySessionData("CLIENT_COUNTRY");

		if (trim($currentCountry) == '' || (string)$country == (string)$currentCountry) {
			return false;
		}

		return true;
	}
}

