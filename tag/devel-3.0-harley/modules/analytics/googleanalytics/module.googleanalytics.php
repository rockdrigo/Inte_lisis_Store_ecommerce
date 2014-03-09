<?php

	/**
	* This is the Google analytics module for Interspire Shopping Cart. To enable
	* this module in Interspire Shopping Cart login to the control panel and click the
	* Settings -> Analytics Settings tab in the menu.
	*/
	class ANALYTICS_GOOGLEANALYTICS extends ISC_ANALYTICS
	{

		/*
			Analytics class constructor
		*/
		public function __construct()
		{

			// Setup the required variables for the Google analytics module
			parent::__construct();

			// http://www.google.com/support/analytics/bin/answer.py?answer=148375
			// 2. Enter everything after the domain name (this is commonly called the Request URI) in the Goal URL box. For example, if your goal page is www.domain.com/whitepaperdownload, enter "/whitepaperdownload" into the Goal URL box. Reaching this page marks a successful conversion.

			// get only the path portion of our store url -- don't use '/finishorder.php' as this is not compatible with ISC sub-directory installs
			$goalURL = $GLOBALS['ShopPath'] . '/finishorder.php';
			$goalURL = parse_url($goalURL);
			$goalURL = $goalURL['path'];

			$this->_name = GetLang('GoogleAnalyticsName');
			$this->_image = "googleanalytics_logo.gif";
			$this->_description = GetLang('GoogleAnalyticsDesc');
			$this->_help = sprintf(GetLang('GoogleAnalyticsHelp'), $goalURL, $GLOBALS['StoreName']);
			$this->_height = 0;
		}

		/**
		* Custom variables for the analytics module. Custom variables are stored in the following format:
		* array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
		* variable_type types are: text,number,password,radio,dropdown
		* variable_options is used when the variable type is radio or dropdown and is a name/value array.
		*/
		public function SetCustomVars()
		{
			$this->_variables['trackingcode'] = array(
				"name" => "Tracking Code",
				"type" => "textarea",
				"help" => GetLang('GoogleAnalyticsTrackingCodeHelp'),
				"default" => "",
				"required" => true,
				"rows" => 7
			);
		}

		/**
		* Return the tracking code for this analytics module.
		*/
		public function GetTrackingCode()
		{
			$trackingCode = $this->GetValue("trackingcode");

			// If we're on a secure server, make sure we're loading the tracking code for the secure server too
			if(isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == "on") {
				$trackingCode = str_replace("http://www.", "https://ssl.", $trackingCode);
			}

			return $trackingCode;
		}

		/**
		 * Return the conversion tracking code for this module.
		 */
		public function GetConversionCode()
		{
			$trackingCode = $this->GetValue('trackingcode');

			// Grab the first order ID from the stack and we'll use that
			$orders = $this->GetOrders();
			$order = current($orders);
			$orderId = $order['orderid'];
			$billingAddress = $this->GetBillingDetails();

			$orderIds = implode(',', array_keys($orders));
			$query = "
				SELECT ordprodid, ordprodvariationid, ordprodsku, price_inc_tax, ordprodname, ordprodqty
				FROM [|PREFIX|]order_products
				WHERE orderorderid IN (".$orderIds.")
			";
			$productResult = $GLOBALS['ISC_CLASS_DB']->Query($query);

			// If we're using the old version of the tracking code (urchin.js) we do things a little differently
			if(strpos($trackingCode, 'urchin.js') === true) {
				$trackingPieces = array(
					'UTM:T',
					$order['orderid'],
					GetConfig('StoreName'),
					number_format($this->GetTotalAmount(), 2, '.', ''),
					number_format($this->GetTaxTotal(), 2, '.', ''),
					number_format($this->GetShippingCost()+$this->GetHandlingCost(), 2, '.', ''),
					$billingAddress['ordbillsuburb'],
					$billingAddress['ordbillstate'],
					$billingAddress['ordbillcountry']
				);
				$conversionCode = implode('|', $trackingPieces)."\n";
				while($product = $GLOBALS['ISC_CLASS_DB']->Fetch($productResult)) {
					$productId = $product['ordprodid'];
					if($product['ordprodvariationid'] > 0) {
						$productId .= '-'.$product['ordprodvariationid'];
					}
					$prodCode = $product['ordprodsku'];
					if (empty($prodCode)) {
						$prodCode = $product['ordprodid'];
					}
					$trackingPieces = array(
						'UTM:I',
						$order['orderid'],
						$prodCode,
						$product['ordprodname'],
						'',
						number_format($product['price_inc_tax'], 2, '.', ''),
						$product['ordprodqty']
					);
					$conversionCode .= implode('|', $trackingPieces)."\n";
				}

				$conversionCode = "<form style='display: none;' name='utmform'><textarea id='utmtrans'>".$conversionCode."</textarea></form>";
				$conversionCode .= "<script type=\"text/javascript\">
					$(document).ready(function() {
						__utmSetTrans();
					});
				</script>";
			}
			// Using the new version of the tracking code
			else {
				$conversionCode = "
					<script type=\"text/javascript\">
					$(document).ready(function() {
						if(typeof(pageTracker) != 'undefined') {
							pageTracker._addTrans(
								'".$order['orderid']."',
								'".addslashes(GetConfig('StoreName'))."',
								'".number_format($this->GetTotalAmount(), 2, '.', '')."',
								'".number_format($this->GetTaxCost(), 2, '.', '')."',
								'".number_format($this->GetShippingCost(), 2, '.', '')."',
								'".$billingAddress['ordbillsuburb']."',
								'".$billingAddress['ordbillstate']."',
								'".$billingAddress['ordbillcountry']."'
							);
				";
				while($product = $GLOBALS['ISC_CLASS_DB']->Fetch($productResult)) {
					$productId = $product['ordprodid'];
					if($product['ordprodvariationid'] > 0) {
						$productId .= '-'.$product['ordprodvariationid'];
					}
					$prodCode = $product['ordprodsku'];
					if (empty($prodCode)) {
						$prodCode = $product['ordprodid'];
					}

					$conversionCode .= "
						pageTracker._addItem(
							'".$order['orderid']."',
							'".addslashes($prodCode)."',
							'".addslashes($product['ordprodname'])."',
							'',
							'".number_format($product['price_inc_tax'], 2, '.', '')."',
							'".$product['ordprodqty']."'
						);
					";
				}
				$conversionCode .= "
						pageTracker._trackTrans();
					}
				});
				</script>";
			}
			return $conversionCode;
		}
	}