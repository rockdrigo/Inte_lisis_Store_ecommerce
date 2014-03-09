<?php

class ISC_CUSTOMER
{
	public function HandlePage()
	{
		$action = "";
		if (isset($_REQUEST['action'])) {
			$action = isc_strtolower($_REQUEST['action']);
		}

		switch ($action)
		{
			//*** Option for activate accounts ***
			case "activate_account": {
				$this->ActivateAccount();
				break;
			}// ***
			case "change_password": {
				$this->SaveNewPassword();
				break;
			}
			case "send_password_email": {
				$this->SendPasswordEmail();
				break;
			}
			case "reset_password": {
				$this->ResetPassword();
				break;
			}
			case "check_login": {
				$this->CheckLogin();
				break;
			}
			case "save_new_account": {
				$this->CreateAccountStep2();
				break;
			}
			case "create_account": {
				$this->CreateAccountStep1();
				break;
			}
			case "logout": {
				$this->Logout();
				break;
			}
			default: {
				$this->ShowLoginPage();
			}
		}
	}

	/**
	 * Attempt to log the customer in to the store.
	 *
	 * @param boolean $silent Set to true to not show any error messages but return true or false depending on if the login was successful or not.
	 * @return boolean True if the login was successful.
	 */
	public function CheckLogin($silent=false)
	{
		if (isset($_POST['login_email']) && isset($_POST['login_pass'])) {
			$email = $GLOBALS['ISC_CLASS_DB']->Quote($_POST['login_email']);
			
			if (strpos($email, '@') === false) {
				$queryNum = sprintf("	SELECT customerid
										FROM [|PREFIX|]intelisis_customers
										WHERE Cliente='%s'", $email);
				$someVar = $GLOBALS['ISC_CLASS_DB']->fetchOne($queryNum);
				if (isset($someVar)) {
					$queryN = sprintf("	SELECT custconemail
										FROM [|PREFIX|]customers
										WHERE customerid='%s'", $someVar);
					$mail = $GLOBALS['ISC_CLASS_DB']->fetchOne($queryN);
					if (isset($mail)) {
						$email = $mail;
					}
				}
			}
			
			// Add field 'custnotes' in search
			$query = sprintf("select customerid, salt, custpassword, customertoken, custimportpassword, custnotes from [|PREFIX|]customers where custconemail='%s'", $email);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				// *** Verify if user is locked or inactive ***
				if ($row['custnotes'] == "INACTIVO") {
					$this->ShowLoginPage("ActivateAccountActivate", 1);
					die();
				}
				if ($row['custnotes'] == "BLOQUEADO") {
					$this->ShowLoginPage("LockedUserMsj", 1);
					die();
				}// *** End verify ***
				$customerid = $row['customerid'];
				$plainText = $_POST['login_pass'];

				$entity = new ISC_ENTITY_CUSTOMER();
				if (!$this->verifyPassword($row, $plainText)) {
					if ($row['custimportpassword'] != '') {
						if (ValidImportPassword($plainText, $row['custimportpassword'])) {
							// imported customer, convert password to isc version
							$entity->updatePassword($customerid, $plainText);
						} else {
							unset($row['customerid']);
						}
					} else {
						// normal user, password mismatch
						unset($row['customerid']);
						
						// Verify if user was created whith custnotes: "PASSWORD" (Intelisis User)
						if ($row['custnotes'] != "PASSWORD") {
							// *** Limit log in ***
							if ($row['custnotes'] == "INTENTO1" || $row['custnotes'] == "INTENTO2") {
								if ($row['custnotes'] == "INTENTO2") {
									//  Update 'custnotes' field, with string: "BLOQUEADO"
									$UpdateNotes = array("custnotes" => 'BLOQUEADO',);
									if ($GLOBALS['ISC_CLASS_DB']->UpdateQuery('customers', $UpdateNotes, 'customerid = "'.$customerid.'"')){
										$this->ResetPassword("limit_exceeded");
									}
									else {
										logAddError('No se pudo actualizar custnotes para BLOQUEAR al usuario!');
										$this->ShowLoginPage("InternalErrorUpdate", 1);
									}
								}
								else {
									//  Update 'custnotes' field, with string: "INTENTO2"
									$UpdateNotes = array("custnotes" => 'INTENTO2',);
									if ($GLOBALS['ISC_CLASS_DB']->UpdateQuery('customers', $UpdateNotes, 'customerid = "'.$customerid.'"')){
										$this->ShowLoginPage("LimitExceededCounter1", 1);
									}
									else {
										logAddError('No se pudo actualizar custnotes(INTENTO2) para bloquear al usuario!');
										$this->ShowLoginPage("InternalErrorUpdate", 1);
									}
								}
							}
							else {
								//  Update 'custnotes' field, with string: "INTENTO1"
								$UpdateNotes = array("custnotes" => 'INTENTO1',);
								if ($GLOBALS['ISC_CLASS_DB']->UpdateQuery('customers', $UpdateNotes, 'customerid = "'.$customerid.'"')) {
									$this->ShowLoginPage("LimitExceededCounter2", 1);
									exit;
								}
								else {
									logAddError('No se pudo actualizar custnotes(INTENTO1) para bloquear al usuario!');
									$this->ShowLoginPage("InternalErrorUpdate", 1);
								}
							}// *** End limit log in ***
							die();
						}
					}

				}

				// Login was OK, set the token as a cookie
				if (isset($row['customerid']) && $row['customerid'] != 0) {
					// Verify if user was created whith custnotes: "PASSWORD" (Intelisis User)
					if ($row['custnotes'] != "PASSWORD") {

						$UpdateData = array("custnotes" => '',);
						if ($GLOBALS['ISC_CLASS_DB']->UpdateQuery('customers', $UpdateData, 'customerid = "'.$customerid.'"')) {
							return $this->LoginCustomer($row, $silent);
						}
						else {
							logAddError('No se pudo actualizar custnotes para liberar y loggear al usuario!');
							$this->ShowLoginPage("InternalErrorUpdate", 1);
						}
					}
					else {
						return $this->LoginCustomer($row, $silent);
					}
				}
			}

			// Bad login credentials
			if($silent == true) {
				return false;
			}
			else {
					$this->ShowLoginPage("BadLoginDetails", 1);
			}
		}
		else {
			ob_end_clean();
			header(sprintf("Location: %s/login.php", $GLOBALS['ShopPath']));
			die();
		}
	}

	/**
	 * Login a customer based upon either their customer ID or record array
	 *
	 * @param mixed Either the customer's ID or record array.
	 * @param boolean Set to true to not show any error messages but return true or false depending on if the login was successful or not.
	 * @return boolean True if the login was successful.
	 */
	public function LoginCustomerById($ClientRecord, $silent=false)
	{
		if (!isId($ClientRecord) && !is_array($ClientRecord)) {
			return false;
		}

		return $this->LoginCustomer($ClientRecord, $silent);
	}

	/**
	 * Private function used for loggin in a customer
	 *
	 * @param mixed Either the customer's ID or record array.
	 * @param boolean Set to true to not show any error messages but return true or false depending on if the login was successful or not.
	 * @return boolean True if the login was successful.
	 */
	private function LoginCustomer($ClientRecord, $silent=false)
	{
		if (isId($ClientRecord)) {
			$ClientRecord = $GLOBALS['ISC_CLASS_DB']->Fetch($GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]customers WHERE customerid=" . (int)$ClientRecord));
		}

		if (!is_array($ClientRecord)) {
			return false;
		}

		// Check if user is inactive
		if (isset($ClientRecord['custnotes']) && $ClientRecord['custnotes'] == "INACTIVO") {
			$this->ShowLoginPage("ActivateAccountActivate", 1);
			die();
		}

		@ob_end_clean();
		if(!trim($ClientRecord['customertoken'])) {
			$custToken = GenerateCustomerToken();
			$updated_customer_token = array(
					"customertoken" => $custToken
			);
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery("customers", $updated_customer_token, "customerid='".$GLOBALS['ISC_CLASS_DB']->Quote($ClientRecord['customerid'])."'");
			$ClientRecord['customertoken'] = $custToken;
		}


		ISC_SetCookie("SHOP_TOKEN", $ClientRecord['customertoken'], time()+(3600*24*7), true);

		// Make the cookie accessible via PHP as well
		$_COOKIE['SHOP_TOKEN'] = $ClientRecord['customertoken'];

		// Also store it in the session as well when we're transferring the session between domains
		$_SESSION['SHOP_TOKEN'] = $ClientRecord['customertoken'];

		if($silent == true) {
			return true;
		}

		if (isset($_SESSION['LOGIN_REDIR']) && $_SESSION['LOGIN_REDIR'] != '') {
			// Take them to the page they wanted
			$page = $_SESSION['LOGIN_REDIR'];
			unset($_SESSION['LOGIN_REDIR']);
			header(sprintf("Location: %s", $page));
		}
		else {
			// Take them to the "My Account" page
			header(sprintf("Location: %s/account.php", $GLOBALS['ShopPathNormal']));
		}

		die();
	}

	/**
		*	Does an account the this email address already exist?
		*/
	public function AccountWithEmailAlreadyExists($Email, $customerId=0)
	{
		$query = "SELECT COUNT(custconemail) AS num
		FROM [|PREFIX|]customers
		WHERE custconemail='" . $GLOBALS['ISC_CLASS_DB']->Quote($Email) . "'";

		if (isId($customerId)) {
			$query .= " AND customerid != " . (int)$customerId;
		}

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if ($row['num'] == 0) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Parse out the phone number
	 *
	 * Method will parse out all the numbers within a string
	 *
	 * @access public
	 * @param string $number The phone number to validate
	 * @return string The numbers within a string
	 */
	public function ParsePhoneNumber($number)
	{
		if (preg_match_all("/[0-9]+/", $number, $matches)) {
			return implode("", $matches[0]);
		}

		return "";
	}

	/**
	 * Validate the phone number
	 *
	 * Method will validate the phone number.
	 *
	 * @access public
	 * @param string $number The phone number to validate
	 * @return bool true if the phone number is valid, false otherwise
	 */
	public function ValidatePhoneNumber($number)
	{
		return strlen($this->parsePhoneNumber($number)) >= 3;
	}

	private function CreateAccountStep2()
	{
		$savedataDetails = array(

				/**
				 * Customer Details
		*/
				FORMFIELDS_FORM_ACCOUNT => array(
						'EmailAddress' => 'custconemail',
						'Password' => 'custpassword',
						'ConfirmPassword' => 'custconfirmpassword',
						'FirstName' => 'custconfirstname',
						'LastName' => 'custconlastname',
						'CompanyName' => 'custconcompany',
						'Phone' => 'custconphone',
						'Notes'	=> 'custnotes',
				),

				/**
				 * Shipping Details
		*/
				FORMFIELDS_FORM_ADDRESS => array(
						'FirstName' => 'shipfirstname',
						'LastName' => 'shiplastname',
						'CompanyName' => 'shipcompany',
						'AddressLine1' => 'shipaddress1',
						'AddressLine2' => 'shipaddress2',
						'City' => 'shipcity',
						'State' => 'shipstate',
						'Country' => 'shipcountry',
						'Zip' => 'shipzip',
						'Phone' => 'shipphone',
						'BuildingType' => 'shipdestination'
				)
		);

		/**
		 * Validate and map submitted field data in one loop
		 */
		$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT, true);
		$fields += $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ADDRESS, true);
		addRFCValidation($fields);
		$customerData = array();
		$shippingData = array();
		$password = '';
		$confirmPassword = '';

		foreach (array_keys($fields) as $fieldId) {

			/**
			 * Validate
			 */
			$errmsg = '';
			if (!$fields[$fieldId]->runValidation($errmsg)) {
				return $this->CreateAccountStep1($errmsg);
			}

			foreach ($savedataDetails as $type => $map) {

				/**
				 * Are we in the customer section or the shipping?
				 */
				if ($type == FORMFIELDS_FORM_ACCOUNT) {
					$referencedData =& $customerData;
				} else {
					$referencedData =& $shippingData;
				}

				/**
				 * We're only interested in the private custom fields here
				 */
				if (array_key_exists($fields[$fieldId]->record['formfieldprivateid'], $map)) {
					$label = $map[$fields[$fieldId]->record['formfieldprivateid']];
					$referencedData[$label] = $fields[$fieldId]->getValue();

					/**
					 * Store the values somewhere if this is a apssword/confirm-password field
					 */
					if ($fields[$fieldId]->record['formfieldprivateid'] == 'Password') {
						$password = $referencedData[$label];
					} else if ($fields[$fieldId]->record['formfieldprivateid'] == 'ConfirmPassword') {
						$confirmPassword = $referencedData[$label];
					}
				}
			}
		}

		/**
		 * Clean up some of the data
		 */
		if (isset($shippingData['shipstate'])) {
			$state = GetStateInfoByName($shippingData['shipstate']);
			if ($state) {
				$shippingData['shipstateid'] = $state['stateid'];
			} else {
				$shippingData['shipstateid'] = '';
			}
		}
		if (isset($shippingData['shipcountry'])) {
			$countryId = GetCountryByName($shippingData['shipcountry']);
			if (isId($countryId)) {
				$shippingData['shipcountryid'] = $countryId;
			} else {
				$shippingData['shipcountryid'] = '';
			}
		}
		if (isset($shippingData['shipdestination'])) {
			$data = $fields[$fieldId]->getValue();
			if (isc_strtolower($shippingData[$label]) == 'house') {
				$shippingData[$label] = 'residential';
			} else {
				$shippingData[$label] = 'commercial';
			}
		}

		// Does an account with this email address already exist?
		if ($this->AccountWithEmailAlreadyExists($customerData['custconemail'])) {
			$this->CreateAccountStep1("already_exists");
		}
		// Else is the provided phone number valid?
		else if (!$this->ValidatePhoneNumber($customerData['custconphone'])) {
			$this->CreateAccountStep1("invalid_number");
		}
		// Else the passwords don't match
		else if ($password !== $confirmPassword) {
			$this->CreateAccountStep1("invalid_passwords");
		}
		else {
			// Create the user account in the database
			$token = GenerateCustomerToken();
			$customerData['customertoken'] = $token;

			// Add in the form sessions here AFTER all the validation
			$accountFormSessionId = $GLOBALS['ISC_CLASS_FORM']->saveFormSession(FORMFIELDS_FORM_ACCOUNT);

			if (isId($accountFormSessionId)) {
				$customerData['custformsessionid'] = $accountFormSessionId;
			}

			$shippingFormSessionId = $GLOBALS['ISC_CLASS_FORM']->saveFormSession(FORMFIELDS_FORM_ADDRESS);

			if (isId($shippingFormSessionId)) {
				$shippingData['shipformsessionid'] = $shippingFormSessionId;
			}

			$customerData["addresses"] = array($shippingData);
			// When an account is created, the status will be 'INACTIVO'
			if(GetConfig('AccountCreationInactiveUsers')){
				$customerData['custnotes'] = 'INACTIVO';
			} 
			else {
				$customerData['custnotes'] = '';
			}

			$_SESSION['FROM_REG'] = 0;
			$customerId = $this->CreateCustomerAccount($customerData);

			if (isId($customerId)) {
				// If user is 'inactive' don't log
				if (isset($customerData['custnotes']) && $customerData['custnotes'] != "INACTIVO") {
					// The account was created, let's log them in automatically
					$this->LoginCustomerById($customerId, true);
					$GLOBALS['ActivateAccountActivateLinkMsj'] = '';
				}
				else {
					$GLOBALS['ActivateAccountActivateLinkMsj'] = GetLang('ActivateAccountActivateLink');
				}
				// Show the "thank you for registering" page
				if (isset($_SESSION['LOGIN_REDIR']) && $_SESSION['LOGIN_REDIR'] != '') {
					$GLOBALS['Continue'] = GetLang('ClickHereToContinue');
					$GLOBALS['ContinueLink'] = urldecode($_SESSION['LOGIN_REDIR']);
					$_SESSION['FROM_REG'] = 1;
				}
				// User has just registered (not in the middle of an order - click here to visit your account)
				else {
					if (isset ($customerData['custnotes']) && $customerData['custnotes'] != "INACTIVO") {
						$GLOBALS['Continue'] = GetLang('ClickHereContinueShopping');
						$GLOBALS['ContinueLink'] = $GLOBALS['ShopPath'];
					}
					else {
						$GLOBALS['Continue'] = '';
						$GLOBALS['ContinueLink'] = '';
					}
				}
				$GLOBALS['ISC_LANG']['CreateAccountThanksIntro'] = sprintf(GetLang('CreateAccountThanksIntro'), $GLOBALS['StoreName'], isc_html_escape($customerData['custconemail']));
				$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName') . " - " . GetLang('CreateAccountThanks'));
					
				if (!isset($_SESSION['IsCheckingOut'])) {
					// Take them to the default thank you page if they aren't checking out
					$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("createaccount_thanks");
					$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
				}
				else {
						
					/**
					 * This is an order so take them straight to the shipping provider page. Also save the
					 * shipping address here as we will need the custom fields
					 */
					if (getCustomerQuote()->getIsSplitShipping()) {
						header("Location: " . $GLOBALS['ShopPath'] . "/checkout.php?action=multiple");
					}
					else {
						header("Location: " . $GLOBALS['ShopPath'] . "/checkout.php");
					}
						
				}

				die();
			}
			else {
				// Couldn't create the account
				$this->CreateAccountStep1("database_error");
			}
		}
	}

	/**
	 * Actually create a customer account in the database.
	 *
	 * @param array An array of details about the customer.
	 * @param boolean True if a welcome email should be sent out to the customer.
	 * @param boolean True if this account is being created invisibily for the customer via the checkout.
	 * @return int The customer ID if successful.
	 */
	public function CreateCustomerAccount($Customer, $Email=true, $checkoutAccount=false)
	{
		$entity = new ISC_ENTITY_CUSTOMER();
		$customerId = $entity->add($Customer);

		if (!isId($customerId)) {
			return;
		}

		// Do we want to email this custome a copy of their registration details?
		if ($Email == true) {

			$emailTemplate = FetchEmailTemplateParser();
			$GLOBALS['FirstName'] = isc_html_escape($Customer['custconfirstname']);
			$GLOBALS['Email'] = isc_html_escape($Customer['custconemail']);
			$GLOBALS['Password'] = isc_html_escape($Customer['custpassword']);

			if($checkoutAccount) {
				$GLOBALS['ISC_LANG']['ThanksForRegisteringAtIntro'] = sprintf(GetLang('CheckoutAccountCreatedIntro'), $GLOBALS['StoreName']);
				$subject = GetLang('CheckoutAccountCreatedSubject');
				$GLOBALS['ISC_LANG']['THanksForRegisteringAt'] = GetLang('CheckoutAccountCreatedSubject');
			}
			else {
				$GLOBALS['ISC_LANG']['ThanksForRegisteringAtIntro'] = sprintf(GetLang('ThanksForRegisteringAtIntro'), $GLOBALS['StoreName']);
				$subject = GetLang('ThanksForRegisteringAt');
			}

			$GLOBALS['ISC_LANG']['ThanksForRegisteringEmailLogin'] = sprintf(GetLang('ThanksForRegisteringEmailLogin'), $GLOBALS['ShopPathSSL']."/account.php", $GLOBALS['ShopPathSSL']."/account.php", $GLOBALS['ShopPathSSL']."/account.php");

			// If the user is 'INACTIVO', will sent link for activate the account
			if (isset($Customer['custnotes']) && $Customer['custnotes'] == "INACTIVO") {
				$data = sprintf("k=%s", base64_encode(isc_html_escape($Customer['customertoken'])));
				$link = sprintf("%s/login.php?action=activate_account&%s", $GLOBALS['ShopPath'], $data);
				$GLOBALS['ActivateAccountEmailAdv'] = GetLang('ActivateAccountEmailSubject');
				$GLOBALS['ActivateAccountMsjLink'] = sprintf(GetLang('ActivateAccountMsj'), $GLOBALS['StoreName'], $link, $link);
			}
			else {
				$GLOBALS['ISC_LANG']['ActivateAccountEmailAdv'] = '';
				$GLOBALS['ISC_LANG']['ActivateAccountMsjLink'] = '';
			}

			$emailTemplate->SetTemplate("createaccount_email");
			$message = $emailTemplate->ParseTemplate(true);

			// Create a new email API object to send the email
			$store_name = GetConfig('StoreName');

			require_once(ISC_BASE_PATH . "/lib/email.php");
			$obj_email = GetEmailClass();
			$obj_email->Set('CharSet', GetConfig('CharacterSet'));
			$obj_email->From(GetConfig('OrderEmail'), $store_name);
			$obj_email->Set("Subject", $subject . $store_name);
			$obj_email->AddBody("html", $message);
			$obj_email->AddRecipient($Customer['custconemail'], "", "h");
			$email_result = $obj_email->Send();

		}

		return $customerId;
	}

	/**
		*	Show the create account form. If $AlreadyExists is true then
		*	they've tried to create an account with an existing email address
		*/
	private function CreateAccountStep1($Error = "")
	{
		$fillPostedValues = false;
		if ($Error != "") {
			$fillPostedValues = true;
			$GLOBALS['HideCreateAccountIntroMessage'] = "none";
		}

		$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT, $fillPostedValues);
		$fields += $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ADDRESS, $fillPostedValues);

		/**
		 * Get any selected country and state
		 */
		$countryName = GetConfig('CompanyCountry');
		$stateFieldId = 0;
		foreach (array_keys($fields) as $fieldId) {
			if (isc_strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'state') {
				$stateFieldId = $fieldId;
			} else if (isc_strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'country' && $fields[$fieldId]->getValue() !== '') {
				$countryName = $fields[$fieldId]->getValue();
			}
		}

		/**
		 * Compile the fields. Also set the country and state dropdowns while we are here
		 */
		$GLOBALS['CreateAccountEmailPassword'] = '';
		$GLOBALS['CreateAccountDetails'] = '';
		$GLOBALS['CreateAccountAccountFormFieldID'] = FORMFIELDS_FORM_ACCOUNT;
		$GLOBALS['CreateAccountShippingFormFieldID'] = FORMFIELDS_FORM_ADDRESS;

		$compiledFields = null;
		$accountFields = array();
		$shippingFields = array();

		/**
		 * These are used for error reporting
		 */
		$emailAddress = '';
		$phoneNo = '';

		foreach (array_keys($fields) as $fieldId) {
			if (isc_strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'emailaddress') {
				$emailAddress = $fields[$fieldId]->getValue();
			}

			if (isc_strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'phone') {
				$phoneNo = $fields[$fieldId]->getValue();
			}

			if (isc_strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'country') {
				$fields[$fieldId]->setOptions(array_values(GetCountryListAsIdValuePairs()));

				if ($countryName !== '') {
					$fields[$fieldId]->setValue($countryName);
				}

				$fields[$fieldId]->addEventHandler('change', 'FormFieldEvent.SingleSelectPopulateStates', array('countryId' => $fieldId, 'stateId' => $stateFieldId));

			} else if (isc_strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'state' && $countryName !== '') {
				$countryId = GetCountryByName($countryName);
				$stateOptions = GetStateListAsIdValuePairs($countryId);
				if (is_array($stateOptions) && !empty($stateOptions)) {
					$fields[$fieldId]->setOptions($stateOptions);
				}
				else {
					// no states for our country, we need to mark this as not required
					$fields[$fieldId]->setRequired(false);
				}
			} else if (isc_strtolower($fields[$fieldId]->record['formfieldlabel']) == isc_strtolower(GetLang('StoreOriginLabel'))) {
					$fields[$fieldId]->setOptions(array_values(explode(',', getStoreOriginOptions())));
					$GLOBALS['StoreOriginDefault'] = getDefaultStoreId();
					$GLOBALS['StoreOriginNameDefault'] = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT Nombre FROM [|PREFIX|]intelisis_Sucursal WHERE Sucursal = "'.$GLOBALS['StoreOriginDefault'].'"', 'Nombre');
			} else if (isc_strtolower($fields[$fieldId]->record['formfieldlabel']) == isc_strtolower(GetLang('DefaultShippingMethodLabel'))) {
					$fields[$fieldId]->setOptions(array_values(explode(',', getDefaultShippingMethodOptions())));
			}

			/**
			 * We don't want this in the address (its only for single page checkout)
			 */
			if (isc_strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'savethisaddress' || isc_strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'shiptoaddress') {
				continue;
			}

			/**
			 * If this is a password field then remove that 'leave blank' label
			 */
			if ($fields[$fieldId]->getFieldType() == 'password') {
				$fields[$fieldId]->setLeaveBlankLabel(false);
			}

			/**
			 * Separate out the fields
			 */
			if ($fields[$fieldId]->record['formfieldformid'] == FORMFIELDS_FORM_ACCOUNT) {
				$GLOBALS['CreateAccountEmailPassword'] .= $fields[$fieldId]->loadForFrontend();
			} else {
				$GLOBALS['CreateAccountDetails'] .= $fields[$fieldId]->loadForFrontend();
			}
		}

		if ($Error == "already_exists") {
			// The email address is taken, they have to choose another one
			$GLOBALS['ErrorMessage'] = sprintf(GetLang('AccountEmailTaken'), isc_html_escape($emailAddress));
		}
		else if ($Error == "invalid_number") {
			// The phone number is invalid
			$GLOBALS['ErrorMessage'] = sprintf(GetLang('AccountEnterValidPhone'), isc_html_escape($phoneNo));
		}
		else if ($Error == "invalid_passwords") {
			// The passwords do not match
			$GLOBALS['ErrorMessage'] = GetLang('AccountPasswordsDontMatch');
		}
		else if ($Error == "database_error") {
			// A database error occured while creating the account
			$GLOBALS['ErrorMessage'] = GetLang('AccountInternalError');
		}
		else if ($Error !== '') {
			// Some other error while validating the field data. Should already be escaped
			$GLOBALS['ErrorMessage'] = $Error;
		}
		else {
			$GLOBALS['HideCreateAccountErrorMessage'] = "none";
		}

		// Get the id of the customer
		$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
		$customer_id = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();

		/**
		 * Load up any form field JS event data and any validation lang variables
		 */
		$GLOBALS['FormFieldRequiredJS'] = $GLOBALS['ISC_CLASS_FORM']->buildRequiredJS();

		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName') . " - " . GetLang('CreateAccount'));
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("createaccount");
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}

	/**
		*	Show the account login page
		*/

	public function ShowLoginPage($Message = "", $MessageStatus = 0, $MessageIsString = false)
	{
		// Show the typing e-mail
		if (isset($_POST['login_email'])) {
			$GLOBALS['LoginEmailAddress'] = $_POST['login_email'];
		}
		else {
			$GLOBALS['LoginEmailAddress'] = '';
		}

		if (isset($_GET['from'])) {
			$Message = "LoginToAccessThatPage";
			$_SESSION['LOGIN_REDIR'] = sprintf("%s/%s", $GLOBALS['ShopPath'], urldecode($_GET['from']));
		}
		else {
			$_SESSION['LOGIN_REDIR'] = '';
		}

		// Do we need to show a message?
		if ($Message != "") {
			if ($MessageIsString) {
				$GLOBALS['LoginMessage'] = $Message;
			} else {
				$GLOBALS['LoginMessage'] = GetLang($Message);
			}
		}
		else {
			// Hide the error box
			$GLOBALS['HideLoginMessage'] = "none";
		}

		// Is it a critical message?
		if($MessageStatus == 1) {
			$GLOBALS['MessageClass'] = "ErrorMessage";
		} else {
			$GLOBALS['MessageClass'] = "SuccessMessage";
		}

		if(!$Message) {
			$messages = getFlashMessages();
			if(!empty($messages)) {
				$message = $messages[0];
				$GLOBALS['LoginMessage'] = $message['message'];
				$GLOBALS['MessageClass'] = $message['class'];
				$GLOBALS['HideLoginMessage'] = '';
			}
		}

		//if (isset($_POST['login_email']) == "") {
			// Show the login page	
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName') . " - " . GetLang('Login'));
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("login");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		//}
		//else {
			//$custString = $_POST['login_email'];
			 //$this->CheckLogin();
		//}
	}

	/**
		*	Show the reset password form
		*/
	private function ResetPassword($Error = "")
	{

		if ($Error == "bad_email") {
			// There's no account with that email address
			$GLOBALS['ErrorMessage'] = sprintf(GetLang('ForgotPasswordBadEmail'), isc_html_escape($_POST['email']));
		}
		else if ($Error == "invalid_link") {
			// The link in the email is invalid
			$GLOBALS['ErrorMessage'] = GetLang('ForgotPasswordInvalidLink');
		}
		else if ($Error == "internal_error") {
			// There was a database error
			$GLOBALS['ErrorMessage'] = GetLang('ForgotPasswordInternalErrror');
		}
		// Limit exceeded for login
		else	if ($Error == "limit_exceeded") {
			$GLOBALS['ErrorMessage'] = GetLang('LimitExceeded');
		}
		else {
			$GLOBALS['HideForgotPasswordError'] = "none";
		}

		// Show the reset password page
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName') . " - " . GetLang('ForgotPassword'));
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("forgotpassword");
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}

	/**
		*	Send the email to confirm the change
		*/
	private function SendPasswordEmail()
	{
		/*
		 Include the email API class
		*/

		if (isset($_POST['email'])) {
			$email = $_POST['email'];

			// Does an account with the email address exist?
			if ($this->AccountWithEmailAlreadyExists($email)) {
				// Is the current password right?
				$query = sprintf("select customerid, customertoken from [|PREFIX|]customers where custconemail='%s'", $GLOBALS['ISC_CLASS_DB']->Quote($email));
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {

					// The account exists, let's create a new temporary token to be used to verify the email that will be sent
					$customer_id = $row['customerid'];
					$storeRandom = md5(uniqid(mt_rand(), true) . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_TIME']);
					$linkRandom = $this->generateCustomerHash($storeRandom, $customer_id);
					$UpdatedCustomer = array(
							"customerpasswordresettoken" => $storeRandom,
							"customerpasswordresetemail" => $email,
					);

					if ($GLOBALS['ISC_CLASS_DB']->UpdateQuery("customers", $UpdatedCustomer, "customerid='".$GLOBALS['ISC_CLASS_DB']->Quote($customer_id)."'")) {
						// Send the email
						$data = sprintf("c=%d&t=%s", $customer_id, $linkRandom);
						$link = sprintf("%s/login.php?action=change_password&%s", $GLOBALS['ShopPath'], $data);
						$store_name = GetConfig('StoreName');
						$email_message = sprintf(GetLang('ForgotPassEmailMessage'), $store_name, $link, $link);

						// Create a new email API object to send the email
						require_once(ISC_BASE_PATH . "/lib/email.php");
						$obj_email = GetEmailClass();
						$obj_email->Set('CharSet', GetConfig('CharacterSet'));
						$obj_email->From(GetConfig('OrderEmail'), $store_name);
						$obj_email->Set("Subject", sprintf(GetLang('ForgotPassEmailSubject'), $store_name));
						$obj_email->AddBody("html", $email_message);
						$obj_email->AddRecipient($email, "", "h");
						$email_result = $obj_email->Send();

						// If the email was sent ok, show a confirmation message
						if ($email_result['success']) {
							flashMessage(getLang('ForgotPassEmailSent'), MSG_SUCCESS, 'login.php#login');
						}
						else {
							// Email error
							$this->ResetPassword("internal_error");
						}
					}
					else {
						// Database error
						$this->ResetPassword("internal_error");
					}
				}
				else {
					// Bad password
					$this->ResetPassword("bad_password");
				}
			}
			else {
				// No account with that email address
				$this->ResetPassword("bad_email");
			}
		}
		else {
			$this->ResetPassword();
		}
	}

	/**
	*	Funtion used for Activate the Account
	*/

		private function ActivateAccount() {
		
			if (isset($_GET['k'])) {
		
				$customerTkn = base64_decode(isc_html_escape($_GET['k']));
		
				$tkn_query = "SELECT customertoken, customerid, custnotes
                        FROM [|PREFIX|]customers
                        WHERE customertoken='".$customerTkn."'";
				$tkn_result = $GLOBALS['ISC_CLASS_DB']->Query($tkn_query);
				$tkn_row = $GLOBALS['ISC_CLASS_DB']->Fetch($tkn_result);

				if (!$tkn_row) {
					return $this->ShowLoginPage("AccountInternalError", 1);
					die();
				}

				if ($tkn_row['custnotes'] == "BLOQUEADO") {
					$this->ShowLoginPage("LockedUserMsj", 1);
					die();
				}
				
				if($tkn_row['customertoken'] != $customerTkn){
					return $this->ShowLoginPage("ActivateAccountInvalidLink", 1);
					die();
				}				
				else {
					$UpdateData = array("custnotes" => '',);
					if ($GLOBALS['ISC_CLASS_DB']->UpdateQuery('customers', $UpdateData, 'customerid = "'.$tkn_row['customerid'].'"')) {
						return $this->LoginCustomerById($tkn_row['customerid'], false);
					}
					else {
						logAddError('No se activo la cuenta de usuario!');
						return $this->ShowLoginPage("ActivateAccountNoUpdate", 1);
					}
				}
		
			} else {
				$this->ShowLoginPage("ActivateAccountInvalidLink", 1);
			}
		
		}


	/**
	 * Save the new password for the customer's account (via link in reset password email)
	 */
	private function SaveNewPassword()
	{

		if (isset($_GET['c']) && isset($_GET['t'])) {

			$customerId = (int)isc_html_escape($_GET['c']);
			$customerHash = isc_html_escape($_GET['t']);

			$query = "SELECT *
			FROM [|PREFIX|]customers
			WHERE customerid=" . $customerId;
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$customer = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			// Can't find them in the database
			if (!isId($customerId) || !$customer) {
				return $this->ResetPassword("invalid_link", 1);
			}

			// Also check to see if our salted string matches this customer
			if (!$this->checkCustomerHash($customerHash, $customer['customerpasswordresettoken'], $customerId)) {
				return $this->ResetPassword("invalid_link", 1);
			}

			// OK, all the arguments are cool. Now we generate a password for them
			$password = Interspire_String::generateReadablePassword();
			$updateData = array(
					'customerpasswordresettoken' => '',
					'customerpasswordresetemail' => '',
					'custnotes' => '',
			);

			if ($GLOBALS['ISC_CLASS_DB']->UpdateQuery('customers', $updateData, 'customerid=' . $customerId) === false) {
				return $this->ResetPassword("internal_error", 1);
			}

			$entity = new ISC_ENTITY_CUSTOMER();
			$entity->updatePassword($customerId, $password);

			// Send the email
			$store_name = GetConfig('StoreName');
			$email_message = sprintf(GetLang('ForgotPasswordEmailConfirmed'), $store_name, $password);

			// Create a new email API object to send the email
			require_once(ISC_BASE_PATH . "/lib/email.php");
			$obj_email = GetEmailClass();
			$obj_email->Set('CharSet', GetConfig('CharacterSet'));
			$obj_email->From(GetConfig('OrderEmail'), $store_name);
			$obj_email->Set("Subject", sprintf(GetLang('ForgotPasswordEmailConfirmedSubject'), $store_name));
			$obj_email->AddBody("html", $email_message);
			$obj_email->AddRecipient($customer['customerpasswordresetemail'], "", "h");
			$email_result = $obj_email->Send();

			if ($email_result['success']) {
				return $this->ShowLoginPage(sprintf(GetLang('ForgotPasswordChanged'), $customer['customerpasswordresetemail']), 0, true);
			} else {
				return $this->ResetPassword("internal_error", 1);
			}
		} else {
			$this->ShowLoginPage();
		}
	}

	/**
	 * Log the current customer out of the store.
	 *
	 * @param boolean Set to true to do a silent logout (not redirect the customer, etc). Defaults to false.
	 */
	public function Logout($silent=false)
	{
		ISC_UnsetCookie("SHOP_TOKEN");
		unset($_COOKIE['SHOP_TOKEN']);
		unset($_SESSION['SHOP_TOKEN']);

		// If performing a silent logout, just stop here and return
		if($silent == true) {
			return true;
		}

		$GLOBALS['LoginOrLogoutLink'] = "login.php";
		if (strtolower(GetConfig('CustomerFunctionality')) == 'login') {
			$GLOBALS['LoginOrLogoutText'] = sprintf(GetLang('SignIn'), $GLOBALS['ShopPath']);
		} else {
			$GLOBALS['LoginOrLogoutText'] = sprintf(GetLang('SignInOrCreateAccount'), $GLOBALS['ShopPath'], '', $GLOBALS['ShopPath'], '');
		}

		$this->ShowLoginPage("LoggedOutSuccessfully");
	}

	/**
		* Get the ID of the customer based on the STORE_TOKEN cookie
		*/
	public function GetCustomerId()
	{
		$shop_token = '';
		if (isset($_COOKIE['SHOP_TOKEN'])) {
			$shop_token = $_COOKIE['SHOP_TOKEN'];
		}
		elseif (isset($_SESSION['SHOP_TOKEN'])) {
			$shop_token = $_SESSION['SHOP_TOKEN'];
			$_COOKIE['SHOP_TOKEN'] = $shop_token;
		}

		if ($shop_token) {
			return $this->GetCustomerIdByToken($shop_token);
		}

		return 0;
	}

	/**
		* Fetch all of the information from the customers table for the current customer
		*/
	public function GetCustomerDataByToken($Token="")
	{
		static $customerCache;
		$customer_id = 0;

		$shop_token = '';

		if($Token == '') {
			if (isset($_COOKIE['SHOP_TOKEN'])) {
				$shop_token = $_COOKIE['SHOP_TOKEN'];
			}
			elseif (isset($_SESSION['SHOP_TOKEN'])) {
				$shop_token = $_SESSION['SHOP_TOKEN'];
				$_COOKIE['SHOP_TOKEN'] = $shop_token;
			}
		}
		else {
			$shop_token = $Token;
		}

		if($shop_token == '') {
			return false;
		}

		// Been cached already? Return that
		if(isset($customerCache[$shop_token])) {
			return $customerCache[$shop_token];
		}

		$query = sprintf("select * from [|PREFIX|]customers where customertoken='%s'", $GLOBALS['ISC_CLASS_DB']->Quote($shop_token));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$customerCache[$shop_token] = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		return $customerCache[$shop_token];
	}

	/**
		* Get the ID of the customer based on the token
		*/
	public function GetCustomerIdByToken($Token)
	{
		$customer = $this->GetCustomerDataByToken($Token);
		if(is_array($customer)) {
			return $customer['customerid'];
		}

		return 0;
	}

	/**
		*	Return a list of shipping addresses for this customer as an arary
		*/
	public function GetCustomerShippingAddresses($customerId=null)
	{
		$addresses = array();

		if(is_null($customerId)) {
			$customerId = $this->GetCustomerId();
		}

		if(!$customerId) {
			return array();
		}

		$query = "
		SELECT *
		FROM [|PREFIX|]shipping_addresses
		WHERE shipcustomerid='".(int)$customerId."'
		ORDER BY shiplastused DESC
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$row['shipfullname'] = trim($row['shipfirstname'].' '.$row['shiplastname']);
			$addresses[$row['shipid']] = $row;
		}

		return $addresses;
	}

	/**
		*	Return the customer's email address
		*/
	public function GetCustomerEmailAddress()
	{
		$email = "";
		$customer_id = $this->GetCustomerId();

		if ($customer_id > 0) {
			$query = sprintf("select custconemail from [|PREFIX|]customers where customerid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($customer_id));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$email = $row['custconemail'];
			}
		}

		return $email;
	}

	/**
		*	Return an entire profile for a customer based on an id. If no ID specified, fetches the details for the current customer.
		*/
	public function GetCustomerInfo($customer_id=0)
	{
		if ($customer_id == 0) {
			$customer_id = $this->GetCustomerId();
		}

		if ($customer_id > 0) {
			$query = sprintf("select * from [|PREFIX|]customers where customerid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($customer_id));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				return $row;
			}
		}
		return false;
	}

	/**
	 * Return the amount of store credit a particular customer has.
	 *
	 * @param int The customer ID to fetch the amount of credit for. If not provided, the current customer is used.
	 * @return float The amount of store credit the customer has.
	 */
	public function GetCustomerStoreCredit($customerid=0)
	{
		if ($customerid == 0) {
			$customerid = $this->GetCustomerId();
		}

		if ($customerid > 0) {
			$query = sprintf("SELECT custstorecredit FROM [|PREFIX|]customers WHERE customerid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($customerid));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$credit = $row['custstorecredit'];
			}
		}

		return $credit;
	}

	/**
	 * Get a particular customer group based on the group Id. If null, the group of the current customer is fetched.
	 *
	 * @param int The customer group id to fetch the group information for.
	 * @param array Information regarding the customer group.
	 */
	public function GetCustomerGroup($groupId=null)
	{
		$groupCache = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('CustomerGroups');
		$group = false;

		if(is_null($groupId) && !defined('ISC_ADMIN_CP')) {
			$customer = $this->GetCustomerDataByToken();

			if(!empty($customer['customerid']) && !empty($customer['custgroupid'])) {
				$groupId = $customer['custgroupid'];
			}
			else if(empty($customer['customerid']) && GetConfig('GuestCustomerGroup') != 0) {
				$groupId = GetConfig('GuestCustomerGroup');
			}
			else if(isset($groupCache['default'])) {
				$groupId = $groupCache['default'];
			}
		}

		if(isset($groupCache[$groupId])) {
			return $groupCache[$groupId];
		}

		return false;
	}

	/**
	 * Get the sales tax information regarding the passed billing & shipping details.
	 *
	 * @param mixed Either an integer with the billing address or an array of details about the address.
	 * @param mixed Either an integer with the shipping address or an array of details about the address.
	 * @return array Array of information containing the tax data. (name, rate, based on etc)
	 */
	public function GetSalesTaxRate($billingAddress, $shippingAddress=0)
	{
		// Setup the array which will be returned
		$taxData = array(
				"tax_name" => "",
				"tax_rate" => 0,
				"tax_based_on" => "",
				"tax_id" => 0,
				"tax_shipping" => 0
		);

		// If tax is being applied globally, just return that
		if(GetConfig('TaxTypeSelected') == 2) {
			$basedOn = 'subtotal';
			if(GetConfig('DefaultTaxRateBasedOn')) {
				$basedOn = GetConfig('DefaultTaxRateBasedOn');
			}
			$taxData['tax_name'] = GetConfig('DefaultTaxRateName');
			$taxData['tax_rate'] = GetConfig('DefaultTaxRate');
			$taxData['tax_based_on'] = $basedOn;
			return $taxData;
		}

		$GLOBALS['ISC_CLASS_ACCOUNT'] = GetClass('ISC_ACCOUNT');
		$countryIds = array();
		$stateIds = array();
		if(!is_array($billingAddress)) {
			$billingAddress = $GLOBALS['ISC_CLASS_ACCOUNT']->GetShippingAddress($billingAddress);
		}

		if(!is_array($shippingAddress) && $shippingAddress > 0) {
			$shippingAddress = $GLOBALS['ISC_CLASS_ACCOUNT']->GetShippingAddress($shippingAddress);
		}

		// A billing address is required for every order. If we don't have one then there's no point in proceeding
		if(!is_array($billingAddress)) {
			return $taxData;
		}

		if(!isset($billingAddress['shipcountryid'])) {
			$billingAddress['shipcountryid'] = GetCountryIdByName($billingAddress['shipcountry']);
		}
		if(!isset($billingAddress['shipstateid'])) {
			$billingAddress['shipstateid'] = GetStateByName($billingAddress['shipstate'], $billingAddress['shipcountryid']);
		}

		if(is_array($shippingAddress)) {
			if(!isset($shippingAddress['shipcountryid'])) {
				$shippingAddress['shipcountryid'] = GetCountryIdByName($shippingAddress['shipcountry']);
			}
			if(!isset($shippingAddress['shipstateid'])) {
				$shippingAddress['shipstateid'] = GetStateByName($shippingAddress['shipstate'], $shippingAddress['shipcountryid']);
			}
		}

		// Do we have a matching state based tax rule?
		if($billingAddress['shipstateid'] || (is_array($shippingAddress) && $shippingAddress['shipstateid'])) {
			$query = "
			SELECT *
			FROM [|PREFIX|]tax_rates
			WHERE (1=0
			";
			if($billingAddress['shipstateid']) {
				$query .= " OR (taxaddress='billing' AND taxratecountry='".(int)$billingAddress['shipcountryid']."' AND taxratestates LIKE '%%,".(int)$billingAddress['shipstateid'].",%%')";
			}

			if(is_array($shippingAddress) && $shippingAddress['shipstateid']) {
				$query .= " OR (taxaddress='shipping' AND taxratecountry='".(int)$shippingAddress['shipcountryid']."' AND taxratestates LIKE '%%,".(int)$shippingAddress['shipstateid'].",%%')";
			}
			$query .= ") AND taxratestatus='1'";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			if(is_array($row)) {
				$taxData = array(
						'tax_name' =>		$row['taxratename'],
						'tax_rate' =>		$row['taxratepercent'],
						'tax_based_on' =>	$row['taxratebasedon'],
						'tax_id' =>		$row['taxrateid'],
						'tax_shipping' => $row['taxshippingfortaxableorder']
				);
				return $taxData;
			}
		}

		// Maybe we've got a matching country based rule
		$query = "
		SELECT *
		FROM [|PREFIX|]tax_rates
		WHERE (1=0 OR (taxratecountry='".(int)$billingAddress['shipcountryid']."' AND taxaddress='billing')
		";
		if(is_array($shippingAddress) && $shippingAddress['shipcountryid']) {
			$query .= " OR (taxratecountry='".(int)$shippingAddress['shipcountryid']."' AND taxaddress='shipping')";
		}
		$query .= ") AND taxratestatus='1' AND taxratestates = ',0,'";

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		if(is_array($row)) {
			$taxData = array(
					'tax_name' =>		$row['taxratename'],
					'tax_rate' =>		$row['taxratepercent'],
					'tax_based_on' =>	$row['taxratebasedon'],
					'tax_id' =>		$row['taxrateid'],
					'tax_shipping' => $row['taxshippingfortaxableorder']
			);
			return $taxData;
		}

		// Otherwise, if we still have nothing, perhaps we have a rule that applies to all countries
		$query = "
		SELECT *
		FROM [|PREFIX|]tax_rates
		WHERE taxratecountry='0' AND taxratestatus='1'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		if(is_array($row)) {
			$taxData = array(
					'tax_name' =>		$row['taxratename'],
					'tax_rate' =>		$row['taxratepercent'],
					'tax_based_on' =>	$row['taxratebasedon'],
					'tax_id' =>		$row['taxrateid'],
					'tax_shipping' => $row['taxshippingfortaxableorder']
			);
			return $taxData;
		}

		// Still here? Just return nothing!
		return $taxData;
	}

	/**
	 * Create a salted customer hash string
	 *
	 * Function will create a salted hash string used for customers
	 *
	 * @access public
	 * @param string $hash The unsalted hash string
	 * @param int $customerId The customer ID
	 * @return string The salted customer hash string on success, FALSE if $hash or $customerID is invalid/empty
	 */
	public function generateCustomerHash($hash, $customerId)
	{
		if ($hash == '' || !isId($customerId)) {
			return false;
		}

		$salt = 'CustomerID:' . $customerId;
		return Interspire_String::generateSaltedHash($hash, $salt);
	}

	/**
	 * Check to see if customer salt string matches
	 *
	 * Function will check to see if the unsalted customer hash string $customerString and the customer id $customerID match against the salted
	 * customer hash string $saltedString
	 *
	 * @access public
	 * @param string $saltedString The salted customer hash string to compare to
	 * @param string $customerString The unsalted customer hash string
	 * @param int $customerId The customer ID
	 * @return bool TRUE if the salted and unsalted strings match, FALSE if no match or if any of the arguments are invalid/empty
	 */
	public function checkCustomerHash($saltedString, $customerString, $customerId)
	{
		if ($saltedString == '' || $customerString == '' || !isId($customerId)) {
			return false;
		}

		$customerString = $this->generateCustomerHash($customerString, $customerId);

		if ($customerString === $saltedString) {
			return true;
		}

		return false;
	}

	/**
	 * Verify if this password is correct
	 *
	 * @param array  $customer The customer record array
	 * @param string $password The plain text password to verify
	 *
	 * @return boolean
	 */
	public function verifyPassword($customer, $password)
	{
		$reSalt = false;
		$plain = $password;
		if (strlen($customer['salt']) == 15) {
			// backward compatibility for auto salted password pre 6.0
			$password = md5($password);
			$reSalt = true;
		}

		$hash = getClass('ISC_ENTITY_CUSTOMER')->generatePasswordHash($password, $customer['salt']);
		if ($hash == $customer['custpassword']) {
			if ($reSalt) {
				// this will re-salt plain password with 16 len salt
				getClass('ISC_ENTITY_CUSTOMER')->updatePassword($customer['customerid'], $plain);
			}

			return true;
		}

		return false;

	}//end verifyPassword()

}
