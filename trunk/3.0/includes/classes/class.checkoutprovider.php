<?php
require_once(dirname(__FILE__).'/class.module.php');

/**
* The Interspire Shopping Cart checkout provider base class, used by all checkout modules
*/

define("PAYMENT_PROVIDER_ONLINE", 1);
define("PAYMENT_PROVIDER_OFFLINE", 2);

class ISC_CHECKOUT_PROVIDER extends ISC_MODULE
{
	/**
	 * @var string The type of module this is.
	 */
	protected $type = 'checkout';

	/**
	 * @var integer The type of payment provider that this module offers (online or offline)
	 */
	protected $paymentType = PAYMENT_PROVIDER_ONLINE;

	/**
	 * @var integer If this payment module should force the status of an order to something other than the default, set it here.
	 */
	protected $forcedStatus = 0;

	/**
	 * @var boolean Disable the checkout links/buttons everywhere except on the main cart page
	 */
	public $disableNonCartCheckoutButtons = false;

	/**
	* @var boolean Show the provider in the list of providers on the checkout page (if there is more than 1)
	*/
	public $showOnConfirmPage = true;

	/**
	 * @var boolean Does this provider support orders from more than one vendor?
	 */
	protected $supportsVendorPurchases = false;

	/**
	 * @var boolean Does this provider support shipping to multiple addresses?
	 */
	protected $supportsMultiShipping = false;

	/**
	 * @var boolean True if this checkout module requires SSL or not. Defaults to false.
	 */
	protected $requiresSSL = false;

	/**
	 * @var int The payment status to return for this order.
	 */
	protected $paymentStatus = null;

	/**
	 * @var array The optional supported currency codes
	 */
	protected $supportedCurrencyCodes = array();

	/**
	 * @var array An array of payment fields that can be shown when creating/editing an order via the control panel.
	 */
	protected $manualPaymentFields = array();

	/**
	 * @var array The details about the order(s) being passed to this checkout provider.
	 */
	private $orderData = array();

	/**
	 * Check if this payment module is accessible by the customer. This is useful for
	 * checking if, for example, the billing address of an order is a specific country.
	 *
	 * @return boolean True if accessible by the customer, false if not.
	 */
	public function IsAccessible()
	{
		return true;
	}

	/**
	 * Get the configured display name for this payment provider. Will read the 'displayname'
	 * setting for this module if it has one.
	 *
	 * @return string The display name for this module.
	 */
	public function GetDisplayName()
	{
		if($this->GetValue('displayname')) {
			return $this->GetValue('displayname');
		}
		else {
			return $this->GetName();
		}
	}

	/**
	 * Return the payment type for this module (either online or offline).
	 * Optionally as a string or number format.
	 *
	 * @param string What to return as. number for a numeric value, text for the text equivalent.
	 * @return mixed Integer if returning as a number, otherwise a string.
	 */
	public function GetPaymentType($returnAsWhat = "number")
	{
		// Kept for backwards compatibility
		if(isset($this->_paymenttype)) {
			$this->paymentType = $this->_paymenttype;
		}

		if($returnAsWhat == "number") {
			return $this->paymentType;
		}
		else if($returnAsWhat == "text") {
			if($this->paymentType == PAYMENT_PROVIDER_ONLINE) {
				return "PAYMENT_PROVIDER_ONLINE";
			}
			else {
				return "PAYMENT_PROVIDER_OFFLINE";
			}
		}
	}

	/**
	 * Checks if the payment module requires SSL to be enabled on the store or not.
	 *
	 * @return boolean True if SSL is required, false if not.
	 */
	public function RequiresSSL()
	{
		// For backwards compatibility, check if _requiresSSL is set.
		if(isset($this->_requiresSSL)) {
			return $this->_requiresSSL;
		}

		return $this->requiresSSL;
	}

	/**
	 * Check if this payment module is enabled or not.
	 *
	 * @return boolean True if enabled, false if not.
	 */
	public function CheckEnabled()
	{
		$checkout_methods = explode(",", GetConfig('CheckoutMethods'));
		if(in_array($this->GetId(), $checkout_methods)) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Build a property/configuration sheet for this checkout module.
	 *
	 * @param string The ID of the tab.
	 * @param boolean Set to false to not draw the table header rows and intro text
	 * @return string The generated configuration page/sheet.
	 */
	public function GetPropertiesSheet($tabId, $doHeaderRows=true, $moduleId='')
	{
		$this->PreparePropertiesSheet($tabId, 'CheckoutId', 'CheckoutJavaScript', 'checkout_selected', array(), $moduleId);

		if (!$doHeaderRows) {
			$GLOBALS['HideHeaderRow'] = 'display:none;';
			$GLOBALS['HelpText'] = '';
		}

		return Interspire_Template::getInstance('admin')->render('module.propertysheet.tpl');
	}

	/**
	 * Redirect to a payment provider with the specified hidden form fields.
	 *
	 * @param string The URL to redirect to.
	 * @param array An array of form fields to POST, if any.
	 */
	protected function RedirectToProvider($url, $fields=array())
	{
		$formFields = '';
		foreach($fields as $name => $value) {
			$formFields .= "<input type=\"hidden\" name=\"".isc_html_escape($name)."\" value=\"".isc_html_escape($value)."\" />\n";
		}
		echo '
			<html>
			<head>
				<title>'.GetLang('Redirecting').'</title>
				<meta http-equiv="Content-Type" content="text/html; charset='.GetConfig('CharacterSet').'" />
			</head>
			<body>
			<form action="'.$url.'" method="post" style="margin-top: 50px; text-align: center;">
				<noscript><input type="submit" value="'.GetLang('ClickIfNotRedirected').'" /></noscript>
				<div id="ContinueButton" style="display: none;">
					<input type="submit" value="'.GetLang('ClickIfNotRedirected').'" />
				</div>
				'.$formFields.'
			</form>
			<script type="text/javascript">
				window.onload = function() {
					document.forms[0].submit();
					setTimeout(function() {
						document.getElementById("ContinueButton").style.display = "";
					}, 1000);
				}
			</script>
			</body>
			</html>
		';
		exit;
	}

	/**
	 * Redirect to the order confirmation page again, with an optional message.
	 *
	 * @param string The message, if we have one.
	 */
	protected function RedirectToOrderConfirmation($reason="", $targetTop=false)
	{
		if($reason) {
			$_SESSION['REDIRECT_TO_CONFIRMATION_MSG'] = $reason;
		}

		if($targetTop) {
			echo "<script>top.location.href='".$GLOBALS['ShopPathSSL']."/checkout.php?action=confirm_order';</script>";
		} else {
			header("Location: ".$GLOBALS['ShopPathSSL']."/checkout.php?action=confirm_order");
		}
		exit;
	}

	/**
	 * Returns the status that this checkout module should force orders to (if there is one)
	 *
	 * @return string The status to force orders to.
	 */
	public function GetForcedStatus()
	{
		return $this->forcedStatus;
	}

	/**
	 * Show any additional payment details/settings at the end of the checkout that
	 * this payment module requires (ie, payment receipt etc). If empty, nothing will be
	 * shown.
	 *
	 * @param array The array of order information.
	 * @return string Any additional data this payment provider may want to show
	 */
	public function DisplayPaymentDetails($order)
	{
		// By default, everything wants to show nothing (now that just sounds cool)
		return '';
	}

	/**
	 * Set the order data/details for the order going through the payment method.
	 * This is called internally, and passes a summary of all of the possible orders
	 * as well as each individual order.
	 *
	 * @param array An array of information about the order.
	 */
	public function SetOrderData($orderData)
	{
		$this->orderData = $orderData;
	}

	/**
	 * Verify that the payment for an order was successfully processed.
	 *
	 * @return boolean True if successful and the order is valid, false if not.
	 */
	public function VerifyOrderPayment()
	{
		return false;
	}

	/**
	 * Set the payment status of the order.
	 *
	 * @param int The payment status to set the orders to.
	 */
	protected function SetPaymentStatus($status)
	{
		$this->paymentStatus = $status;
	}

	/**
	 * Get the set payment status.
	 */
	public function GetPaymentStatus()
	{
		return $this->paymentStatus;
	}

	/**
	 * Return an array of all of the actual orders being processed by this payment
	 * gateway.
	 *
	 * @return array An array of the orders being processed.
	 */
	protected function GetOrders()
	{
		if(isset($this->orderData['orders'])) {
			return $this->orderData['orders'];
		} else {
			return array();
		}
	}

	/**
	 * Get the total amount that's being processed by this payment gateway.
	 * The total_inc_tax column for every single order being processed will be the
	 * total for the entire order, so we simply return the amount for one of the orders
	 * being processed.
	 *
	 * @return string The amount to be processed by this checkout method.
	 */
	protected function GetGatewayAmount()
	{
		reset($this->orderData['orders']);
		$order = current($this->orderData['orders']);

		// 'ordgatewayamount' is being removed. total_inc_tax is now what
		// should go through the gateway.
		return $order['total_inc_tax'];
	}

	/**
	 * Gets the total amount for shipping that's being processed by this gateway.
	 * Returns a totalled amount of the shipping cost column for each of the orders being
	 * processed.
	 *
	 * @return string The total amount of the shipping costs.
	 */
	protected function GetShippingCost()
	{
		$shippingColumn = 'shipping_cost_ex_tax';
		if(getConfig('taxDefaultTaxDisplayOrders') == TAX_PRICES_DISPLAY_INCLUSIVE) {
			$shippingColumn = 'shipping_cost_inc_tax';
		}

		$amount = 0;
		foreach($this->orderData['orders'] as $order) {
			$amount += $order[$shippingColumn];
		}
		return $amount;
	}

	/**
	 * Gets the total amount for handling that's being processed by this gateway.
	 * Returns a totalled amount of the handling cost column for each of the orders being
	 * processed.
	 *
	 * @return string The total amount of the handling costs.
	 */
	protected function GetHandlingCost()
	{
		$handingColumn = 'handling_cost_ex_tax';
		if(getConfig('taxDefaultTaxDisplayOrders') == TAX_PRICES_DISPLAY_INCLUSIVE) {
			$handingColumn = 'handling_cost_inc_tax';
		}

		$amount = 0;
		foreach($this->orderData['orders'] as $order) {
			$amount += $order[$handingColumn];
		}
		return $amount;
	}

	/**
	 * Gets the total amount for for tax that's being charged ON TOP of the subtotal by this gateway.
	 * Returns a totalled amount of the ordtaxcost for each of the orders being
	 * processed where the tax is NOT included.
	 *
	 * @param boolean ignore the default tax display configuration setting.
	 *
	 * @return string The total amount of tax.
	 */
	protected function GetTaxCost($ignoreTaxDisplayConfig=false)
	{
		if(!$ignoreTaxDisplayConfig && getConfig('taxDefaultTaxDisplayOrders') == TAX_PRICES_DISPLAY_INCLUSIVE) {
			return 0;
		}

		$amount = 0;
		foreach($this->orderData['orders'] as $order) {
			$amount += $order['total_tax'];
		}
		return $amount;
	}

	/**
	 * Get the total amount of all of the orders (before any gift certificates)
	 * or store credit etc are applied. Use GetGatewayAmount() to determine the amount
	 * to be charged via a payment gateway. Totals the amount from all active orders.
	 *
	 * @return string The total amount of all of the orders.
	 */
	protected function GetTotalAmount()
	{
		$amount = 0;
		foreach($this->orderData['orders'] as $order) {
			$amount += $order['total_inc_tax'];
		}
		return $amount;
	}

	/**
	 * Get the subtotal for all of the items in all of the orders.
	 *
	 * @return string The subtotal amount of all of the orders.
	 */
	protected function GetSubTotal()
	{
		$subTotalColumn = 'subtotal_ex_tax';
		if(getConfig('taxDefaultTaxDisplayOrders') == TAX_PRICES_DISPLAY_INCLUSIVE) {
			$subTotalColumn = 'subtotal_inc_tax';
		}

		foreach($this->orderData['orders'] as $order) {
			$amount += $order[$subTotalColumn];
		}
		return $amount;
	}

	/**
	 * Check if all of the orders going through the payment gateway are digital.
	 *
	 * @return boolean True if all are digital orders.
	 */
	protected function IsDigitalOrder()
	{
		foreach($this->orderData['orders'] as $order) {
			if(!$order['ordisdigital']) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Get the store default currency at the time that this order was placed.
	 *
	 * @return integer The ID of the currency.
	 */
	protected function GetBaseCurrency()
	{
		reset($this->orderData['orders']);
		$order = current($this->orderData['orders']);
		return $order['orddefaultcurrencyid'];
	}

	/**
	 * Get the currency ID that these orders were placed in. This is used
	 * for showing currency localized amounts/prices. (All orders for a vendor order)
	 * will be placed in the same currency.
	 *
	 * @return integer The ID of the currency.
	 */
	protected function GetCurrency()
	{
		reset($this->orderData['orders']);
		$order = current($this->orderData['orders']);
		return $order['ordcurrencyid'];
	}


	/**
	 * Get the id of the customer who placed these orders.
	 *
	 * @return string The customer id.
	 */
	protected function GetCustomerId()
	{
		reset($this->orderData['orders']);
		$order = current($this->orderData['orders']);
		return $order['ordcustid'];
	}

	/**
	 * Get the IP address of the customer who placed these orders.
	 *
	 * @return string The IP address of the customer.
	 */
	protected function GetIpAddress()
	{
		reset($this->orderData['orders']);
		$order = current($this->orderData['orders']);
		return $order['ordipaddress'];
	}

	/**
	 * Get the status of the orders being processed through the payment gateway.
	 * All statuses will be the same, so simply fetch for the first order and return that.
	 *
	 * @return int The order status.
	 */
	protected function GetOrderStatus()
	{
		reset($this->orderData['orders']);
		$order = current($this->orderData['orders']);
		return $order['ordstatus'];
	}

	/**
	 * Return an array of all of the billing details (name, address etc)
	 * for these orders. The billing address for all orders will be the same
	 * (can't have split billing addresses) so we simply fetch for the current
	 * order and return that.
	 *
	 * @return array An array of billing details.
	 */
	protected function GetBillingDetails()
	{
		$details = array();
		reset($this->orderData['orders']);
		$order = current($this->orderData['orders']);
		foreach($order as $field => $value) {
			if(substr($field, 0, 7) == 'ordbill') {
				$details[$field] = $value;
			}
		}

		return $details;
	}

	/**
	 * Return the shipping address for this order. In the case that the order has
	 * multiple shipping addresses, the first matched is returned. If the order is
	 * digital (ie, no shipping) then the billing address is returned.
	 */
	protected function getShippingAddress()
	{
		reset($this->orderData['orders']);
		$order = current($this->orderData['orders']);
		if($this->isDigitalOrder()) {
			// Map the billing address to match the new format
			$billingMap = array(
				'ordbillfirstname' => 'first_name',
				'ordbilllastname' => 'last_name',
				'ordbillcompany' => 'company',
				'ordbillstreet1' => 'address_1',
				'ordbillstreet2' => 'address_2',
				'ordbillsuburb' => 'city',
				'ordbillstate' => 'state',
				'ordbillzip' => 'zip',
				'ordbillcountry' => 'country',
				'ordbillcountrycode' => 'country_iso2',
				'ordbillcountryid' => 'country_id',
				'ordbillstateid' => 'state_id',
				'ordbillphone' => 'phone',
				'ordbillemail' => 'email',
			);
			$billingDetails = $this->getBillingDetails();
			$shippingDetails = array();
			foreach($billingMap as $billingField => $shippingField) {
				$shippingDetails[$shippingField] = $billingDetails[$billingField];
			}

			return $shippingDetails;
		}

		// Otherwise, fetch the first shipping address on this order
		$query = "
			SELECT *
			FROM [|PREFIX|]order_addresses
			WHERE order_id='".$order['orderid']."'
			LIMIT 1
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		$address = $GLOBALS['ISC_CLASS_DB']->fetch($result);
		return $address;
	}

	/**
	 * Update one or more orders in the database that are currently handled by
	 * this payment gateway. Also updates the internal cache of order information.
	 *
	 * @param array An array of fields to be updated.
	 * @param array Optionally an array of specific orders to update (otherwise, assumes all)
	 */
	protected function UpdateOrders($what, $orderIds=array())
	{
		if(empty($orderIds)) {
			$orderIds = array_keys($this->orderData['orders']);
		}
		else if(!is_array($orderIds)) {
			$orderIds = array($orderIds);
		}

		// Update the orders in the database
		$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $what, "orderid IN (".implode(',', $orderIds).")");

		//if we have order details in cache,
		if(isset($this->orderData['orders'])) {
			// Now update the order info we have cached
			foreach(array_keys($this->orderData['orders']) as $orderId) {
				if(!in_array($orderId, $orderIds)) {
					continue;
				}
				$this->orderData['orders'][$orderId] = array_merge($this->orderData['orders'][$orderId], $what);
			}
		}
	}

	/**
	 * Return the billing country for these orders. This method is also available
	 * if the pending orders are yet to be saved in the database.
	 *
	 * @return integer The billing country ID for the order.
	 */
	protected function GetBillingCountry()
	{
		// If we have an order loaded, we can simply use that
		if(!empty($this->orderData)) {
			$billingDetails = $this->GetBillingDetails();
			return $billingDetails['ordbillcountryid'];
		}

		// Order hasn't gone through the checkout yet
		$GLOBALS['ISC_CLASS_CHECKOUT'] = GetClass('ISC_CHECKOUT');
		$billingAddress = $GLOBALS['ISC_CLASS_CHECKOUT']->getQuote()->getBillingAddress();
		return $billingAddress->getCountryId();
	}

	/**
	 * Checks if the current checkout module is compatible with vendor (split order)
	 * purchases.
	 *
	 * @return boolean True if compatible, false if not.
	 */
	public function IsVendorCompatible()
	{
		return (bool)$this->supportsVendorPurchases;
	}

	/**
	 * Checks if the current module is compatible with orders that have multiple
	 * shipping addresses.
	 *
	 * @return boolean True if compatible, false if not.
	 */
	public function IsMultiShippingCompatible()
	{
		return (bool)$this->supportsMultiShipping;
	}

	/**
	 * Creates a unique order id by combining all order ids together
	 *
	 * @return string A string of order ids.
	 */
	public function GetCombinedOrderId()
	{
		$orders = $this->GetOrders();

		if (!is_array($orders)) {
			return false;
		}

		ksort($orders);
		$combinedId = '';

		foreach ($orders as $order) {
			$combinedId .= $order['orderid'];

			if (isset($combinedId{30})) {
				$combinedId = substr($combinedId,0,30);
				break;
			}
		}

		return $combinedId;
	}

	/**
	 * Return a list of any manual payment fields that should be shown when creating/editing
	 * an order via the control panel, if any.
	 *
	 * @param array An array containing the details of the existing order, if any.
	 * @return array An array of manual payment fields.
	 */
	public function GetManualPaymentFields($existingOrder=array())
	{
		return $this->manualPaymentFields;
	}

	/**
	 *
	 * DEPRECATED VARIABLES/METHODS.
	 *
	*/

	/**
	 * Set the total for the order.
	 *
	 * @deprecated 4.0
	 * @see SetOrderData()
	 */
	public function SetTotal($total)
	{
	}

	/**
	 * Return the total for an order.
	 *
	 * @deprecated 4.0
	 * @see GetGatewayAmount()
	 */
	protected function GetTotal()
	{
		return $this->GetGatewayAmount();
	}

	/**
	 * Get the supported currencies
	 *
	 * Method will get the supported currencies
	 *
	 * @acces protected
	 * @return array The supported currencies
	 */
	protected function getSupportedCurrencies()
	{
		return $this->supportedCurrencyCodes;
	}

	/**
	 * Set the supported currencies
	 *
	 * Method will set the supported currencies
	 *
	 * @acces protected
	 * @param mixed $currencies The array of currencies or currency string
	 * @return bool TRUE if the arugments were valid and currency set, FALSE if arguments were invalid
	 */
	protected function setSupportedCurrencies($currencies)
	{
		if (!is_array($currencies) && is_string($currencies)) {
			$currencies = array($currencies);
		}

		$currencies = array_filter($currencies);

		if (!is_array($currencies) || empty($currencies)) {
			return false;
		}

		$this->supportedCurrencyCodes = $currencies;
		return true;
	}

	/**
	 * Check to see if the currency is supported
	 *
	 * Method will check to see if the currency $currency is supported
	 *
	 * @access protected
	 * @param string $currency The currency string to check
	 * @return bool TRUE if the currency is supported, FALSE if not
	 */
	protected function checkSupportedCurrencies($currency)
	{
		if (trim($currency) == '') {
			return false;
		}

		if (empty($this->supportedCurrencyCodes)) {
			return true;
		}

		return Store_Array::inArrayCI(trim($currency), $this->supportedCurrencyCodes);
	}

	/**
	* Gets a comma delimited string of order Ids in the format: #1, #2, #3
	*
	* @return string The string of order Ids or an empty string if no orders are associated with this module.
	*/
	protected function GetOrderIdsString()
	{
		$orders = $this->GetOrders();
		if (empty($orders)) {
			return '';
		}

		$orderIds = '#'.implode(', #', array_keys($orders));

		return $orderIds;
	}

	/**
	 * Fetch a summary describing all of the taxes applied to the orders being
	 * paid.
	 *
	 * @return array Nested array by order, of all applied taxes.
	 */
	protected function getOrderTaxes()
	{
		$taxSummary = array();
		$orderIds = array();
		foreach($orders as $order) {
			$orderIds[] = $order['orderid'];
		}

		$query = "
			SELECT
			FROM [|PREFIX|]order_taxes
			WHERE orderid IN (".implode(',', $orderIds).")
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		while($tax = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			if(!isset($taxSummary[$tax['order_id']])) {
				$taxSummary[$tax['order_id']] = array();
			}

			$taxSummary[$tax['order_id']] = $tax;
		}

		return $taxSummary;
	}
}