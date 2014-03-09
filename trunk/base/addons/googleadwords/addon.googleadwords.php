<?php

	/**
	* Google AdWords addon for Interspire Shopping Cart
	* The Google AdWords addon allow you to automatically generate Google AdWords ads for all products
	* in your store, complete with unique URLs, product titles and ad text. These can then be imported
	* into Google AdWords to advertise your store alongside Google search results
	*
	* @author: Mitchell Harper
	* @copyright: Interspire Pty. Ltd.
	* @date: 15th January 2008
	*/

	require_once(dirname(__FILE__) . '/../../includes/classes/class.addon.php');

	class ADDON_GOOGLEADWORDS extends ISC_ADDON
	{

		/*
			The header fields for the CSV file
		*/
		private $_fields = array("title", "desc1", "desc2", "displayurl", "destinationurl");

		/*
			The placeholder tokens to replace with product details
		*/
		private $_tokens = array("{PRODNAME}" => "prodname",
							 "{PRODBRAND}" => "prodbrand",
							 "{PRODSUMMARY}" => "prodsummary",
							 "{PRODPRICE}" => "prodprice",
							 "{PRODSKU}" => "prodsku",
							 "{PRODCAT}" => "prodcat",
							 "{PRODLINK}" => "[AUTO]"
		);

		/**
		* Constructor
		* Setup the addon-specific variables through the addon parent class
		*/
		public function __construct()
		{
			$this->SetId('addon_googleadwords');
			$this->SetName('Google AdWords');
			$this->LoadLanguageFile();

			$this->RegisterMenuItem(array(
				'location' => 'mnuMarketing',
				'text' => GetLang('GoogleAdWordsMenuText'),
				'description' => GetLang('GoogleAdWordsMenuDescription'),
				'id' => 'addon_googleadwords'
			));

			$this->SetImage('logo.gif');
			$this->SetHelpText(GetLang('GoogleAdWordsHelpText'));
		}

		/**
		* Init
		* Initialize any other addon-specific code that needs to run
		*/
		public function init()
		{
			$this->ShowSaveAndCancelButtons(false);
		}

		/**
		* EntryPoint
		* Start by collecting a few options for generating the AdWords ads
		*
		* @return Void
		*/
		public function EntryPoint()
		{
			$GLOBALS['HTTPHost'] = $_SERVER['HTTP_HOST'];
			$GLOBALS['ISC_CLASS_ADMIN_CATEGORY'] = GetClass('ISC_ADMIN_CATEGORY');
			$GLOBALS['CategoryOptions'] = $GLOBALS['ISC_CLASS_ADMIN_CATEGORY']->GetCategoryOptions(0, "<option %s value='%d'>%s</option>", "selected=\"selected\"", "", false);
			$this->ParseTemplate('googleadwords.form');
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

			$cat_ids = "";

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

				$query = "select p.productid, p.tax_class_id, ";

				// Do we need to get the product's name?
				if(is_numeric(isc_strpos($all_fields, "{PRODNAME}"))) {
					$query .= "p.prodname, ";
				}

				// Do we need to get the product's brand?
				if(is_numeric(isc_strpos($all_fields, "{PRODBRAND}"))) {
					$query .= "(select brandname from [|PREFIX|]brands where brandid=p.prodbrandid) as prodbrand, ";
				}

				// Do we need to get the product's summary?
				if(is_numeric(isc_strpos($all_fields, "{PRODSUMMARY}"))) {
					//$query .= "substring(proddesc from 1 for 100) as prodsummary, ";
					$query .= "proddesc as prodsummary, ";
				}

				// Do we need to get the product's price?
				if(is_numeric(isc_strpos($all_fields, "{PRODPRICE}"))) {
					$query .= "p.prodcalculatedprice as prodprice, ";
				}

				// Do we need to get the product's SKU?
				if(is_numeric(isc_strpos($all_fields, "{PRODSKU}"))) {
					$query .= "p.prodcode as prodsku, ";
				}

				// Do we need to get the product's category?
				if(is_numeric(isc_strpos($all_fields, "{PRODCAT}"))) {
					$query .= "(select catname from [|PREFIX|]categoryassociations ca inner join [|PREFIX|]categories c on ca.categoryid=c.categoryid where ca.productid=p.productid limit 1) as prodcat ";
				}

				$query = rtrim($query, ", ");
				$query .= " from [|PREFIX|]products p ";

				// Do we need to filter on category?
				if($cat_ids != "") {
					$query .= sprintf("inner join [|PREFIX|]categoryassociations ca on p.productid=ca.productid where ca.categoryid in (%s)", $cat_ids);
				}

				// Build the headers for the CSV file
				$csv = implode(EXPORT_FIELD_SEPARATOR, $this->_GetFields());
				$csv .= EXPORT_RECORD_SEPARATOR;

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
				header("Content-Disposition: attachment; filename=adwords-".date("Y-m-d").".csv;");
				header("Content-Length: " . strlen($csv));
				echo $csv;

				// Let the parent class know the addon's just been executed
				parent::LogAction();

				exit;
			}
			else {
				// Bad form details
				$GLOBALS['ErrorTitle'] = GetLang('Oops');
				$GLOBALS['Message'] = MessageBox(GetLang('GoogleAdWordsBadFormDetails'), MSG_ERROR);
				$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("error");
				$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
			}
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

			foreach($this->_GetFields() as $field) {
				$row .= EXPORT_FIELD_ENCLOSURE . $_POST[$field] . EXPORT_FIELD_ENCLOSURE . EXPORT_FIELD_SEPARATOR;
			}

			// Replace tokens out of the row
			$row = $this->_ReplaceTokens($row, $Data);
			$row = rtrim($row, EXPORT_FIELD_SEPARATOR);
			$row .= EXPORT_RECORD_SEPARATOR;
			return $row;
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
				if(isset($Data[$val]) || $token == "{PRODLINK}") {

					switch($token) {
						case "{PRODSUMMARY}": {
							$Data[$val] = strip_tags($Data[$val]);

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
					}

					// Replace the value from the row
					$Row = str_replace($token, $Data[$val], $Row);
				}
				else {
					// Replace the value with nothing
					$Row = str_replace($token, "", $Row);
				}
			}

			// Run one final trim
			$Row = trim($Row);

			// Return the row
			return $Row;
		}
	}
