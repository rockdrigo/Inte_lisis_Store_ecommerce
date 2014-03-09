<?php

	require_once(dirname(__FILE__).'/class.module.php');

	/**
	* The Interspire Shopping Cart analytics base class, used by all analytics modules
	*/
	class ISC_ANALYTICS extends ISC_MODULE
	{
		/**
		* @var string $type The type of module this is
		*/
		protected $type = 'analytics';

		/**
		 * @var array The details about the order(s) being passed when generating conversion tracking code.
		 */
		private $orderData = array();

		protected function CheckEnabled()
		{
			$analytics_methods = explode(",", GetConfig('AnalyticsMethods'));
			if(in_array($this->GetId(), $analytics_methods)) {
				return true;
			}
			else {
				return false;
			}
		}

		/*
			Return a HTML-formatted list of properties for this analytics module
		*/
		public function GetPropertiesSheet($tab_id)
		{
			return parent::GetPropertiesSheet($tab_id, 'PackageId', 'AnalyticsJavaScript', 'package_selected');
		}

		/**
		 * Return the tracking code for this analytics module.
		 *
		 * @return string The tracking code.
		 */
		public function GetTrackingCode()
		{
			return $this->GetValue('trackingcode');
		}

		/**
		 * Return the order conversion tracking code for this analytics module.
		 *
		 * @return string The tracking code.
		 */
		public function GetConversionCode()
		{
			return '';
		}

		/**
		 * Set the order data/details that will be used for generating conversion tracking code.
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
		 * Return an array of all of the actual orders being processed by this analytics
		 * module.
		 *
		 * @return array An array of the orders being processed.
		 */
		protected function GetOrders()
		{
			return $this->orderData['orders'];
		}

		/**
		 * Get the total amount that's being processed by this analytics module.
		 * The total_inc_tax column for every single order being processed will be the
		 * total for the entire order, so we simply return the amount for one of the orders
		 * being processed.
		 *
		 * @return string The amount to be processed by this checkout method.
		 */
		protected function GetGatewayAmount()
		{
			$order = current($this->orderData['orders']);
			return $order['total_inc_tax'];
		}

		/**
		 * Gets the total amount for shipping that's being processed by this analytics module.
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
		 * Gets the total amount for handling that's being processed by this analytics module.
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
		 * Gets the total amount for for tax that's being charged ON TOP of the subtotal by this analytics module.
		 * Returns a totalled amount of the ordtaxcost for each of the orders being
		 * processed where the tax is NOT included.
		 *
		 * @return string The total amount of tax.
		 */
		protected function GetTaxCost()
		{
			if(getConfig('taxDefaultTaxDisplayOrders') == TAX_PRICES_DISPLAY_INCLUSIVE) {
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
		 * or store credit etc are applied.
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
		 * Get the currency ID that these orders were placed in. This is used
		 * for showing currency localized amounts/prices. (All orders for a vendor order)
		 * will be placed in the same currency.
		 *
		 * @return integer The ID of the currency.
		 */
		protected function GetCurrency()
		{
			$order = current($this->orderData['orders']);
			return $order['ordcurrencyid'];
		}

		/**
		 * Get the IP address of the customer who placed these orders.
		 *
		 * @return string The IP address of the customer.
		 */
		protected function GetIpAddress()
		{
			$order = current($this->orderData['orders']);
			return $order['ordipaddress'];
		}

		/**
		 * Get the status of the orders being processed.
		 * All statuses will be the same, so simply fetch for the first order and return that.
		 *
		 * @return int The order status.
		 */
		protected function GetOrderStatus()
		{
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
			$order = current($this->orderData['orders']);
			foreach($order as $field => $value) {
				if(substr($field, 0, 7) == 'ordbill') {
					$details[$field] = $value;
				}
			}

			return $details;
		}
	}