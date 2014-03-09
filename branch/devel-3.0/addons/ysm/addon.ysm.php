<?php

	/**
	* Yahoo Search Marketing addon for Interspire Shopping Cart
	* The Yahoo Search Marketing addon allow you to automatically generate YSM ads for all products
	* in your store, complete with unique URLs, product titles and ad text. These can then be imported
	* into Yahoo Search Marketing to advertise your store alongside Yahoo search results
	*
	* @author: Mitchell Harper
	* @copyright: Interspire Pty. Ltd.
	* @date: 25th January 2008
	*/

	require_once(dirname(__FILE__) . '/../../includes/classes/class.addon.php');

	class ADDON_YSM extends ISC_ADDON
	{

		/*
			The header fields for the CSV file and how post variables map to them
		*/
		private $_fields = array(
							 "Campaign Name" => "",
							 "Ad Group Name" => "",
							 "Component Type" => "",
							 "Component Status" => "",
							 "Keyword" => "",
							 "Keyword Alt Text" => "",
							 "Keyword Custom URL" => "",
							 "Sponsored Search Bid (USD)" => "",
							 "Sponsored Search Bid Limit (USD)" => "",
							 "Sponsored Search Status" => "",
							 "Match Type" => "",
							 "Content Match Bid (USD)" => "",
							 "Content Match Bid Limit (USD)" => "",
							 "Content Match Status" => "",
							 "Ad Name" => "",
							 "Ad Title" => "title",
							 "Ad Short Description" => "desc1",
							 "Ad Long Description" => "desc2",
							 "Display URL" => "displayurl",
							 "Destination URL" => "destinationurl",
							 "Watch List" => "",
							 "Campaign ID" => "",
							 "Campaign Description" => "",
							 "Campaign Start Date" => "",
							 "Campaign End Date" => "",
							 "Ad Group ID" => "",
							 "Ad Group: Optimize Ad Display" => "",
							 "Ad ID" => "",
							 "Keyword ID" => "",
							 "Checksum" => "",
							 "Error Message" => ""
		);

		/*
			The placeholder tokens to replace with product details
		*/
		private $_tokens = array("{PRODNAME}" => "prodname",
							 "{PRODBRAND}" => "prodbrand",
							 "{PRODSUMMARY}" => "prodsummary",
							 "{PRODPRICE}" => "prodprice",
							 "{PRODSKU}" => "prodsku",
							 "{PRODCAT}" => "prodcat",
							 "{PRODLINK}" => "[AUTO]",
							 "{STORENAME}" => "[AUTO]"
		);

		/*
			Is content match selected?
		*/
		private $_contentmatch = false;

		/*
			The maximum CPC per ad
		*/
		private $_maxcpc = 0;

		/**
		* Constructor
		* Setup the addon-specific variables through the addon parent class
		*/
		public function __construct()
		{

			// Call all standard addon functions
			$this->SetId('addon_ysm');
			$this->SetName('Yahoo Search Marketing');
			$this->LoadLanguageFile();

			$this->RegisterMenuItem(array(
				'location' => 'mnuMarketing',
				'text' => GetLang('YSMMenuText'),
				'description' => GetLang('YSMMenuDescription'),
				'id' => 'addon_ysm'
			));

			$this->SetImage('logo.gif');
		}

		/**
		* Init
		* Initialize any other addon-specific code that needs to run
		*/
		public function init()
		{
			$this->SetHelpText(GetLang('YSMHelpText'));
			$this->ShowSaveAndCancelButtons(false);

			// Has content match been ticked?
			if(isset($_POST['contentmatch'])) {
				$this->_contentmatch = true;
			}

			// What's the maximum CPC?
			if(isset($_POST['maxcpc'])) {
				$this->_maxcpc = FormatPrice($_POST['maxcpc'], false, false, true);
			}
		}


		/**
		* EntryPoint
		* Start by collecting a few options for generating the AdWords ads
		*
		* @return Void
		*/
		public function EntryPoint()
		{

			$this->init();

			$GLOBALS['HTTPHost'] = $_SERVER['HTTP_HOST'];
			$GLOBALS['ISC_CLASS_ADMIN_CATEGORY'] = GetClass("ISC_ADMIN_CATEGORY");
			$GLOBALS['CategoryOptions'] = $GLOBALS['ISC_CLASS_ADMIN_CATEGORY']->GetCategoryOptions(0, "<option %s value='%d'>%s</option>", "selected=\"selected\"", "", false);
			$GLOBALS['SamplePrice'] = FormatPrice(199);
			$this->ParseTemplate('ysm.form');
		}

		/**
		* _GetFields
		* Get the list of field headers for the CSV file
		*
		* @return Array
		*/
		private function _GetFields()
		{
			return $this->_fields;
		}

		/**
		* _GetTokens
		* Get the list of placeholder tokens
		*
		* @return Array
		*/
		private function _GetTokens()
		{
			return $this->_tokens;
		}

		/**
		* ExportCSV
		* Grab all products and create the CSV file to output
		*
		* @return Void
		*/
		public function ExportCSV()
		{

			$this->init();

			$cat_ids = "";
			$csv = "";

			if(isset($_POST['category']) && isset($_POST['title']) && isset($_POST['desc1']) && isset($_POST['desc2']) && isset($_POST['displayurl']) && isset($_POST['destinationurl'])) {

				$all_fields = $_POST['title'] . $_POST['desc1'] . $_POST['desc2'] . $_POST['displayurl'] . $_POST['destinationurl'];

				if(count($_POST['category']) == 1 && in_array(0, $_POST['category'])) {
					// Export all products
				}
				else {
					// Only export the selected categories
					foreach($_POST['category'] as $cat_id) {
						if($cat_id != 0) {
							$cat_ids .= $cat_id . ",";
						}
					}

					$cat_ids = rtrim($cat_ids, ",");
				}

				$query = "select p.productid, p.prodname, p.tax_class_id ";

				// Do we need to get the product's brand?
				if(is_numeric(isc_strpos($all_fields, "{PRODBRAND}"))) {
					$query .= "(select brandname from [|PREFIX|]brands where brandid=p.prodbrandid) as prodbrand";
				}

				// Do we need to get the product's summary?
				if(is_numeric(isc_strpos($all_fields, "{PRODSUMMARY}"))) {
					//$query .= "substring(proddesc from 1 for 100) as prodsummary, ";
					$query .= ", proddesc as prodsummary ";
				}

				// Do we need to get the product's price?
				if(is_numeric(isc_strpos($all_fields, "{PRODPRICE}"))) {
					$query .= ", p.prodcalculatedprice as prodprice ";
				}

				// Do we need to get the product's SKU?
				if(is_numeric(isc_strpos($all_fields, "{PRODSKU}"))) {
					$query .= ", p.prodcode as prodsku ";
				}

				// Do we need to get the product's category?
				if(is_numeric(isc_strpos($all_fields, "{PRODCAT}"))) {
					$query .= "(select catname from [|PREFIX|]categoryassociations ca inner join [|PREFIX|]categories c on ca.categoryid=c.categoryid where ca.productid=p.productid limit 1) as prodcat ";
				}

				$cat_ids = rtrim($cat_ids, ", ");
				$query .= " from [|PREFIX|]products p ";

				// Do we need to filter on category?
				if($cat_ids != "") {
					$query .= sprintf("inner join [|PREFIX|]categoryassociations ca on p.productid=ca.productid where ca.categoryid in (%s)", $cat_ids);
				}

				// Build the headers for the CSV file
				$csv .= $this->_HeaderRow();

				// Build the campaign row
				$csv .= $this->_CampaignRow();

				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$csv .= $this->_CreateRecord($row);
				}
				// Flush the buffer
				ob_end_clean();

				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Cache-Control: private", false);
				header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				header("Content-Type: application/download");
				header("Content-Disposition: attachment; filename=\"ysm-".isc_date("Y-m-d").".csv\";");
				header("Content-Length: " . strlen($csv));
				echo $csv;

				// Let the parent class know the addon's just been executed
				parent::LogAction();

				exit;
			}
			else {
				// Bad form details
				$GLOBALS['ErrorTitle'] = GetLang('Oops');
				$GLOBALS['Message'] = MessageBox(GetLang('YSMBadFormDetails'), MSG_ERROR);
				$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("error");
				$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
			}
		}

		/**
		* _HeaderRow
		* Build the header row for the CSV file which contains a list of fields
		*
		* @return String
		*/
		private function _HeaderRow()
		{
			$header = "";

			foreach($this->_GetFields() as $csv_field => $post_field) {
				$header .= $csv_field . EXPORT_FIELD_SEPARATOR;
			}

			$header = preg_replace('/' . EXPORT_FIELD_SEPARATOR . '$/', '', $header);
			$header .= EXPORT_RECORD_SEPARATOR;

			return $header;
		}

		/**
		* _IsContentMatch
		* Was the content match checkbox ticked?
		*
		* @return Boolean
		*/
		private function _IsContentMatch()
		{
			return $this->_contentmatch;
		}

		/**
		* _CampaignRow
		* Build the campaign row under which all ads will appear grouped in YSM
		*
		* @return String
		*/
		private function _CampaignRow()
		{
			$campaign = GetConfig('StoreName') . EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= "Campaign" . EXPORT_FIELD_SEPARATOR;
			$campaign .= "On" . EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= "On" . EXPORT_FIELD_SEPARATOR;
			$campaign .= "Advanced" . EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;

			if($this->_IsContentMatch()) {
				$campaign .= "On";
			}

			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= "Off" . EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= "Ads for " . GetConfig('StoreName') . EXPORT_FIELD_SEPARATOR;
			$campaign .= date("m/d/Y") . EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;
			$campaign .= EXPORT_FIELD_SEPARATOR;

			$campaign .= EXPORT_RECORD_SEPARATOR;

			return $campaign;
		}

		/**
		* _GetMaxCPC
		* Get the maximum CPC
		*
		* @return Float
		*/
		private function _GetMaxCPC()
		{
			return $this->_maxcpc;
		}

		/**
		* _Strip
		* Format text so it doesn't mess up the CSV file's format
		*
		* @param String $Val The string to format
		* @return String
		*/
		private function _Strip($Val)
		{
			$Val = str_replace("&quot;", "'", $Val);
			$Val = str_replace(",", " ", $Val);
			$Val = str_replace("\"", " " . GetLang("YSMInch"), $Val);
			return $Val;
		}

		/**
		* _CreateAdGroup
		* Create the ad group row for an ad
		*
		* @param Array $Data A reference to the product row
		* @return String
		*/
		private function _CreateAdGroup(&$Data)
		{
			$adgroup = GetConfig('StoreName') . EXPORT_FIELD_SEPARATOR;
			$adgroup .= $this->_Strip($Data['prodname']) . EXPORT_FIELD_SEPARATOR;
			$adgroup .= "Ad Group" . EXPORT_FIELD_SEPARATOR;
			$adgroup .= "On" . EXPORT_FIELD_SEPARATOR;
			$adgroup .= EXPORT_FIELD_SEPARATOR;
			$adgroup .= EXPORT_FIELD_SEPARATOR;
			$adgroup .= EXPORT_FIELD_SEPARATOR;
			$adgroup .= $this->_GetMaxCPC() . EXPORT_FIELD_SEPARATOR;
			$adgroup .= EXPORT_FIELD_SEPARATOR;
			$adgroup .= "On" . EXPORT_FIELD_SEPARATOR;
			$adgroup .= "Advanced" . EXPORT_FIELD_SEPARATOR;
			$adgroup .= "Default" . EXPORT_FIELD_SEPARATOR;
			$adgroup .= EXPORT_FIELD_SEPARATOR;

			if($this->_IsContentMatch()) {
				$campaign .= "On";
			}

			$adgroup .= EXPORT_FIELD_SEPARATOR;
			$adgroup .= EXPORT_FIELD_SEPARATOR;
			$adgroup .= EXPORT_FIELD_SEPARATOR;
			$adgroup .= EXPORT_FIELD_SEPARATOR;
			$adgroup .= EXPORT_FIELD_SEPARATOR;
			$adgroup .= EXPORT_FIELD_SEPARATOR;
			$adgroup .= EXPORT_FIELD_SEPARATOR;
			$adgroup .= "Off" . EXPORT_FIELD_SEPARATOR;
			$adgroup .= EXPORT_FIELD_SEPARATOR;
			$adgroup .= EXPORT_FIELD_SEPARATOR;
			$adgroup .= EXPORT_FIELD_SEPARATOR;
			$adgroup .= EXPORT_FIELD_SEPARATOR;
			$adgroup .= EXPORT_FIELD_SEPARATOR;
			$adgroup .= "On" . EXPORT_FIELD_SEPARATOR;
			$adgroup .= EXPORT_FIELD_SEPARATOR;
			$adgroup .= EXPORT_FIELD_SEPARATOR;
			$adgroup .= EXPORT_FIELD_SEPARATOR;

			$adgroup .= EXPORT_RECORD_SEPARATOR;

			return $adgroup;
		}

		/**
		* _CreateRecord
		* Create an entry in the CSV file and return it
		*
		* @param Array $Data A reference to the product row
		* @return String
		*/
		private function _CreateRecord(&$Data)
		{
			$row = "";

			// Create the ad group for the ad
			$row .= $this->_CreateAdGroup($Data);

			foreach($this->_GetFields() as $csv_field => $post_field) {
				if(isset($_POST[$post_field])) {
					$data = $_POST[$post_field];
				}
				else {
					$data = "{" . $csv_field . "}";
				}
				$row .= EXPORT_FIELD_ENCLOSURE . $data . EXPORT_FIELD_ENCLOSURE . EXPORT_FIELD_SEPARATOR;
			}

			// Replace tokens out of the row
			$row = $this->_ReplaceTokens($row, $Data);
			$row = rtrim($row, EXPORT_FIELD_SEPARATOR);
			$row .= EXPORT_RECORD_SEPARATOR;

			// Create the keyword row for the ad
			$row .= $this->_CreateKeyword($Data);

			return $row;
		}

		/**
		* _CreateKeyword
		* Create a row for the ads keyword, which is the product name
		*
		* @param Array $Data A reference to the product row
		* @return String
		*/
		private function _CreateKeyword(&$Data)
		{
			$keyword = GetConfig('StoreName') . EXPORT_FIELD_SEPARATOR;
			$keyword .= $this->_Strip($Data['prodname']) . EXPORT_FIELD_SEPARATOR;
			$keyword .= "Keyword" . EXPORT_FIELD_SEPARATOR;
			$keyword .= "On" . EXPORT_FIELD_SEPARATOR;
			$keyword .= $this->_Strip($Data['prodname']) . EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= "Default" . EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= "Advanced" . EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= "Off" . EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;
			$keyword .= EXPORT_FIELD_SEPARATOR;

			$keyword .= EXPORT_RECORD_SEPARATOR;

			return $keyword;
		}

		/**
		* _ReplaceTokens
		* Replace the placeholder tokens with values from the database
		*
		* @param String $row The row from the CSV file
		* @param Array $Data A reference to the database row for the product
		* @return String
		*/
		private function _ReplaceTokens($Row, &$Data)
		{

			$tokens = $this->_GetTokens();

			foreach($this->_GetTokens() as $token => $val) {
				if(isset($Data[$val]) || $token == "{PRODLINK}" || $token == "{STORENAME}") {
					switch($token) {
						case "{PRODSUMMARY}": {
							$Data[$val] = $this->_Strip(strip_tags($Data[$val]));

							if(strlen($Data[$val]) > 32) {
								$Data[$val] = isc_substr($Data[$val], 0, 32) . "...";
							}

							$Data[$val] = trim($Data[$val]);
							$Data[$val] = str_replace("\n", "", $Data[$val]);
							$Data[$val] = str_replace("\r", "", $Data[$val]);
							$Data[$val] = str_replace("\t", " ", $Data[$val]);
							break;
						}
						case "{PRODPRICE}": {
							$price = getClass('ISC_TAX')->getPrice($Data[$val], $Data['tax_class_id'], getConfig('taxDefaultTaxDisplayProducts'));
							$Data[$val] = FormatPrice($price, false, true);
							break;
						}
						case "{PRODLINK}": {
							$Data[$val] = ProdLink($Data['prodname']);
							break;
						}
						case "{STORENAME}": {
							$Data[$val] = GetConfig("StoreName");
							break;
						}
					}

					// Replace the value from the row
					$Row = str_replace($token, $Data[$val], $Row);
				}
				else {
					// Replace the value with nothing
					$Row = str_replace($token, "", $Row);
				}
			}

			$Row = str_replace("{Campaign Name}", GetConfig('StoreName'), $Row);
			$Row = str_replace("{Ad Group Name}", $this->_Strip($Data['prodname']), $Row);
			$Row = str_replace("{Component Type}", "Ad", $Row);
			$Row = str_replace("{Component Status}", "On", $Row);
			$Row = str_replace("{Keyword}", "", $Row);
			$Row = str_replace("{Keyword Alt Text}", "", $Row);
			$Row = str_replace("{Keyword Custom URL}", "", $Row);
			$Row = str_replace("{Sponsored Search Bid (USD)}", "", $Row);
			$Row = str_replace("{Sponsored Search Bid Limit (USD)}", "", $Row);
			$Row = str_replace("{Sponsored Search Status}", "", $Row);
			$Row = str_replace("{Match Type}", "", $Row);
			$Row = str_replace("{Content Match Bid (USD)}", "", $Row);
			$Row = str_replace("{Content Match Bid Limit (USD)}", "", $Row);
			$Row = str_replace("{Content Match Status}", "", $Row);
			$Row = str_replace("{Ad Name}", $this->_BuildAdName($Data['prodname']), $Row);
			$Row = str_replace("{Watch List}", "", $Row);
			$Row = str_replace("{Campaign ID}", "", $Row);
			$Row = str_replace("{Campaign Description}", "", $Row);
			$Row = str_replace("{Campaign Start Date}", "", $Row);
			$Row = str_replace("{Campaign End Date}", "", $Row);
			$Row = str_replace("{Ad Group ID}", "", $Row);
			$Row = str_replace("{Ad Group: Optimize Ad Display}", "", $Row);
			$Row = str_replace("{Ad ID}", "", $Row);
			$Row = str_replace("{Keyword ID}", "", $Row);
			$Row = str_replace("{Checksum}", "", $Row);
			$Row = str_replace("{Error Message}", "", $Row);

			// Run one final trim
			$Row = trim($Row);

			// Return the row
			return $Row;
		}

		/**
		* _BuildAdName
		* Build an ad name/id based on the product name
		*
		* @param String $ProdName The product's name
		* @return String
		*/
		private function _BuildAdName($ProdName)
		{
			return GetLang("YSMAdFor") . " " . $ProdName;
		}
	}