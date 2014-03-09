<?php
class CHECKOUT_CREDITCARDMANUALLY extends ISC_CHECKOUT_PROVIDER
{
	private $_card_types = array(
		'VISA' => array(
			'type' => 'Visa',
			'regexp' => '^4[0-9]{15,18}$',
			'requiresCVV2' => true
		),
		'AMEX' => array(
			'type' => 'American Express',
			'regexp' => '^(34|37)[0-9]{13}$',
			'requiresCVV2' => true
		),
		'MC' => array(
			'type' => 'Mastercard',
			'regexp' => '^5[1-5]{1}[0-9]{14}$',
			'requiresCVV2' => true
		),
		'DINERS' => array(
			'type' => 'Diners Club',
			'regexp' => '^(30|36|38|55)[0-9]{12}([0-9]{2})?$',
			'requiresCVV2' => false
		),
		'DISCOVER' => array(
			'type' => 'Discover',
			'regexp' => '^6011[0-9]{12}$',
			'requiresCVV2' => true
		),
		'SOLO' => array(
			'type' => 'Solo',
			'regexp' => '^6767[0-9]{12}([0-9]{2,3})?$',
			'requiresCVV2' => true,
			'hasIssueNo' => true,
			'hasIssueDate' => true
		),
		'MAESTRO' => array(
			'type' => 'Maestro',
			'regexp' => '^(50[0-9]{4}|5[6-8][0-9]{4}|6[0-9]{5})[0-9]{6,13}$',
			'requiresCVV2' => true,
			'hasIssueNo' => true,
			'hasIssueDate' => true
		),
		'SWITCH' => array(
			'type' => 'Switch/Maestro UK',
			'regexp' => '^6759[0-9]{12}([0-9]{2,3})?$',
			'requiresCVV2' => true,
			'hasIssueNo' => true,
			'hasIssueDate' => true
		),
		'LASER' => array(
			'type' => 'Laser',
			'regexp' => '^(6304|6706|6771|6709)[0-9]{12,15}?$'
		),
		'JCB' => array(
			'type' => 'JCB',
			'regexp' => '^35(2[8-9]|[3-8][0-9])[0-9]{12}$',
			'requiresCVV2' => true
		)
	);

	protected $paymentType = PAYMENT_PROVIDER_OFFLINE;

	/**
	 * @var boolean Does this provider support orders from more than one vendor?
	 */
	protected $supportsVendorPurchases = true;

	/**
	 * @var boolean Does this provider support shipping to multiple addresses?
	 */
	protected $supportsMultiShipping = true;

	/*
		Does this payment provider require SSL?
	*/
	protected $requiresSSL = true;

	/*
		Checkout class constructor
	*/
	public function __construct()
	{
		// Setup the required variables for the manual credit card module
		parent::__construct();
		$this->_name = GetLang('CCManualName');
		$this->_description = GetLang('CCManualDesc');
		$this->_help = GetLang('CCManualHelp');
		$this->_height = 0;
	}

	/*
	 * Check if this checkout module can be enabled or not.
	 *
	 * @return boolean True if this module is supported on this install, false if not.
	 */
	public function IsSupported()
	{
		if(!function_exists("mcrypt_encrypt")) {
			$this->SetError(GetLang('CCManualErrorNoMCrypt'));
		}
		else if(!GetConfig('UseSSL')) {
			$this->SetError(GetLang('CCManualNoSSLError'));
		}

		if(!$this->HasErrors()) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	* Custom variables for the checkout module. Custom variables are stored in the following format:
	* array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
	* variable_type types are: text,number,password,radio,dropdown
	* variable_options is used when the variable type is radio or dropdown and is a name/value array.
	*/
	public function SetCustomVars()
	{
		$this->_variables['displayname'] = array(
			"name" => "Display Name",
			"type" => "textbox",
			"help" => GetLang('DisplayNameHelp'),
			"default" => $this->GetName(),
			"required" => true
		);

		$acceptedTypes = array();
		foreach($this->_card_types as $type => $options) {
			$acceptedTypes[$options['type']] = $type;
		}
		$defaultCardTypes = implode(",", array_keys($this->_card_types));

		$this->_variables['acceptedcards'] = array(
			"name" => "Accepted Card Types",
			"type" => "dropdown",
			"help" => GetLang('CCManualAcceptedCardTypesHelp'),
			"default" => $defaultCardTypes,
			"required" => true,
			"options" => $acceptedTypes,
			"multiselect" => true,
			'multiselectheight' => 12
		);
	}

	/**
	* ShowPaymentForm
	* Show a payment form for this particular gateway if there is one.
	* This is useful for gateways that require things like credit card details
	* to be submitted and then processed on the site.
	*/
	public function ShowPaymentForm()
	{
		$GLOBALS['CCMonths'] = "";
		$GLOBALS['CCYears'] = "";
		$GLOBALS['CCIssueDateMonths'] = $GLOBALS['CCIssueDateYears'] = '';
		$cc_type = "";

		// Get the credit card types
		if(isset($_POST['cc_cctype'])) {
			$cc_type = $_POST['cc_cctype'];
		}

		$GLOBALS['CCTypes'] = $this->_GetCCTypes($cc_type);

		for ($i = 1; $i <= 12; $i++) {
			$stamp = mktime(0, 0, 0, $i, 15, date("Y"));

			$i = str_pad($i, 2, "0", STR_PAD_LEFT);

			if (isset($_POST['cc_expm']) && $_POST['cc_ccexpm'] == $i) {
				$sel = 'selected="selected"';
			} else {
				$sel = "";
			}

			if(isset($_POST['cc_issuedatem']) && $_POST['cc_issuedatem'] == $i) {
				$issueSel = 'selected="selected"';
			}
			else {
				$issueSel = '';
			}

			$GLOBALS['CCMonths'] .= sprintf("<option %s value='%s'>%s</option>", $sel, $i, date("M", $stamp));
			$GLOBALS['CCIssueDateMonths'] .= sprintf("<option %s value='%s'>%s</option>", $issueSel, $i, date("M", $stamp));
		}

		for($i = date("Y"); $i <= date("Y")+10; $i++) {
			if(isset($_POST['cc_ccexpy']) && $_POST['cc_ccexpy'] == isc_substr($i, 2, 2)) {
				$sel = 'selected="selected"';
			}
			else {
				$sel = "";
			}
			$GLOBALS['CCYears'] .= sprintf("<option %s value='%s'>%s</option>", $sel, isc_substr($i, 2, 2), $i);
		}

		for($i = date("Y"); $i > date("Y")-5; --$i) {
			if(isset($_POST['cc_issuedatey']) && $_POST['cc_issuedatey'] == isc_substr($i, 2, 2)) {
				$sel = 'selected="selected"';
			}
			else {
				$sel = "";
			}
			$GLOBALS['CCIssueDateYears'] .= "<option value=\"".$i."\" ".$sel.">".$i."</option>";
		}

		// Grab the billing details for the order
		$billingDetails = $this->GetBillingDetails();

		$GLOBALS['CCName'] = isc_html_escape($billingDetails['ordbillfirstname'].' '.$billingDetails['ordbilllastname']);

		// Format the amount that's going to be going through the gateway
		$GLOBALS['OrderAmount'] = CurrencyConvertFormatPrice($this->GetGatewayAmount());

		// Was there an error validating the payment? If so, pre-fill the form fields with the already-submitted values
		if($this->HasErrors()) {
			$fields = array(
				"CCName" => 'cc_name',
				"CCNum" => 'cc_ccno',
				"CCIssueNo" => 'cc_issueno',
			);
			foreach($fields as $global => $post) {
				if(isset($_POST[$post])) {
					$GLOBALS[$global] = isc_html_escape($_POST[$post]);
				}
			}

			$cc_error = implode("<br />", $this->GetErrors());
			$GLOBALS['CCErrorMessage'] = $cc_error;
		}
		else {
			// Hide the error message box
			$GLOBALS['HideCCManualError'] = "none";
		}

		// Collect their details to send through to eWay
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("ccmanual");
		return $GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate(true);
	}

	/**
	* ProcessPaymentForm
	* Process and validate input from a payment form for this particular
	* gateway.
	*
	* @return boolean True if valid details and payment has been processed. False if not.
	*/
	public function ProcessPaymentForm()
	{
		if(isset($_POST['cc_ccno'])) {
			$cctype = $_POST['cc_cctype'];
			$ccno = $_POST['cc_ccno'];

			// Valid credit card type selected?
			if(!isset($this->_card_types[$cctype])) {
				$this->SetError(GetLang('CCManualBadCardType'));
				return false;
			}

			// Does this card type require a CVV2 code but one wasn't entered?
			if($this->CardTypeRequiresCVV2($cctype) && (!isset($_POST['cc_cvv2']) || strlen($_POST['cc_cvv2']) > 4 || strlen($_POST['cc_cvv2']) < 3)) {
				$this->SetError(GetLang('CCManualEnterCVV2Number'));
				return false;
			}


			// Was an expiry date provided that's in the past or not provided at all?
			if(!isset($_POST['cc_ccexpm']) || $_POST['cc_ccexpm']=='' || !isset($_POST['cc_ccexpy']) || $_POST['cc_ccexpy']=='' || ($_POST['cc_ccexpm'] < isc_date("m") && $_POST['cc_ccexpy'] == isc_date("y")) || $_POST['cc_ccexpy'] < isc_date("y")) {
				$this->SetError(GetLang('CCManualBadExpiryDate'));
				return false;
			}

			// Was an issue date requested, but is in the future?
			if($this->CardTypeHasIssueDate($cctype)) {
				$hasErrors = false;
				if(!isset($_POST['cc_issuedatem']) || !isset($_POST['cc_issuedatey'])) {
					$hasErrors = false;
				}
				else if(mktime(0, 0, 0, $_POST['cc_issuedatem'], 1, $_POST['cc_issuedatey']) > time()) {
					$hasErrors = true;
				}

				if($hasErrors == true) {
					$this->SetError(GetLang('CCManualBadIssueDate'));
					return false;
				}
			}

			// Is the card number valid?
			if($this->_ValidateCC($ccno, $cctype)) {

				$cc_vars = array("cc_name" => $_POST['cc_name'],
								 "cc_cctype" => $this->_card_types[$cctype]['type'],
								 "cc_ccno" => $this->_CCEncrypt($_POST['cc_ccno']),
								 "cc_ccexpm" => $_POST['cc_ccexpm'],
								 "cc_ccexpy" => $_POST['cc_ccexpy'],
				);

				if($this->CardTypeHasIssueNo($cctype)) {
					$cc_vars['cc_issueno'] = $this->_CCEncrypt($_POST['cc_issueno']);
				}

				if($this->CardTypeHasIssueDate($cctype)) {
					$cc_vars['cc_issuedatem'] = (int)$_POST['cc_issuedatem'];
					$cc_vars['cc_issuedatey'] = (int)$_POST['cc_issuedatey'];
				}

				// Update the orders to contain the credit card details
				$orders = $this->GetOrders();
				foreach($orders as $order) {
					// Is there any existing extra info for the pending order?
					if($order['extrainfo'] != "") {
						$extraInfo = @unserialize($order['extrainfo']);
						if(is_array($extraInfo)) {
							$extraInfo = @array_merge($extraInfo, $cc_vars);
						}
					}
					else {
						$extraInfo = $cc_vars;
					}

					$updated_order = array("extrainfo" => serialize($extraInfo));
					$GLOBALS['ISC_CLASS_DB']->UpdateQuery("orders", $updated_order, "orderid='" . $order['orderid'] . "'");
				}

				// Now return back and say we're all good for this order
				$this->SetPaymentStatus(PAYMENT_STATUS_PENDING);
				return true;
			}
			else {
				$this->SetError(GetLang('CCManualBadCardNumber'));
				return false;
			}
		}
		else {
			$this->SetError(GetLang('CCManualBadCardNumber'));
			return false;
		}
	}

	/**
	* _CCEncrypt
	* Encrypt the credit card number before it's stored in the database
	*
	* @param Int $CCNo The credit card number
	* @return String The encrypted card number
	*/
	private function _CCEncrypt($CCNo)
	{
		$iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$crypt = mcrypt_encrypt(MCRYPT_BLOWFISH, GetConfig('EncryptionToken'), $CCNo, MCRYPT_MODE_ECB, $iv);
		$crypt = base64_encode($crypt);
		return $crypt;
	}

	private function _CCDecrypt($CCEnc)
	{
		$CCEnc = base64_decode($CCEnc);
		$iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$decrypt = mcrypt_decrypt(MCRYPT_BLOWFISH, GetConfig('EncryptionToken'), $CCEnc, MCRYPT_MODE_ECB, $iv);
		$decrypt = rtrim($decrypt, "\0");
		return $decrypt;
	}

	/**
	* _ValidateCC
	* Make sure the credit card number entered was valid
	*
	* @param String $cc_num The credit card number to validate
	* @param String $type The type of card to validate against
	* @return Boolean
	*/
	private function _ValidateCC($cc_num, $type)
	{

		$verified = false;

		if(!isset($this->_card_types[$type])) {
			return false;
		}

		// Is this credit card type allowed?
		$card_types = $this->GetValue("acceptedcards");

		if(!is_array($card_types)) {
			if($card_types != '') {
				$card_types = array($card_types);
			}
			else {
				$card_types = array_keys($this->_card_types);
			}
		}

		if(!in_array($type, $card_types)) {
			return false;
		}

		$cardType = $this->_card_types[$type];

		if(isset($cardType['regexp'])) {
			if(preg_match("/".$cardType['regexp']."/", $cc_num)) {
				return true;
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}

	/**
	* _GetCCTypes
	* Get a list of credit card types and return them as options
	*
	* @param String $Selected The selected card type if the form was already posted
	* @return String
	*/
	private function _GetCCTypes($Selected="")
	{
		$options = "";

		// Get the enabled credit card types
		$card_types = $this->GetValue("acceptedcards");

		if(!is_array($card_types)) {
			if($card_types != '') {
				$card_types = array($card_types);
			}
			else {
				$card_types = array_keys($this->_card_types);
			}
		}

		// sort the cards alphabetically
		uasort($card_types, array($this, 'compare_cards'));

		foreach($card_types as $type) {
			$card = $this->_card_types[$type];
			if($Selected == $type) {
				$sel = "selected=\"selected\"";
			}
			else {
				$sel = "";
			}

			$class = '';
			if($this->CardTypeRequiresCVV2($type)) {
				$class .= ' requiresCVV2';
			}

			if($this->CardTypeHasIssueNo($type)) {
				$class .= ' hasIssueNo';
			}

			if($this->CardTypeHasIssueDate($type)) {
				$class .= ' hasIssueDate';
			}

			$options .= sprintf("<option id='CCType_%s' class='%s' value='%s' %s>%s</option>", $type, $class, $type, $sel, $card['type']);
		}

		return $options;
	}

	private function compare_cards($a, $b)
	{
		$a = $this->_card_types[$a];
		$b = $this->_card_types[$b];

		return strcasecmp($a['type'], $b['type']);
	}

	/**
	 * Check if a particular credit card type requires a CVV2/CSV code.
	 *
	 * @param string The type of the credit card to check.
	 * @return boolean True if a CVV2 code is required.
	 */
	private function CardTypeRequiresCVV2($type)
	{
		if(isset($this->_card_types[$type]['requiresCVV2']) && $this->_card_types[$type]['requiresCVV2']) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Check if a particular credit card type can contain an issue code.
	 *
	 * @param string The type of the credit card to check.
	 * @return boolean True if an issue code.
	 */
	private function CardTypeHasIssueNo($type)
	{
		if(isset($this->_card_types[$type]['hasIssueNo']) && $this->_card_types[$type]['hasIssueNo']) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Check if a particular credit card type can contain an issue date.
	 *
	 * @param string The type of the credit card to check.
	 * @return boolean True if an issue date is allowable.
	 */
	private function CardTypeHasIssueDate($type)
	{
		if(isset($this->_card_types[$type]['hasIssueDate']) && $this->_card_types[$type]['hasIssueDate']) {
			return true;
		}
		else {
			return false;
		}
	}

	public function handleRemoteAdminRequest()
	{
		if (empty($_POST['orderId'])) {
			exit;
		}

		$order = getOrder($_POST['orderId']);
		$extraInfo = @unserialize($order['extrainfo']);
		if (empty($order) && !is_array($extraInfo)) {
			exit;
		}

		unset($extraInfo['cc_ccno']);
		unset($extraInfo['cc_cvv2']);
		unset($extraInfo['cc_name']);
		unset($extraInfo['cc_ccaddress']);
		unset($extraInfo['cc_cczip']);
		unset($extraInfo['cc_cctype']);
		unset($extraInfo['cc_ccexpm']);
		unset($extraInfo['cc_ccexpy']);

		if(isset($extraInfo['cc_issueno'])) {
			unset($extraInfo['cc_issueno']);
		}

		if(isset($extraInfo['cc_issuedatey'])) {
			unset($extraInfo['cc_issuedatey']);
			unset($extraInfo['cc_issuedatem']);
			unset($extraInfo['cc_issuedated']);
		}

		$updatedOrder = array(
			"extrainfo" => serialize($extraInfo)
		);
		$GLOBALS['ISC_CLASS_DB']->UpdateQuery("orders", $updatedOrder, "orderid='".$order['orderid']."'");
		echo 1;
	}

	/**
	* DipslayPaymentDetails
	* Show any additional payment settings that this payment provider may have
	* saved in to the orders table. This is shown on the orders quick view page.
	*
	* @param array The array of order information.
	* @return string Any additional data this payment provider may want to show
	*/
	public function DisplayPaymentDetails($order)
	{
		if($order['extrainfo'] == '') {
			return '';
		}

		$extraInfo = @unserialize($order['extrainfo']);

		if(!isset($extraInfo['cc_ccno'])) {
			return '';
		}

		$ccNo = $this->_CCDecrypt($extraInfo['cc_ccno']);

		$issueDetails = '';
		if(isset($extraInfo['cc_issueno'])) {
			$issueDetails = '<tr>
				<td class="text" valign="top">'.GetLang('CCManualCreditCardIssueNo').':</td>
				<td class="text">'.$this->_CCDecrypt($extraInfo['cc_issueno']).'</td>
			</tr>';
		}

		if(isset($extraInfo['cc_issuedatey'])) {
			$issueDetails .= '<tr>
				<td class="text" valign="top">'.GetLang('CCManualIssueDate').':</td>
				<td class="text">'.$extraInfo['cc_issuedatem'].'/'.$extraInfo['cc_issuedatey'].'</td>
			</tr>';
		}

		$details = '
			<script type="text/javascript">
			function ClearCreditCardDetails(orderid) {
				$.ajax({
					url: "remote.php?remoteSection=orders&w=checkoutModuleAction",
					data: {
						module: "creditcardmanually",
						orderId: orderid,
					},
					type: "post",
					success: function() {
						$("#CCDetails_"+orderid).remove()
					}
				});
			}
			</script>
			<div id="CCDetails_'.$order['orderid'].'">
				<br />
				<h5>'.GetLang('CreditCardDetails').'</h5>
				<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td class="text" valign="top" width="120">'.GetLang('CCManualCardHoldersName').':</td>
					<td class="text">'.$extraInfo['cc_name'].'</td>
				</tr>
				<tr>
					<td class="text" valign="top">'.GetLang('CCManualCreditCardType').':</td>
					<td class="text">'.$extraInfo['cc_cctype'].'</td>
				</tr>
				<tr>
					<td class="text" valign="top">'.GetLang('CCManualCreditCardNo').':</td>
					<td class="text">'.$ccNo.'</td>
				</tr>
					'.$issueDetails.'
				<tr>
					<td class="text" valign="top">'.GetLang('CCManualExpirationDate').':</td>
					<td class="text">'.$extraInfo['cc_ccexpm'].'/'.$extraInfo['cc_ccexpy'].'</td>
				</tr>
				<tr>
					<td class="text" colspan="2" align="right"><input type="button" class="SmallButton" value="'.GetLang('CCManualClearDetails').'" onclick="ClearCreditCardDetails('.$order['orderid'].');" />&nbsp;&nbsp;</td>
				</tr>
				</table>
			</div>
		';
		return $details;
	}

	/**
	 * Return a list of any manual payment fields that should be shown when creating/editing
	 * an order via the control panel, if any.
	 *
	 * @param array An array containing the details of existing values, if any.
	 * @return array An array of manual payment fields.
	 */
	public function GetManualPaymentFields($existingOrder=array())
	{
		$existingDetails = array(
			'cc_name' => '',
			'cc_cctype' => '',
			'cc_ccexpm' => '',
			'cc_ccexpy' => '',
			'cc_ccno' => '',
			'cc_issueno' => '',
			'cc_issuedatem' => '',
			'cc_issuedatey' => ''
		);

		if(isset($existingOrder['extrainfo']) && $existingOrder['extrainfo'] != '') {
			$extraInfo = @unserialize($existingOrder['extrainfo']);
			$existingDetails = array_merge($existingDetails, $extraInfo);
			if($existingDetails['cc_ccno']) {
				$existingDetails['cc_ccno'] = $this->_CCDecrypt($existingDetails['cc_ccno']);
			}
		}
		else if(isset($existingOrder['paymentMethod'][$this->GetId()])) {
			$existingDetails = array_merge($existingDetails, $this->GetId());
		}

		$monthOptions = '';
		$issueMonthOptions = '<option value="">&nbsp;</option>';
		for($i = 1; $i <= 12; $i++) {
			$stamp = mktime(0, 0, 0, $i, 15, date("Y"));
			$i = str_pad($i, 2, "0", STR_PAD_LEFT);

			$sel = '';
			if($existingDetails['cc_ccexpm'] == $i) {
				$sel = 'selected="selected"';
			}
			$monthOptions .= '<option value="'.$i.'" '.$sel.'>'.date('M', $stamp).'</option>';

			$sel = '';
			if($existingDetails['cc_issuedatem'] == $i) {
				$sel = 'selected="selected"';
			}
			$issueMonthOptions .= '<option value="'.$i.'" '.$sel.'>'.date('M', $stamp).'</option>';
		}

		$yearOptions = '';
		for($i = date("Y"); $i <= date("Y")+10; $i++) {
			$sel = '';
			$value = isc_substr($i, 2, 2);
			if($value == $existingDetails['cc_ccexpy']) {
				$sel = 'selected="selected"';
			}
			$yearOptions .= '<option value="'.$value.'" '.$sel.'>'.$i.'</option>';
		}

		$issueYearOptions = '<option value="">&nbsp;</option>';
		for($i = date("Y"); $i > date("Y")-5; --$i) {
			$sel = '';
			$value = isc_substr($i, 2, 2);
			if($value == $existingDetails['cc_issuedatey']) {
				$sel = 'selected="selected"';
			}
			$issueYearOptions .= '<option value="'.$value.'" '.$sel.'>'.$i.'</option>';
		}

		// the stored cc type is the descriptive name, need to get key from the types array
		$cctype = "";
		if ($existingDetails['cc_cctype']) {
			foreach ($this->_card_types as $key => $type) {
				if ($type['type'] == $existingDetails['cc_cctype']) {
					$cctype = $key;
					break;
				}
			}
		}

		$cardOptions = $this->_GetCCTypes($cctype);
		$fields = array(
			'cc_name' => array(
				'type' => 'text',
				'title' => GetLang('CCManualCardHoldersName'),
				'value' => $existingDetails['cc_name'],
				'required' => true
			),
			'cc_cctype' => array(
				'type' => 'select',
				'title' => GetLang('CCManualCreditCardType'),
				'options' => $cardOptions,
				'onchange' => "
					if(\$(this).find('option:selected').is('.requiresCVV2')) {
						\$(this).parents('.paymentMethodForm').find('.Field_cc_cvv2').show();
					}
					else {
						\$(this).parents('.paymentMethodForm').find('.Field_cc_cvv2').hide();
					}

					if(\$(this).find('option:selected').is('.hasIssueNo')) {
						\$(this).parents('.paymentMethodForm').find('.Field_cc_issueno').show();
					}
					else {
						\$(this).parents('.paymentMethodForm').find('.Field_cc_issueno').hide();
					}

					if(\$(this).find('option:selected').is('.hasIssueDate')) {
						\$(this).parents('.paymentMethodForm').find('.Field_cc_issuedate').show();
					}
					else {
						\$(this).parents('.paymentMethodForm').find('.Field_cc_issuedate').hide();
					}
				",
				'required' => true
			),
			'cc_ccno' => array(
				'type' => 'text',
				'title' => GetLang('CCManualCreditCardNo'),
				'value' => $existingDetails['cc_ccno'],
				'required' => true
			),
			'cc_expiry' => array(
				'type' => 'html',
				'title' => GetLang('CCManualExpirationDate'),
				'html' => '
					<select name="paymentField[checkout_creditcardmanually][cc_ccexpm]">'.$monthOptions.'</select>
					&nbsp;
					<select name="paymentField[checkout_creditcardmanually][cc_ccexpy]">'.$yearOptions.'</select>
				',
				'required' => true
			),
			'cc_issueno' => array(
				'type' => 'text',
				'title' => GetLang('CCManualCreditCardIssueNo'),
				'value' => $existingDetails['cc_issueno'],
				'required' => true
			),
			'cc_issuedate' => array(
				'type' => 'html',
				'title' => GetLang('CCManualIssueDate'),
				'html' => '
					<select name="paymentField[checkout_creditcardmanually][cc_issuedatem]">'.$issueMonthOptions.'</select>
					&nbsp;
					<select name="paymentField[checkout_creditcardmanually][cc_issuedatey]">'.$issueYearOptions.'</select>
				',
				'required' => true
			)
		);

		return $fields;
	}

	/**
	 * Save the manual payment fields for this checkout provider.
	 *
	 * @param array The information about the order.
	 * @param array An array of fields for this module that were passed back.
	 */
	public function ProcessManualPayment($order, $data)
	{
		$cctype = $data['cc_cctype'];
		$cardVars = array(
			'cc_name'		=> $data['cc_name'],
			'cc_cctype'		=> $this->_card_types[$cctype]['type'],
			'cc_ccno'		=> $this->_CCEncrypt($data['cc_ccno']),
			'cc_ccexpm'		=> $data['cc_ccexpm'],
			'cc_ccexpy'		=> $data['cc_ccexpy']
		);

		if($this->CardTypeHasIssueNo($cctype)) {
			$cardVars['cc_issueno'] = $this->_CCEncrypt($data['cc_issueno']);
		}

		if($this->CardTypeHasIssueDate($cctype)) {
			$cardVars['cc_issuedatem'] = (int)$data['cc_issuedatem'];
			$cardVars['cc_issuedatey'] = (int)$data['cc_issuedatey'];
		}

		if($order['extrainfo'] != "") {
			$extraInfo = @unserialize($order['extrainfo']);
			if(is_array($extraInfo)) {
				$extraInfo = @array_merge($extraInfo, $cardVars);
			}
		}
		else {
			$extraInfo = serialize($cardVars);
		}

		$updatedOrder = array(
			"extrainfo" => serialize($extraInfo)
		);
		$GLOBALS['ISC_CLASS_DB']->UpdateQuery("orders", $updatedOrder, "orderid='".(int)$order['orderid']. "'");
		return array(
			'result' => true,
			'amount' => $order['total_inc_tax'],
		);
	}
}