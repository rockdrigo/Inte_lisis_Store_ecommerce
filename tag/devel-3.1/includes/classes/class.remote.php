<?php
	if (!defined('ISC_BASE_PATH')) {
		die();
	}

	require_once(ISC_BASE_PATH.'/lib/class.xml.php');

	class ISC_REMOTE extends ISC_XML_PARSER
	{
		public function __construct()
		{
			parent::__construct();
		}

		public function HandleToDo()
		{
			/**
			 * Convert the input character set from the hard coded UTF-8 to their
			 * selected character set
			 */
			convertRequestInput();

			$what = isc_strtolower(@$_REQUEST['w']);

			switch  ($what) {
				case "countrystates": {
					$this->GetCountryStates();
					break;
				}
				case "getstates": {
					$this->GetStateList();
					break;
				}
				case "getcountries": {
					$this->GetCountryList();
					break;
				}
				case "getexchangerate": {
					$this->GetExchangeRate();
					break;
				}
				case "expresscheckoutlogin":
					$this->ExpressCheckoutLogin();
					break;
				case "expresscheckoutgetaddressfields":
					$this->GetExpressCheckoutAddressFields();
					break;
				case 'getexpresscheckoutconfirmation':
					$this->GetExpressCheckoutConfirmation();
					break;
				case "expresscheckoutloadpaymentform":
					$this->GetExpressCheckoutPaymentForm();
					break;
				case 'saveexpresscheckoutbillingaddress':
					$this->saveExpressCheckoutBillingAddress();
					break;
				case 'saveexpresscheckoutshippingaddress':
					$this->saveExpressCheckoutShippingAddress();
					break;
				case 'saveexpresscheckoutshippingprovider':
					$this->saveExpressCheckoutShippingProvider();
					break;
				case "getshippingquotes":
					$this->GetShippingQuotes();
					break;
				case 'selectgiftwrapping':
					$this->SelectGiftWrapping();
					break;
				case 'editconfigurablefieldsincart':
					$this->EditConfigurableFieldsInCart();
					break;
				case 'deleteuploadedfileincart':
					$this->DeleteUploadedFileInCart();
					break;
				case 'addproducts':
					$this->AddProductsToCart();
					break;
				case 'paymentprovideraction':
					$this->ProcessRemoteActions();
					break;
				case 'doadvancesearch':
					$this->doAdvanceSearch();
					break;
				case 'sortadvancesearch':
					$this->sortAdvanceSearch();
					break;
				case 'getvariationoptions':
					$this->GetVariationOptions();
					break;
				case "updatelanguage": {
					$this->UpdateLanguage();
					break;
				}
				case 'disabledesignmode': {
					$this->DisableDesignMode();
					break;
				}
				case 'productrefreshstock': {
					$this->ProductRefreshStock(false, true);
					break;
				}
				case 'addcustomfield': {
					$this->AddCustomField();
					break;
				}
			}
		}

		private function AddCustomField(){
			if (!array_key_exists('nextId', $_REQUEST)) {
				print '';
				exit;
			}
			$GLOBALS['orderfastFieldId'] = $_REQUEST['nextId'];
			$GLOBALS['HideOrderfastDelete'] = '';
			
			echo $GLOBALS['ISC_CLASS_TEMPLATE']->getSnippet('OrderfastFieldRow');
			exit;
		}
		
		/*
		 * Funcionalidad para consultar el inventario del producto a travez de IntelisisWebService.
		 * Se requiere incluir el Panel "ProductStockDetails.html" en algun lugar de la plantilla product.html
		 * 
		 *  REQ10046 - Se aumentan los parametros para funcionar en modo JSON (o sea, la funcion es llamada desde el boton de Refrescar Existencias (En linea), o que regrese el arreglo,
		 *  	por si se llama desde GetVariationOptions()
		 */
		public function ProductRefreshStock($productCode = false, $outputJSON = false) {

			// Si se llama desde el boton, no tenemos SKU, así que lo consultamos con productID y Options
			if(!$productCode) {
				if(!isset($_REQUEST['productId']) || !isset($_REQUEST['options'])){
					$out = array(
						'error' => 'No se pudo contactar al servicio para obtener el inventario',
					);
					logAddError('No se definio un SKU de producto, y no se pasaron el productId o las opciones para buscarlo');
					if($outputJSON) echo isc_json_encode($out);
					else return $out;
				}
				$productId = $_REQUEST['productId'];
				$optionIds = $_REQUEST['options'];
				
				if($optionIds == '') {
					$productCode = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT prodcode FROM [|PREFIX|]products WHERE productid = "'.$productId.'"', 'prodcode');
				}
				else {
					$productCode = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT vcsku FROM [|PREFIX|]product_variation_combinations WHERE vcproductid = "'.$productId.'" AND vcoptionids = "'.$optionIds.'"', 'vcsku');
				}
			}
			
			if(GetConfig('syncIWSurl') != ''){
			
				$IWS = new ISC_INTELISIS_WS_PRODUCTSTOCK($productCode);
				if(!$out = $IWS->prepareRequest()){
					$out = array(
						'error' => 'No se pudo contactar al servicio para obtener el inventario',
					);
				}
				
				$result_Sucursal = $GLOBALS['ISC_CLASS_DB']->Query('SELECT * FROM [|PREFIX|]intelisis_Sucursal');
				$Sucursales = array();
				while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result_Sucursal)) {
					$Sucursales[$row['Sucursal']] = $row;
				}

				if(!isset($out['error'])) {
					foreach ($out as $index => $stock) {
						if(isset($Sucursales[$stock['Sucursal']])) {
							$out[$index]['SucursalDetalles'] = array(
								'Nombre' => $Sucursales[$stock['Sucursal']]['Nombre'],
								'Contacto' => $Sucursales[$stock['Sucursal']]['Direccion'] . "<br/>tel:" . $Sucursales[$stock['Sucursal']]['Telefonos'],
							);
						}
						else
							$out[$index]['SucursalDetalles'] = array(
								'Nombre' => 'ID: '.$stock['Sucursal'],
								'Contacto' => 'No se encontraron los detalles de contacto de esta Sucursal',
							);
					}
					if($outputJSON) { echo isc_json_encode($out); exit; }
					else { return $out; }
				}
			}
			
			// REQ10046 - Se aumenta esto para que actualice la existencia de la tabla si es que falla la solicitud a IWS
			$productStockDetail = getProductStockDetail($productCode);
			
			$out = array();
			if(count($productStockDetail) > 0){
				foreach($productStockDetail as $row) {
					$out[] = array(
						'Cantidad' => $row['Existencia'], 
						'SucursalDetalles' => array(
							'Nombre' => $row['Nombre'],
							'Contacto' => $row['Contacto'],
						),
					);
				}
			}
			else {
				$out = array(
						'error' => 'No se encontraron existencias de este producto con la combinacion elegida en las Sucursales',
				);
			}
			
			if($outputJSON) echo isc_json_encode($out);
			else return $out;
		}
		
		public function DisableDesignMode()
		{
			isc_unsetCookie('designModeToken');
			exit;
		}

		public function DeleteUploadedFileInCart()
		{
			if(!isset($_REQUEST['item']) || !isset($_REQUEST['field'])) {
				return false;
			}

			$itemId = $_REQUEST['item'];

			$quote = getCustomerQuote();
			$item = $quote->getItemById($itemId);
			if(!$item) {
				return false;
			}

			$item->deleteConfigurableFile($_REQUEST['field']);
		}

		public function EditConfigurableFieldsInCart()
		{
			$quote = getCustomerQuote();
			if(!isset($_REQUEST['itemid']) || !$quote->hasItem($_REQUEST['itemid'])) {
				return false;
			}

			$output = '';

			$item = $quote->getItemById($_REQUEST['itemid']);
			$existingConfiguration = $item->getConfiguration();

			$GLOBALS['ItemId'] = $item->getId();

			$GLOBALS['ISC_CLASS_PRODUCT'] = GetClass('ISC_PRODUCT');
			$GLOBALS['CartProductName'] = isc_html_escape($item->getName());

			$fields = $item->getConfigurableOptions();
			foreach($fields as $field) {
				$GLOBALS['ProductFieldType'] = isc_html_escape($field['fieldtype']);
				$GLOBALS['ProductFieldId'] = (int)$field['productfieldid'];
				$GLOBALS['ProductFieldName'] = isc_html_escape($field['fieldname']);
				$GLOBALS['ProductFieldRequired'] = '';
				$GLOBALS['FieldRequiredClass'] = '';
				$GLOBALS['ProductFieldValue'] = '';
				$GLOBALS['ProductFieldFileValue'] = '';
				$GLOBALS['HideCartFileName'] = 'display: none';
				$GLOBALS['CheckboxFieldNameLeft'] = '';
				$GLOBALS['CheckboxFieldNameRight'] = '';
				$GLOBALS['HideDeleteFileLink'] = 'display: none';
				$GLOBALS['HideFileHelp'] = "display:none";

				$configurableField = array(
					'type'				=> '',
					'name'				=> '',
					'fileType'			=> '',
					'fileOriginalName'	=> '',
					'value'				=> '',
					'selectOptions'		=> '',
				);

				if(isset($existingConfiguration[$field['productfieldid']])) {
					$configurableField = $existingConfiguration[$field['productfieldid']];
				}

				$snippetFile = 'ProductFieldInput';
				switch ($field['fieldtype']) {
					case 'textarea': {
						$GLOBALS['ProductFieldValue'] = isc_html_escape($configurableField['value']);
						$snippetFile = 'ProductFieldTextarea';
						break;
					}
					case 'file': {
						$fieldValue = isc_html_escape($configurableField['fileOriginalName']);
						$GLOBALS['HideDeleteCartFieldFile'] = '';
						$GLOBALS['CurrentProductFile'] = $fieldValue;
						$GLOBALS['ProductFieldFileValue'] = $fieldValue;
						$GLOBALS['HideFileHelp'] = "";
						$GLOBALS['FileSize'] = Store_Number::niceSize($field['fieldfilesize']*1024);

						if($fieldValue != '') {
							$GLOBALS['HideCartFileName'] = '';
						}

						if(!$field['fieldrequired']) {
							$GLOBALS['HideDeleteFileLink'] = '';
						}
						$GLOBALS['FileTypes'] = isc_html_escape($field['fieldfiletype']);
						break;
					}
					case 'checkbox': {
						$GLOBALS['CheckboxFieldNameLeft'] = $GLOBALS['ProductFieldName'];
						if($configurableField['value'] == 'on') {
							$GLOBALS['ProductFieldValue'] = 'checked';
						}
						$snippetFile = 'ProductFieldCheckbox';
						break;
					}
					case 'select':
						$options = explode(',', $configurableField['selectOptions']);
						$optionStr = '<option value="">' . GetLang('PleaseChooseAnOption') . '</option>';
						foreach ($options as $option) {
							$option = trim($option);

							$selected = '';
							if ($option == $configurableField['value']) {
								$selected = 'selected="selected"';
							}

							$optionStr .= "<option value=\"" . isc_html_escape($option) . "\" " . $selected . ">" . isc_html_escape($option) . "</option>\n";
						}
						$GLOBALS['SelectOptions'] = $optionStr;
						$snippetFile = 'ProductFieldSelect';
						break;
					default: {
						$GLOBALS['ProductFieldValue'] = isc_html_escape($configurableField['value']);
						break;
					}
				}

				if($field['fieldrequired']) {
					$GLOBALS['ProductFieldRequired'] = '<span class="Required">*</span>';
					$GLOBALS['FieldRequiredClass'] = 'FieldRequired';
				}
				$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('Cart'.$snippetFile);
			}
			$GLOBALS['SNIPPETS']['ProductFieldsList'] = $output;

			$editProductFields = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('CartEditProductFieldsForm');
			echo $GLOBALS['ISC_CLASS_TEMPLATE']->ParseSnippets($editProductFields, $GLOBALS['SNIPPETS']);
		}

		public function SelectGiftWrapping()
		{
			$quote = getCustomerQuote();
			if(!isset($_REQUEST['itemId']) || !$quote->hasItem($_REQUEST['itemId'])) {
				exit;
			}

			$item = $quote->getItemById($_REQUEST['itemId']);

			$GLOBALS['GiftWrappingTitle'] = sprintf(GetLang('GiftWrappingForX'), isc_html_escape($item->getName()));
			$GLOBALS['ProductName'] = $item->getName();
			$GLOBALS['ItemId'] = $item->getId();

			// Get the available gift wrapping options for this product
			$wrappingOptions = $item->getGiftWrappingOptions();
			if($wrappingOptions === false) {
				exit;
			}

			if(empty($wrappingOptions) || in_array(0, $wrappingOptions)) {
				$giftWrapWhere = "wrapvisible='1'";
			}
			else {
				$wrappingOptions = implode(',', array_map('intval', $wrappingOptions));
				$giftWrapWhere = "wrapid IN (".$wrappingOptions.")";
			}
			$query = "
				SELECT *
				FROM [|PREFIX|]gift_wrapping
				WHERE ".$giftWrapWhere."
				ORDER BY wrapname ASC
			";
			$wrappingOptions = array();
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while($wrap = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$wrappingOptions[$wrap['wrapid']] = $wrap;
			}

			// This product is already wrapped, select the existing value
			$GLOBALS['GiftWrapMessage'] = '';

			$selectedWrapping = 0;
			$existingWrapping = $item->getGiftWrapping();
			if($existingWrapping !== false) {
				$selectedWrapping = $existingWrapping['wrapid'];
				$GLOBALS['GiftWrapMessage'] = isc_html_escape($existingWrapping['wrapmessage']);
			}

			$GLOBALS['HideGiftWrapMessage'] = 'display: none';

			// Build the list of wrapping options
			$GLOBALS['WrappingOptions'] = '';
			$GLOBALS['GiftWrapPreviewLinks'] = '';
			foreach($wrappingOptions as $option) {
				$sel = '';
				if($selectedWrapping == $option['wrapid']) {
					$sel = 'selected="selected"';
					if($option['wrapallowcomments']) {
						$GLOBALS['HideGiftWrapMessage'] = '';
					}
				}
				$classAdd = '';
				if($option['wrapallowcomments']) {
					$classAdd = 'AllowComments';
				}

				if($option['wrappreview']) {
					$classAdd .= ' HasPreview';
					$previewLink = GetConfig('ShopPath').'/'.GetConfig('ImageDirectory').'/'.$option['wrappreview'];
					if($sel) {
						$display = '';
					}
					else {
						$display = 'display: none';
					}
					$GLOBALS['GiftWrapPreviewLinks'] .= '<a id="GiftWrappingPreviewLink'.$option['wrapid'].'" class="GiftWrappingPreviewLinks" target="_blank" href="'.$previewLink.'" style="'.$display.'">'.GetLang('Preview').'</a>';
				}

				$GLOBALS['WrappingOptions'] .= '<option class="'.$classAdd.'" value="'.$option['wrapid'].'" '.$sel.'>'.isc_html_escape($option['wrapname']).' ('.CurrencyConvertFormatPrice($option['wrapprice']).')</option>';
			}

			$quantity = $item->getQuantity();
			if($quantity > 1) {
				$GLOBALS['ExtraClass'] = 'PL40';
				$GLOBALS['GiftWrapModalClass'] = 'SelectGiftWrapMultiple';
				$GLOBALS['SNIPPETS']['GiftWrappingOptions'] = '';
				for($i = 1; $i <= $quantity; ++$i) {
					$GLOBALS['GiftWrappingId'] = $i;
					$GLOBALS['SNIPPETS']['GiftWrappingOptions'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('GiftWrappingWrapOptions');
				}
			}
			else {
				$GLOBALS['HideSplitWrappingOptions'] = 'display: none';
			}

			$GLOBALS['HideWrappingTitle']		= 'display: none';
			$GLOBALS['HideWrappingSeparator']	= 'display: none';
			$GLOBALS['GiftWrappingId'] = 'all';
			$GLOBALS['SNIPPETS']['GiftWrappingOptionsSingle'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('GiftWrappingWrapOptions');

			$selectWrapping = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('SelectGiftWrapping');
			echo $GLOBALS['ISC_CLASS_TEMPLATE']->ParseSnippets($selectWrapping, $GLOBALS['SNIPPETS']);
		}

		/**
		 * Check a customers entered credentials when logging in via the express checkout.
		 */
		private function ExpressCheckoutLogin()
		{
			// Attempt to log the customer in
			$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
			if(!$GLOBALS['ISC_CLASS_CUSTOMER']->CheckLogin(true)) {
				$loginLink = '#';
				$onClick = '$("#checkout_type_register").click(); $("#CreateAccountButton").click(); return false;';
				$errorMessage = sprintf(GetLang('CheckoutBadLoginDetails'), $loginLink, $onClick);
				$response = array(
					'status' => 0,
					'errorMessage' => $errorMessage,
					'errorContainer' => '#CheckoutLoginError',
				);
				echo isc_json_encode($response);
				exit;
			}

			$response = array(
				'status' => 1,
				'resetSteps' => true,
				'changeStep' => 'BillingAddress',
				'completedSteps' => array(
					array(
						'id' => 'AccountDetails',
						'message' => getLang('CheckingOutAs').' '.$_POST['login_email']
					),
				),
				'stepContent' => array(
					array(
						'id' => 'BillingAddress',
						'content' => getClass('ISC_CHECKOUT')->expressCheckoutChooseAddress('billing', true)
					),
					array(
						'id' => 'ShippingAddress',
						'content' => getClass('ISC_CHECKOUT')->expressCheckoutChooseAddress('shipping', true)
					),
				)
			);
			echo isc_json_encode($response);
			exit;
		}

		/**
		 * Generate the payment form for a payment provider (credit card manual, etc) and display it for the express checkout.
		 */
		private function GetExpressCheckoutPaymentForm()
		{
			// Attempt to create the pending order with the selected details
			$pendingResult = getClass('ISC_CHECKOUT')->savePendingOrder();

			// There was a problem creating the pending order
			if(!is_array($pendingResult)) {
				$response = array(
					'status' => 0,
					'errorMessage' => getLang('ProblemCreatingOrder'),
					'changeStep' => 'Confirmation'
				);
				echo isc_json_encode($response);
				exit;
			}

			// There was a problem creating the pending order but we have an actual error message
			if(isset($pendingResult['error'])) {
				$response = array(
					'status' => 0,
					'errorMessage' => $pendingResult['error'],
					'changeStep' => 'Confirmation'
				);
				echo isc_json_encode($response);
				exit;
			}

			// Otherwise, the gateway want's to do something
			if($pendingResult['provider']->GetPaymentType() == PAYMENT_PROVIDER_ONLINE || method_exists($pendingResult['provider'], 'ShowPaymentForm')) {
				if($pendingResult['provider']->GetPaymentType() !== PAYMENT_PROVIDER_ONLINE) {
					$pendingResult['showPaymentForm'] = $pendingResult['provider']->ShowPaymentForm();
				}

				// If we have a payment form to show then show that
				if(isset($pendingResult['showPaymentForm']) && $pendingResult['showPaymentForm']) {
					$response = array(
						'status' => 1,
						'stepContent' => array(
							array(
								'id' => 'PaymentDetails',
								'content' => $pendingResult['provider']->showPaymentForm()
							),
						),
						'changeStep' => 'PaymentDetails',
						'completeSteps' => array(
							array(
								'id' => 'Confirmation',
								'message' => $pendingResult['provider']->getDisplayName()
							),
						),
					);
					echo isc_json_encode($response);
				}
			}
			exit;
		}

		/**
		 * Generate the order confirmation message and save the pending order for a customer checking out via the
		 * express checkout
		 */
		private function GetExpressCheckoutConfirmation($completedSteps = array())
		{


			$confirmation = getClass('ISC_CHECKOUT')->GenerateExpressCheckoutConfirmation();
			if(!$confirmation) {
				$response = array(
					'status' => 0,
					'changeStep' => 'BillingAddress',
				);
				echo isc_json_encode($response);
				exit;
			}

			$response = array(
				'status' => 1,
				'changeStep' => 'Confirmation',
				'resetSteps' => true,
				'stepContent' => array(
					array(
						'id' => 'Confirmation',
						'content' => $confirmation
					),
				),
				'completedSteps' => $completedSteps,
			);
			echo isc_json_encode($response);
			exit;
		}

		public function getCheckoutAddressPreview(ISC_QUOTE_ADDRESS $address)
		{
			$addressPieces = array(
				$address->getFirstName().' '.$address->getLastName(),
				$address->getCompany(),
				$address->getAddress1(),
				$address->getAddress2(),
				$address->getCity(),
				$address->getStateName(),
				$address->getCountryName(),
				$address->getZip()
			);
			foreach($addressPieces as $k => $piece) {
				if(!trim($piece)) {
					unset($addressPieces[$k]);
				}
			}

			$addressString = implode(', ', $addressPieces);
			if(isc_strlen($addressString) > 60) {
				$addressString = substr($addressString, 0, 57).'...';
			}

			return $addressString;
		}

		/**
		 * Save the billing address for a customer checking out via express checkout.
		 */
		public function saveExpressCheckoutBillingAddress()
		{
			// If the customer is not logged in and guest checkout is enabled, then don't go any further
			if(!customerIsSignedIn() && !getConfig('GuestCheckoutEnabled') &&
				empty($_POST['createAccount'])) {
					$response = array(
						'status' => 0,
						'changeStep' => 'AccountDetails',
						'errorMessage' => getLang('GuestCheckoutDisabledError')
					);
					echo isc_json_encode($response);
					exit;
			}

			$addressDetails =  null;
			$shipToBilling = false;

			// If the customer isn't signed in then they've just entered an address that we need to validate
			if(isset($_REQUEST['BillingAddressType']) && $_REQUEST['BillingAddressType'] == 'new') {
				$errors = array();
				// An invalid address was entered, show the form again
				$addressDetails = getClass('ISC_CHECKOUT')->validateGuestCheckoutAddress('billing', $errors);
				if(!$addressDetails) {
					$response = array(
						'status' => 0,
						'changeStep' => 'BillingAddress',
						'errorMessage' => implode("\n", $errors)
					);
					echo isc_json_encode($response);
					exit;
				}

				// Make sure the email address isn't already in use if the customer is
				// creating a new account.
				// Plus if it's guess checkout and creation of account after the checkout process is enabled
				if(!customerIsSignedIn() && (!empty($_POST['createAccount']) || (getConfig('GuestCheckoutEnabled') && getConfig('GuestCheckoutCreateAccounts')))) {
					$emailField = $GLOBALS['ISC_CLASS_FORM']->getFormField(FORMFIELDS_FORM_ACCOUNT, '1', '', true);
					$email = $emailField->getValue();

					// Check that this email address isn't already in use by a customer
					$customer = GetClass('ISC_CUSTOMER');
					if($customer->AccountWithEmailAlreadyExists($email)) {
						$response = array(
							'status' => 0,
							'changeStep' => 'BillingAddress',
							'errorMessage' => getLang('CheckoutEmailAddressInUseAjax'),
							'focus' => '#'.$emailField->getFieldId(),
						);
						echo isc_json_encode($response);
						exit;
					}
				}

				if(!empty($_POST['ship_to_billing_new'])) {
					$shipToBilling = true;
				}
			}
			else {
				// We've just selected an address
				if(isset($_POST['sel_billing_address'])) {
					$addressDetails = (int)$_POST['sel_billing_address'];
				}

				if(!empty($_POST['ship_to_billing_existing'])) {
					$shipToBilling = true;
				}
			}

			// There was a problem saving the selected billing address
			if(!getClass('ISC_CHECKOUT')->setOrderBillingAddress($addressDetails)) {
				$response = array(
					'status' => 0,
					'changeStep' => 'BillingAddress',
					'errorMessage' => getLang('UnableSaveOrderBillingAddress'),
				);

				echo isc_json_encode($response);
				exit;
			}

			if(!empty($_POST['save_billing_address'])) {
				getCustomerQuote()->getBillingAddress()->setSaveAddress(true);
			}

			$completedSteps = array(
				array(
					'id' => 'BillingAddress',
					'message' => $this->getCheckoutAddressPreview(
						getCustomerQuote()->getBillingAddress()
					),
				)
			);

			// If creating an account, store the account creation fields
			unset($_SESSION['CHECKOUT']['CREATE_ACCOUNT']);
			if(!empty($_POST['createAccount'])) {
				$accountFields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT, true);
				$accountSession = array(
					'customFields' => array()
				);
				foreach($accountFields as $fieldId => $formField) {
					if($formField->record['formfieldprivateid'] == 'Password') {
						$accountSession['password'] = $formField->getValue();
					}
					// Apart from the password, only interested in CUSTOM fields
					else if(!$formField->record['formfieldprivateid']) {
						$accountSession['customFields'][$fieldId] = $formField->getValue();
					}
				}
				$_SESSION['CHECKOUT']['CREATE_ACCOUNT'] = $accountSession;
			}

			// If a digital order, skip right to the order confirmation
			if(getCustomerQuote()->isDigital()) {
				$this->getExpressCheckoutConfirmation($completedSteps);
				exit;
			}

			// Otherwise, proceed with shipping

			// Shipping to the billing address so save it as well
			if($shipToBilling) {
				if(!getClass('ISC_CHECKOUT')->setOrderShippingAddress($addressDetails, true)) {
					$response = array(
						'status' => 0,
						'changeStep' => 'ShippingAddress',
						'errorMessage' => getLang('UnableSaveOrderShippingAddress'),
					);

					echo isc_json_encode($response);
					exit;
				}

				// If we're shipping to the billing address, then reload the shipping address
				// quote block, because it could contain updated values.
				$stepContent = array(array(
					'id' => 'ShippingAddress',
					'content' => getClass('ISC_CHECKOUT')->expressCheckoutChooseAddress('shipping', true),
				));
				$this->getExpressCheckoutShippers($completedSteps, $stepContent);
				exit;
			}

			$response = array(
				'status' => 1,
				'changeStep' => 'ShippingAddress',
				'completedSteps' => $completedSteps,
				'resetSteps' => true,
			);
			echo isc_json_encode($response);
			exit;
		}

		public function saveExpressCheckoutShippingAddress()
		{
			$quote = getCustomerQuote();
			if($quote->isDigital()) {
				exit;
			}

			$addressDetails = null;

			if(isset($_REQUEST['ShippingAddressType']) && $_REQUEST['ShippingAddressType'] == 'new') {
				$errors = array();
				// An invalid address was entered, show the form again
				$addressDetails = getClass('ISC_CHECKOUT')->validateGuestCheckoutAddress('shipping', $errors);
				if(!$addressDetails) {
					$response = array(
						'status' => 0,
						'changeStep' => 'ShippingAddress',
						'errorMessage' => implode("\n", $errors)
					);
					echo isc_json_encode($response);
					exit;
				}
			}
			else {
				// We've just selected an address
				if(isset($_POST['sel_shipping_address'])) {
					$addressDetails = (int)$_POST['sel_shipping_address'];
				}
			}

			if(!getClass('ISC_CHECKOUT')->setOrderShippingAddress($addressDetails)) {
				$response = array(
					'status' => 0,
					'changeStep' => 'ShippingAddress',
					'errorMessage' => getLang('UnableSaveOrderShippingAddress'),
				);

				echo isc_json_encode($response);
				exit;
			}

			$this->getExpressCheckoutShippers();
		}

		public function saveExpressCheckoutShippingProvider()
		{
			$quote = getCustomerQuote();
			if($quote->isDigital()) {
				exit;
			}
			
			if(GetConfig('isIntelisis') && GetConfig('syncIWSurl') != ''){
				$IWS_preorder = new ISC_INTELISIS_WS_PREORDER();
				$IWS_preorder->prepareRequest();
			}
			
			// If the shipping provider couldn't be saved with the order show an error message
			// For each shipping address in the order, the shipping provider now needs to be saved
			$success = true;
			$shippingAddresses = getClass('ISC_CHECKOUT')->getQuote()->getShippingAddresses();
			foreach($shippingAddresses as $shippingAddress) {
				$shippingAddressId = $shippingAddress->getId();
				if(!isset($_POST['selectedShippingMethod'][$shippingAddressId])) {
					$success = false;
					break;
				}

				$id = $_POST['selectedShippingMethod'][$shippingAddressId];
				$cachedShippingMethod = $shippingAddress->getCachedShippingMethod($id);
				if(empty($cachedShippingMethod)) {
					$success = false;
					break;
				}

				$shippingAddress->setShippingMethod(
					$cachedShippingMethod['price'],
					$cachedShippingMethod['description'],
					$cachedShippingMethod['module']
				);
				$shippingAddress->setHandlingCost($cachedShippingMethod['handling']);
			}
			if(!$success) {
				$response = array(
					'success' => 0,
					'changeStep' => 'ShippingProvider',
					'errorMessage' => getLang('UnableSaveOrderShippingMethod'),
				);
				echo isc_json_encode($response);
				exit;
			}
			$completedSteps = array(
				array(
					'id' => 'ShippingProvider',
					'message' =>
						$shippingAddress->getShippingProvider() . ' ' .
						getLang('ExpressCheckoutFor') . ' ' .
						currencyConvertFormatPrice($shippingAddress->getShippingCost())
				)
			);

			$this->getExpressCheckoutConfirmation($completedSteps);
		}

		/**
		 * Fetch the address entry fields for a guest when using the express checkout.
		 */
		private function GetExpressCheckoutAddressFields()
		{
			// Make sure the customer is logged out. This is a guest checkout
			getClass('ISC_CUSTOMER')->logout(true);

			if(!empty($_POST['type']) && $_POST['type'] != 'guest') {
				$addressType = 'account';
				$accountDetailsMessage = getLang('ExpressCheckoutCreatingAnAccount');
			}
			else {
				$addressType = 'billing';
				$accountDetailsMessage = getLang('ExpressCheckoutCheckingOutAsGuest');
			}

			$response = array(
				'status' => 1,
				'completedSteps' => array(
					array(
						'id' => 'AccountDetails',
						'message' => $accountDetailsMessage,
					),
				),
				'stepContent' => array(
					array(
						'id' => 'BillingAddress',
						'content' => getClass('ISC_CHECKOUT')->expressCheckoutChooseAddress($addressType, true),
					),
					array(
						'id' => 'ShippingAddress',
						'content' => getClass('ISC_CHECKOUT')->expressCheckoutChooseAddress('shipping', true),
					),
				),
				'changeStep' => 'BillingAddress',
				'resetSteps' => 1,
			);

			echo isc_json_encode($response);
			exit;
		}

		/**
		 * Generate a list of shipping methods/providers for a customer checking out via the express checkout.
		 */
		private function GetExpressCheckoutShippers($completedSteps = array(), $stepContent = array())
		{
			$defaultCurrency = GetDefaultCurrency();
			$quote = getCustomerQuote();
			if($quote->isDigital()) {
				exit;
			}

			$shippingAddress = $quote->getShippingAddress();
			if(!$shippingAddress->hasCompleteAddress()) {
				$response = array(
					'status' => 0,
					'changeStep' => 'ShippingAddress',
					'resetSteps' => true,
					'errorMessage' => getLang('UnableToShipToAddressSingle'),
					'stepContent' => $stepContent,
				);
				echo isc_json_encode($response);
				exit;
			}

			$availableMethods = $shippingAddress->getAvailableShippingMethods();
			if(empty($availableMethods)) {
				$response = array(
					'status' => 0,
					'changeStep' => 'ShippingAddress',
					'resetSteps' => true,
					'errorMessage' => getLang('UnableToShipToAddressSingle'),
					'stepContent' => $stepContent,
				);
				echo isc_json_encode($response);
				exit;
			}

			// Keeping for legacy reasons for now
			$GLOBALS['HideVendorTitle'] = 'display: none';
			$GLOBALS['HideVendorItems'] = 'display: none';

			// Because split shipping isn't supported on express checkout:
			$GLOBALS['HideItemList'] = 'display: none';
			$GLOBALS['HideHorizontalRule'] = 'display: none';
			$GLOBALS['HideAddressLine'] = 'display: none';

			$hasTransit = false;
			$GLOBALS['ShippingQuotes'] = '';

			// Now build a list of the actual available quotes
			$GLOBALS['ShippingProviders'] = '';
			$GLOBALS['AddressId'] = $shippingAddress->getId();
			foreach($availableMethods as $quoteId => $method) {
				$price = getClass('ISC_TAX')->getPrice(
					$method['price'],
					getConfig('taxShippingTaxClass'),
					getConfig('taxDefaultTaxDisplayCart'),
					$shippingAddress->getApplicableTaxZone()
				);
				$GLOBALS['ShipperName'] = isc_html_escape($method['description']);
				$GLOBALS['ShippingPrice'] = CurrencyConvertFormatPrice($price,$defaultCurrency['currencyid'],null,true);
				$GLOBALS['ShippingQuoteId'] = $quoteId;
				$GLOBALS['ShippingData'] = $GLOBALS['ShippingQuoteId'];

				if(isset($method['transit'])) {
					$hasTransit = true;

					$days = $method['transit'];

					if ($days == 0) {
						$transit = GetLang("SameDay");
					}
					else if ($days == 1) {
						$transit = GetLang('NextDay');
					}
					else {
						$transit = sprintf(GetLang('Days'), $days);
					}

					$GLOBALS['TransitTime'] = $transit;
					$GLOBALS['TransitTime'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('CartShippingTransitTime');
				}
				else {
					$GLOBALS['TransitTime'] = "";
				}
				$GLOBALS['ShippingProviders'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ExpressCheckoutShippingMethod");
			}
			// Add it to the list
			$GLOBALS['ShippingQuotes'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('ShippingQuote');

			if ($hasTransit) {
				$GLOBALS['DeliveryDisclaimer'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('CartShippingDeliveryDisclaimer');
			}

			$methodList = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('ExpressCheckoutChooseShipper');

			$response = array(
				'status' => 1,
				'changeStep' => 'ShippingProvider',
				'resetSteps' => true,
				'stepContent' => array_merge($stepContent, array(array(
					'id' => 'ShippingProvider',
					'content' => $methodList
				))),
				'completedSteps' => array_merge($completedSteps, array(array(
					'id' => 'ShippingAddress',
					'message' => $this->getCheckoutAddressPreview($shippingAddress),
				))),
			);
			
			echo isc_json_encode($response);
			exit;
		}

		/**
		 * Retrieve a list of shipping quotes for a customer estimating their shipping on the 'View Cart' page.
		 */
		private function GetShippingQuotes()
		{
			if(empty($_POST['countryId']) || empty($_POST['zipCode'])) {
				exit;
			}

			$statesList = GetStateListAsIdValuePairs((int)$_POST['countryId']);
			if (!empty($statesList) && empty($_POST['stateId'])) {
				exit;
			}

			// Cart page shipping quotes don't support split shipping
			$quote = getCustomerQuote();
			$quote->setIsSplitShipping(false);

			$shippingAddress = $quote->getShippingAddress();
			$billingAddress = $quote->getBillingAddress();

			$shippingAddress->setCountryById($_POST['countryId']);
			$billingAddress->setCountryById($_POST['countryId']);
			if(!empty($_POST['stateId'])) {
				$shippingAddress->setStateById($_POST['stateId']);
				$billingAddress->setStateById($_POST['stateId']);
			}
			if(!empty($_POST['zipCode'])) {
				$shippingAddress->setZip($_POST['zipCode']);
				$billingAddress->setZip($_POST['zipCode']);
			}

			$quote->addShippingAddress($shippingAddress);
			$shippingMethods = $shippingAddress->getAvailableShippingMethods();
			if(empty($shippingMethods)) {
				echo getLang('UnableEstimateShipping');
				exit;
			}

			// Keeping this for legacy purposes for now
			$GLOBALS['HideVendorDetails'] = 'display: none';
			$GLOBALS['ShippingQuotesListNote'] = '';
			$GLOBALS['HideShippingQuotesListNote'] = 'display: none';
			$GLOBALS['VendorShippingQuoteClass'] = '';
			$GLOBALS['HideShippingItemList'] = 'display: none';

			$hasTransit = false;
			$GLOBALS['ShippingQuoteRow'] = '';
			foreach($shippingMethods as $quoteId => $method) {
				$price = getClass('ISC_TAX')->getPrice(
					$method['price'],
					getConfig('taxShippingTaxClass'),
					getConfig('taxDefaultTaxDisplayCart'),
					$shippingAddress->getApplicableTaxZone()
				);
				$GLOBALS['ShipperName'] = isc_html_escape($method['description']);
				$GLOBALS['ShippingPrice'] = CurrencyConvertFormatPrice($price);
				$GLOBALS['ShippingQuoteId'] = $quoteId;

				$GLOBALS['TransitTime'] = "";
				if(isset($method['transit'])) {
					$hasTransit = true;
					$days = $method['transit'];
					if ($days == 0) {
						$transit = GetLang("SameDay");
					}
					else if ($days == 1) {
						$transit = GetLang('NextDay');
					}
					else {
						$transit = sprintf(GetLang('Days'), $days);
					}

					$GLOBALS['TransitTime'] = $transit;
					$GLOBALS['TransitTime'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('CartShippingTransitTime');
				}

				$GLOBALS['ShippingQuoteRow'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('CartShippingQuoteRow');
			}

			$GLOBALS['ShippingQuotes'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('EstimatedShippingQuote');

			if ($hasTransit) {
				$GLOBALS['DeliveryDisclaimer'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('CartShippingDeliveryDisclaimer');
			}

			echo $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('EstimatedShippingQuoteList');
		}

		private function GetCountryStates()
		{
			$country = $_REQUEST['c'];
			echo GetStateList($country);
		}

		private function GetExchangeRate()
		{
			if (!array_key_exists("currencyid", $_REQUEST)
				|| !($result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]currencies WHERE currencyid = " . (int)$_REQUEST['currencyid']))
				|| !($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result))) {
				exit;
			}

			print $row['currencyexchangerate'];
			exit;
		}

		public function GetStateList()
		{
			if (!array_key_exists('countryName', $_POST) || $_POST['countryName'] == '') {
				$tags[] = $this->MakeXMLTag('status', 0);
				$this->SendXMLHeader();
				$this->SendXMLResponse($tags);
				exit;
			}

			$tags[] = $this->MakeXMLTag('status', 1);
			$tags[] = '<options>';

			$query = "SELECT statename
						FROM [|PREFIX|]countries c
							JOIN [|PREFIX|]country_states s ON c.countryid = s.statecountry
						WHERE c.countryname='" . $GLOBALS['ISC_CLASS_DB']->Quote($_POST['countryName']) . "'
						ORDER BY statename ASC";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$tags[] = '<option>';
				$tags[] = $this->MakeXMLTag('name', $row['statename'], true);
				$tags[] = '</option>';
			}

			$tags[] = '</options>';
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}

		private function GetCountryList()
		{
			$tags[] = $this->MakeXMLTag('status', 1);
			$tags[] = '<options>';

			$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]countries ORDER BY countryname ASC");
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$tags[] = '<option>';
				$tags[] = $this->MakeXMLTag('name', $row['countryname'], true);
				$tags[] = '</option>';
			}

			$tags[] = '</options>';
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}

		/**
		* Handles adding products from the list display mode
		*
		*/
		private function AddProductsToCart()
		{
			$response = array();

			if (isset($_REQUEST['products'])) {
				/** @var ISC_CART */
				$cart = GetClass('ISC_CART');

				$products = explode("&", $_REQUEST["products"]);

				foreach ($products as $product) {
					list($id, $qty) = explode("=", $product);
					if (!$cart->AddSimpleProductToCart($id, $qty)) {
						$response["error"] = $_SESSION['AddProductErrorMessage'];
					}
				}
			}

			echo isc_json_encode($response);
			exit;
		}


		public function ProcessRemoteActions()
		{

			if(!isset($_REQUEST['provider'])) {
				$tags[] = $this->MakeXMLTag('errorMsg', GetLang('ExpressCheckoutLoadError')."1");
				$this->SendXMLHeader();
				$this->SendXMLResponse($tags);
				exit;
			}
			if(!GetModuleById('checkout', $provider, $_REQUEST['provider'])) {
				$tags[] = $this->MakeXMLTag('errorMsg', GetLang('ExpressCheckoutLoadError')."2");
				$this->SendXMLHeader();
				$this->SendXMLResponse($tags);
				exit;
			}

			// This gateway doesn't support remote actions
			if(!method_exists($provider, 'ProcessRemoteActions')) {
				$tags[] = $this->MakeXMLTag('errorMsg', GetLang('ExpressCheckoutLoadError')."3");
				$this->SendXMLHeader();
				$this->SendXMLResponse($tags);
				exit;
			}

			$result = $provider->ProcessRemoteActions();
			$tags[] = $this->MakeXMLTag('errorMsg', $result['error']);
			$tags[] = $this->MakeXMLTag('data', isc_html_escape($result['data']));
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}

		private function sortAdvanceSearch()
		{
			if (!array_key_exists("section", $_REQUEST) || trim($_REQUEST["section"]) == "") {
				exit;
			}

			if (!array_key_exists("sortBy", $_REQUEST) || trim($_REQUEST["sortBy"]) == "") {
				exit;
			}

			$this->doAdvanceSearch();
		}

		private function ProcessCombinationImage($productId, $selections_concat, $modifiers_concat) {
			$query_layers = "SELECT layerid, option_name, filename FROM [|PREFIX|]product_image_layers WHERE productid = ".$productId;
			$result_layers = $GLOBALS['ISC_CLASS_DB']->Query($query_layers);
			
			$layers = array();
			while ($row_layers = $GLOBALS['ISC_CLASS_DB']->Fetch($result_layers)) {
				$layers[$row_layers['option_name']]['id'] = $row_layers['layerid'];
				$layers[$row_layers['option_name']]['filename'] = $row_layers['filename'];
			}

			$selectionIDs = '';
			$optionsArray = array();
			$optionsArray = explode(',', $selections_concat);
			$modifiersArray = explode(',', $modifiers_concat);
			
			foreach ($optionsArray as $value) {
				$selectionIDs .= array_key_exists($value, $layers) ? $layers[$value]['id'] . ',' : '';
			}
			$selectionIDs = substr($selectionIDs, 0, strlen($selectionIDs)-1);
			
			$combination_image = ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/combination_images/'.$productId.'/'.$selectionIDs.'-'.$modifiers_concat.'.png';
			$combination_image_std = ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/combination_images/'.$productId.'/'.$selectionIDs.'-'.$modifiers_concat.'.png';
			$return = array();
					
			if(empty($layers)) {
				return $return;
			}
			
			if(!file_exists(ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/combination_images/')) {
				isc_mkdir(ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/combination_images/', ISC_WRITEABLE_DIR_PERM);
			}
			if(!file_exists(ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/combination_images/'.$productId)) {
				isc_mkdir(ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/combination_images/'.$productId, ISC_WRITEABLE_DIR_PERM);
			}

			if(!file_exists($combination_image)) {
			
				$imagefile = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT imagefile FROM [|PREFIX|]product_images WHERE imageprodid = '.$productId, 'imagefile');
				
				copy(ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/'.$imagefile, $combination_image);
				chmod($combination_image, 0664);
				
				foreach($optionsArray as $key => $option) {
					$composite_cmd = 'composite -gravity center '.ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/layer_images/'.$layers[$option]['filename'] . '_'.$modifiersArray[$key].'.png ';
					$composite_cmd .= $combination_image . ' ' . $combination_image;
					
					exec($composite_cmd);
				}

				$width = GetConfig('ProductImagesProductPageImage_width');
				$height = GetConfig('ProductImagesProductPageImage_height');
				copy($combination_image, ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/combination_images/'.$productId.'/std-'.$selectionIDs.'-'.$modifiers_concat.'.png');
				$convert_cmd = 'convert '.$combination_image. ' -resize '.$width.'x'.$height.' '.ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/combination_images/'.$productId.'/std-'.$selectionIDs.'-'.$modifiers_concat.'.png';;
				exec($convert_cmd);
				
			}
			$return['std'] = GetConfig('ShopPath').'/'.GetConfig('ImageDirectory').'/combination_images/'.$productId.'/std-'.$selectionIDs.'-'.$modifiers_concat.'.png';
			$return['zoom'] = GetConfig('ShopPath').'/'.GetConfig('ImageDirectory').'/combination_images/'.$productId.'/'.$selectionIDs.'-'.$modifiers_concat.'.png'; 
		
			return $return;
		}
		
		private function getDeliveryDate($combinationid){
			if(!GetConfig('showDeliveryDateFromStatus')){
				return false;
			}
			
			$Situacion = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT Situacion FROM [|PREFIX|]intelisis_variation_combinations WHERE combinationid = "'.$combinationid.'"', 'Situacion');
			if(!$Situacion || $Situacion == ''){
				return 'No se encontraron datos sobre la situacion del articulo';
			}
			
			$result = $GLOBALS['ISC_CLASS_DB']->Query('SELECT DiasEntrega, PeriodoEntrega FROM [|PREFIX|]intelisis_prodstatus WHERE Situacion = "'.$Situacion.'"');
			if(!$result){
				return 'No se encontraron datos de la situacion del articulo';
			}
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			if($row['DiasEntrega'] == '' || $row['PeriodoEntrega'] == ''){
				return 'No se configuraron datos de la situacion del articulo';			
			}
	
			$date = getDeliveryDate($row['DiasEntrega'], $row['PeriodoEntrega']);
			return isc_date('d/M/Y', $date);
		}
		
		/**
		*
		* @param int The customer group to use to determine the final product price (used when getting variation details from back end quote system)
		*/
		public function GetVariationOptions($customerGroupId = null)
		{
			$productId = (int)$_GET['productId'];
			$optionIds = $_GET['options'];
			$optionIdsArray = array_map('intval', explode(',', $optionIds));

			// We need to find the next type of option that's selectable, so what we do
			// is because the vcoptionids column is in the order that the customer selects
			// the options, we just find a single matching option and then look up values
			// according to the voname.

			$query = "
				SELECT prodvariationid, vnumoptions
				FROM [|PREFIX|]products p
				JOIN [|PREFIX|]product_variations v ON (v.variationid=p.prodvariationid)
				WHERE p.productid='".$productId."'
			";
			$result =$GLOBALS['ISC_CLASS_DB']->query($query);
			$product = $GLOBALS['ISC_CLASS_DB']->fetch($result);

			// Invalid product variation, or product doesn't have a variation
			if(empty($product)) {
				exit;
			}
			
			if($_GET['selections'] != '' || $_GET['modifiers'] != '') {
				$selections = $_GET['selections'];
				$modifiers = $_GET['modifiers'];
				
				$LayerImage = $this->ProcessCombinationImage($productId, $selections, $modifiers);
			}			
			
			// If we received the number of options the variation has in, then the customer
			// has selected an entire row. Find that row.
			if(count($optionIdsArray) == $product['vnumoptions']) {
				$setMatches = array();
				foreach($optionIdsArray as $optionId) {
					$setMatches[] = 'FIND_IN_SET('.$optionId.', vcoptionids)';
				}
				$query = "
					SELECT *
					FROM [|PREFIX|]product_variation_combinations
					WHERE
						vcproductid='".$productId."' AND
						vcenabled=1 AND
						".implode(' AND ', $setMatches)."
					LIMIT 1
				";
				$result = $GLOBALS['ISC_CLASS_DB']->query($query);
				$combination = $GLOBALS['ISC_CLASS_DB']->fetch($result);

				$productClass = new ISC_PRODUCT($productId);
				$combinationDetails = $productClass->getCombinationDetails($combination, $customerGroupId);

				$combinationDetails['comboFound'] = true;
				
				/*
				 * REQ11552: NES - Obtenemos la fecha de entrega a partir de situacion del producto en esta
				 * combinacion especifica (puede ser diferente al producto padre) y la regresamos en el JSON
				 */
				if($date = $this->getDeliveryDate($combination['combinationid'])) {
					$combinationDetails['deliveryDateFromStatus'] = $date;
				}
				
				if(isset($LayerImage) && is_array($LayerImage) && !empty($LayerImage)) {
					unset($combinationDetails['thumb']);
					unset($combinationDetails['image']);
					$new_images = array (
						'thumb' => $LayerImage['std'],
						'image' => $LayerImage['std'],
						'zoom' => $LayerImage['zoom'],
					);
				$combinationDetails = array_merge($combinationDetails, $new_images);
				}
				
				// REQ10046 - Obtenemos el detalle de inventario por sucursal para regresar, con el SKU especifico de la combinacion
				if($productStockDetail = $this->ProductRefreshStock($combinationDetails['sku'], false)){
					$combinationDetails['stockDetail'] = $productStockDetail;
				}
				
				if ($combinationDetails['sku'] == null) {
					// prevent a blank sku on details page
					$combinationDetails['sku'] = '';
				}
				
				echo isc_json_encode($combinationDetails);
				exit;
			}

			// Try to find a combination row with the incoming option ID string, to determine
			// which set of options is next.
			$query = "
				SELECT DISTINCT voname
				FROM [|PREFIX|]product_variation_options
				WHERE
					vovariationid='".$product['prodvariationid']."'
				ORDER BY vooptionsort ASC
				LIMIT ".count($optionIdsArray).", 1
			";
			$optionName = $GLOBALS['ISC_CLASS_DB']->fetchOne($query);

			$hasOptions = false;
			$valueHTML = '';

			$setMatches = array();
			foreach($optionIdsArray as $optionId) {
				$setMatches[] = 'FIND_IN_SET('.$optionId.', vcoptionids)';
			}

			$query = "
				SELECT *
				FROM [|PREFIX|]product_variation_options
				WHERE
					vovariationid='".$product['prodvariationid']."' AND
					voname='".$GLOBALS['ISC_CLASS_DB']->quote($optionName)."'
				ORDER BY vovaluesort ASC
			";
			$result = $GLOBALS['ISC_CLASS_DB']->query($query);
			while($option = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
				$query = "
					SELECT combinationid
					FROM [|PREFIX|]product_variation_combinations
					WHERE
						vcproductid='".$productId."' AND
						vcenabled=1 AND
						FIND_IN_SET(".$option['voptionid'].", vcoptionids) > 0 AND
						".implode(' AND ', $setMatches)."
					LIMIT 1
				";
				// Ok, this variation option isn't in use for this product at the moment. Skip it
				if(!$GLOBALS['ISC_CLASS_DB']->fetchOne($query)) {
					continue;
				}

				$option = new Xhtml_Option($option['vovalue'], (int)$option['voptionid']);
				$valueHTML .= $option->render();
				$hasOptions = true;
			}
			
			$return = array(
				'hasOptions' 	=> $hasOptions,
				'options'		=> $valueHTML
			);
			
			if(isset($LayerImage) && is_array($LayerImage) && !empty($LayerImage)) {
				$new_images = array (
					'layersFound' => true,
					'thumb' => $LayerImage,
					'image' => $LayerImage,
					'zoom' => $LayerImage['zoom'],
				);
			$return = array_merge($return, $new_images);
			}

			echo isc_json_encode($return);
			exit;
		}



		/**
		* Updates the language file. Used by design mode
		*
		* @return void
		*/
		private function UpdateLanguage()
		{
			if(!getClass('ISC_ADMIN_AUTH')->isDesignModeAuthenticated()) {
				exit;
			}

			$name	= str_replace("lang_", "", $_REQUEST['LangName']);
			$value	= $_REQUEST['NewValue'];
			/*$value = str_replace(array("\n","\r"), "", $value);*/
			$value = str_replace('"', "&quot;", $value);

			$content = file_get_contents(ISC_BASE_PATH."/language/".GetConfig('Language')."/front_language.ini");
			$frontLang = parse_ini_file(ISC_BASE_PATH."/language/".GetConfig('Language')."/front_language.ini");

			$replacement = $name . ' = "' . str_replace('$', '\$', $value) . '"';
			$replace = preg_replace("#^\s*".preg_quote($name, "#")."\s*=\s*\"".preg_quote(@$frontLang[$name], "#").'"\s*$#im', $replacement, $content);

			if(file_put_contents(ISC_BASE_PATH."/language/".GetConfig('Language')."/front_language.ini", $replace)) {
				$tags[] = $this->MakeXMLTag('status',1);
				$tags[] = $this->MakeXMLTag('newvalue', $value, true);
			}else {
				$langFile = ISC_BASE_PATH.'/language/'.GetConfig('Language').'/admin/common.ini';
				ParseLangFile($langFile);
				$tags[] = $this->MakeXMLTag('status',0);
				$tags[] = $this->MakeXMLTag('message', GetLang('UpdateLanguage'));
			}

			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			die();
		}
	}
