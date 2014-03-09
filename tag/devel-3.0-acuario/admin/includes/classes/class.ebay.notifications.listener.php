<?php

if (!defined('ISC_BASE_PATH')) {
	die();
}

/**
* This class handles notifications received about items listed on ebay.
*/
class ISC_ADMIN_EBAY_NOTIFICATIONS_LISTENER extends ISC_ADMIN_BASE
{
	/**
	* This method parses, validates and routes the incoming request to the correct methods.
	*
	* @param string $soapaction optional, if not provided it will be taken from $_SERVER['HTTP_SOAPACTION']
	* @param string $body optional, if not provided it will be taken directly from php://input
	*/
	public function handleRequest($soapaction = null, $body = null)
	{
		$this->log->LogSystemDebug('ebay', 'Incoming eBay notification');

		if ($soapaction === null) {
			if (isset($_SERVER['HTTP_SOAPACTION'])) {
				// for some reason, the soapaction header value is double-quoted, so...
				$soapaction = stripcslashes(trim($_SERVER['HTTP_SOAPACTION'], '"'));

				if (!preg_match('#^http://developer\\.ebay\\.com/notification/([^/]+)$#', $soapaction, $matches)) {
					$this->log->LogSystemDebug('ebay', 'Invalid SOAPACTION header', $soapaction);
					return;
				}

				$soapaction = $matches[1];
				unset($matches);
			}
		}

		if (!$soapaction) {
			$this->log->LogSystemDebug('ebay', 'No SOAPACTION header defined');
			return;
		}

		if ($body === null) {
			$body = file_get_contents('php://input');
		}

		// parse incoming xml
		require_once(ISC_BASE_PATH . '/lib/nusoap/nusoap.php');
		$parser = new nusoap_parser($body);

		if ($parser->getError()) {
			$this->log->LogSystemDebug('ebay', 'eBay notification could not be parsed to xml: ' . $parser->getError(), $body);
			return;
		}
		unset($body);

		// validate signature as genuine ebay notification, see http://developer.ebay.com/DevZone/XML/docs/WebHelp/WorkingWithNotifications-Receiving_Platform_Notifications.html
		$header = $parser->get_soapheader();
		$body = $parser->get_soapbody();

		$providedSignature = $header['RequesterCredentials']['NotificationSignature'];

		$timestamp = $body['Timestamp'];
		$expectedSignature = base64_encode(md5($timestamp . GetConfig("EbayDevId") . GetConfig("EbayAppId") . GetConfig("EbayCertId"), true));
		if (strcmp($expectedSignature, $providedSignature) !== 0) {
			$this->log->LogSystemDebug('ebay', 'eBay notification failed signature validation, provided signature was: ' . $providedSignature);
			return;
		}
		unset($expectedSignature, $providedSignature, $timestamp, $body, $header);

		// route to protected methods or ignore if a method for this notification is not implemented
		$method = '_handle' . $soapaction;
		if (!is_callable(array($this, $method))) {
			$this->log->LogSystemDebug('ebay', 'No processor defined for eBay notification event: ' . $soapaction);
			return false;
		}

		$body = $parser->get_soapbody();
		unset($parser);

		$this->log->LogSystemDebug('ebay', 'Accepted eBay notification event: ' . $soapaction, '<pre>' . isc_html_escape(var_export($body, true)) . '</pre>');
		return $this->$method($body);
	}

	/**
	* Ebay: Sent to a seller when a buyer completes the checkout process for an item. Not sent when an auction ends without bids.
	*
	* My notes: Seems to be triggered when the buyer's payment process for an AUCTION item has completed, is not fired for fixed price items which fire 'FixedPrice...' notifications instead
	*
	* @param array $body
	*/
	protected function _handleAuctionCheckoutComplete($body)
	{
		// The data fields in the notification are the same as those returned by the GetItemTransactions call with the default detail level.
		if (!empty ($body['Item']['ItemID']) && ISC_ADMIN_EBAY::validEbayItemId($body['Item']['ItemID'])) {
			// variables init
			$order = array();
			$orderId = 1;
			$order['ShippingInsuranceCost'] = 0;
			$completedPaymentHoldStatus = array('None', 'Released');
			$orderStatus = ORDER_STATUS_AWAITING_PAYMENT;
			$existingOrderId = 0;

			// Determine if the buyer purchase multiple items from the same seller
			if (!empty($body['TransactionArray']['Transaction']['ContainingOrder'])) {
			 // Call the operation to get the order transaction.
				$orderId = $body['TransactionArray']['Transaction']['ContainingOrder']['OrderID'];

				// if the record already exist, check if we need to update existing orders, that the payment hasn't been cleared previously.
				$existingOrder = GetOrderByEbayOrderId($orderId);
				$orderTransaction = ISC_ADMIN_EBAY_OPERATIONS::getOrderTransactions($orderId);
				$transactions = $orderTransaction->OrderArray->Order->TransactionArray->Transaction;

				$order['SubTotal'] = (string) $orderTransaction->OrderArray->Order->Subtotal;
				$order['ShippingCost'] = (string) $orderTransaction->OrderArray->Order->ShippingServiceSelected->ShippingServiceCost;
				$order['ShippingInsuranceCost'] = 0;
				$order['GrandTotal'] = (string) $orderTransaction->OrderArray->Order->Total;
				$order['TotalQuantityPurchased'] = 0;
				foreach ($transactions as $transaction) {
					$convertedTransaction = (array) $transaction;
					$variationOptionsString = '';
					if (isset($convertedTransaction['Variation']->VariationSpecifics)) {
						$variationNameValueList = (array) $convertedTransaction['Variation']->VariationSpecifics->NameValueList;
						$variationOptions = array();
						$variationSpecifics = (array) $convertedTransaction['Variation']->VariationSpecifics;
						if (is_array($variationSpecifics['NameValueList'])) {
							foreach ($variationSpecifics['NameValueList'] as $option) {
								$variationOptions[(string) $option->Name] = (string) $option->Value;
							}
						} else {
							$variationOptions[(string) $variationSpecifics['NameValueList']->Name] = (string) $variationSpecifics['NameValueList']->Value;
						}
						$variationOptionsString = serialize($variationOptions);
					}
					$quantityPurchased = $convertedTransaction['QuantityPurchased'];
					$transactionPrice = $convertedTransaction['TransactionPrice'];
					$itemId = (string) $convertedTransaction['Item']->ItemID;
					$transactionId = (string) $convertedTransaction['TransactionID'];
					$totalTransactionPrice = $transactionPrice * $quantityPurchased;
					$order['Transaction'][] = array(
						'QuantityPurchased' => $quantityPurchased,
						'TransactionPrice' => $transactionPrice,
						'ItemId' => $itemId,
						'TotalTransactionPrice' => $totalTransactionPrice,
						'VariationOptionsString' => $variationOptionsString,
						'TransactionId' => $transactionId,
					);
					$order['TotalQuantityPurchased'] += $quantityPurchased;
					$order['Currency'] = GetCurrencyByCode($body['TransactionArray']['Transaction']['AmountPaid']['!currencyID']);
					$buyerInfoShippingAddress = $body['TransactionArray']['Transaction']['Buyer']['BuyerInfo']['ShippingAddress'];
					$buyerEmailAddress = $body['TransactionArray']['Transaction']['Buyer']['Email'];
				}

				if ($existingOrder) {
					$existingOrderId = $existingOrder['orderid'];
				}
			}
			else {
				$transactions = $body['TransactionArray'];
				foreach ($transactions as $transaction) {
					$itemId = $body['Item']['ItemID'];
					$transactionId = $transaction['TransactionID'];
					$query = "
						SELECT *
						FROM [|PREFIX|]order_products
						WHERE ebay_item_id = '".$GLOBALS["ISC_CLASS_DB"]->Quote($itemId)."'
							AND ebay_transaction_id = '".$GLOBALS["ISC_CLASS_DB"]->Quote($transactionId)."'
						LIMIT 1
					";
					$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
					$row = $GLOBALS['ISC_CLASS_DB']->Fetch($res);
					$eachItemPriceExTax = $transaction['TransactionPrice']['!'];
					$quantityPurchased = $transaction['QuantityPurchased'];
					$totalTransactionPrice = $quantityPurchased * $eachItemPriceExTax;
					$variationOptionsString = '';

					// do we have a variation for this product?
					if (isset($transaction['Variation']['VariationSpecifics'])) {
						$variationNameValueList = $transaction['Variation']['VariationSpecifics']['NameValueList'];
						$variationOptions = array();
						foreach ($variationNameValueList as $option) {
							$variationOptions[$option['Name']] = $option['Value'];
						}
						$variationOptionsString = serialize($variationOptions);
					}
					$order['TotalQuantityPurchased'] = $quantityPurchased;
					$order['SubTotal'] = $eachItemPriceExTax * $order['TotalQuantityPurchased'];
					$order['ShippingCost'] = $transaction['ShippingServiceSelected']['ShippingServiceCost']['!'];
					if (isset ($transaction['ShippingServiceSelected']['ShippingInsuranceCost']['!'])) {
						$order['ShippingInsuranceCost'] = $transaction['ShippingServiceSelected']['ShippingInsuranceCost']['!'];
					}
					$order['GrandTotal'] = $transaction['AmountPaid']['!'];
					$order['Transaction'][] = array(
						'QuantityPurchased' => $quantityPurchased,
						'TransactionPrice' => $eachItemPriceExTax,
						'ItemId' => $itemId,
						'TotalTransactionPrice' => $totalTransactionPrice,
						'VariationOptionsString' => $variationOptionsString,
						'TransactionId' => $transactionId,
					);
					$order['Currency'] = GetCurrencyByCode($transaction['AmountPaid']['!currencyID']);
					$buyerInfoShippingAddress = $transaction['Buyer']['BuyerInfo']['ShippingAddress'];
					$buyerEmailAddress = $transaction['Buyer']['Email'];

					if (!$row) {
						// only process the new transaction
						break;
					} else {
						$existingOrderId = $row['orderorderid'];
					}
				}
			}

			$paymentHoldStatus = $body['TransactionArray']['Transaction']['Status']['PaymentHoldStatus'];
			if (in_array(trim($paymentHoldStatus), $completedPaymentHoldStatus)) {
				$orderStatus = ORDER_STATUS_AWAITING_FULFILLMENT;
			}
			if ($existingOrderId != 0) {
				if (!isset ($existingOrder)) {
					$existingOrder = GetOrder($existingOrderId, false, true, true);
				}

				// check if there're any existing order need to be updated.
				// in the case, paypal release the hold payment of buyer
				if ($existingOrder['ordstatus'] == ORDER_STATUS_AWAITING_PAYMENT
				&& $orderStatus == ORDER_STATUS_AWAITING_FULFILLMENT) {
					// update the quantity for each transaction
					$GLOBALS["ISC_CLASS_DB"]->StartTransaction();
					foreach ($order['Transaction'] as $eachTransaction) {
						// Get product Id
						try {
							$itemObj = new ISC_ADMIN_EBAY_ITEMS($eachTransaction['ItemId']);
							$productId = $itemObj->getProductId();
						} catch (Exception $e) {
							$this->log->LogSystemDebug('ebay', $e->getMessage());
							return false;
						}

						// update the item quantity in store
						$updatedData['quantity_remaining'] = $itemObj->getQuantityRemaining() - $eachTransaction['QuantityPurchased'];
						if (!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('ebay_items', $updatedData, "ebay_item_id='" . $eachTransaction['ItemId'] . "'")) {
							$this->log->LogSystemDebug('ebay', $GLOBALS["ISC_CLASS_DB"]->Error());
							$GLOBALS["ISC_CLASS_DB"]->RollbackTransaction();
							return false;
						}
						if (!UpdateOrderStatus($existingOrderId, $orderStatus, true, true)) {
							$GLOBALS["ISC_CLASS_DB"]->RollbackTransaction();
							return false;
						}
					}
					$GLOBALS["ISC_CLASS_DB"]->CommitTransaction();

					// update the store inventory if necessary
					if (GetConfig('UpdateInventoryLevels') == 1) {
						DecreaseInventoryFromOrder($existingOrderId);
					}
					$this->log->LogSystemDebug('ebay', 'The status of the store order ('. $existingOrderId .') has been updated to: Awaiting Fulfillment');
				}
				return true;
			}

			$order['ShippingTotalCost'] = $order['ShippingInsuranceCost'] + $order['ShippingCost'];

			// Buyer's address information
			$addressMap = array(
				'Name',
				'CompanyName',
				'Street1',
				'Street2',
				'CityName',
				'PostalCode',
				'Country',
				'CountryName',
				'Phone',
				'StateOrProvince',
			);

			// Initialize the value, make sure it's not empty
			foreach ($addressMap as $key) {
				if (!isset($buyerInfoShippingAddress[$key])) {
					$buyerInfoShippingAddress[$key] = '';
				}
			}
			$buyerCountryId = GetCountryIdByISO2($buyerInfoShippingAddress['Country']);
			$buyerStateId = GetStateByName($buyerInfoShippingAddress['StateOrProvince'], $buyerCountryId);
			$buyerStateName = $buyerInfoShippingAddress['StateOrProvince'];
			if (!$buyerStateId) {
				$buyerStateId = GetStateByAbbrev($buyerInfoShippingAddress['StateOrProvince'], $buyerCountryId);
				$stateInfo = GetStateInfoById($buyerStateId);
				$buyerStateName = $stateInfo['statename'];
			}

			// Tokenize buyer's first and last name
			$nameTokens = explode(' ', $buyerInfoShippingAddress['Name']);
			$buyerFirstName = $nameTokens[0];
			$buyerLastName = '';
			if (!empty($nameTokens[1])) {
				$buyerLastName = $nameTokens[1];
			}

			$orderToken = generateOrderToken();

			// Preparing data to be inserted to orders table
			$newOrder = array(
				'ordtoken' => $orderToken,
				'orderpaymentmodule' => '',
				'orderpaymentmethod' => '',
				'orderpaymentmodule' => '',
				'extraInfo' => serialize(array()),
				'orddefaultcurrencyid' => $order['Currency']['currencyid'],
				'orddate' => time(),
				'ordlastmodified' => time(),
				'ordcurrencyid' => $order['Currency']['currencyid'],
				'ordcurrencyexchangerate' => 1,
				'ordipaddress' => GetIP(),
				'ordcustmessage' => '',
				'ordstatus' => $orderStatus,
				'base_shipping_cost' => $order['ShippingTotalCost'],
				'base_handling_cost' => 0,
				'ordbillemail' => $buyerEmailAddress,
				'ordbillfirstname' => $buyerFirstName,
				'ordbilllastname' => $buyerLastName,
				'ordbillcompany' => $buyerInfoShippingAddress['CompanyName'],
				'ordbillstreet1' => $buyerInfoShippingAddress['Street1'],
				'ordbillstreet2' => $buyerInfoShippingAddress['Street2'],
				'ordbillsuburb' => $buyerInfoShippingAddress['CityName'],
				'ordbillzip' => $buyerInfoShippingAddress['PostalCode'],
				'ordbillcountrycode' => $buyerInfoShippingAddress['Country'],
				'ordbillphone' => $buyerInfoShippingAddress['Phone'],
				'ordbillstateid' => (int) $buyerStateId,
				'ordbillstate' => $buyerStateName,
				'ordbillcountry' => $buyerInfoShippingAddress['CountryName'],
				'ordbillcountryid' => (int) $buyerCountryId,
				'total_ex_tax' => $order['GrandTotal'],
				'total_inc_tax' => $order['GrandTotal'],
				'shipping_cost_ex_tax' => $order['ShippingTotalCost'],
				'shipping_cost_inc_tax' => $order['ShippingTotalCost'],
				'subtotal_inc_tax' => $order['SubTotal'],
				'subtotal_ex_tax' => $order['SubTotal'],
				'ebay_order_id' => $orderId,
			);
			ResetStartingOrderNumber();

			// Start the transaction
			$GLOBALS["ISC_CLASS_DB"]->StartTransaction();

			// Inserting order data
			$newOrderId = $GLOBALS["ISC_CLASS_DB"]->InsertQuery('orders', $newOrder);
			if (!$newOrderId) {
				$this->log->LogSystemDebug('ebay', $GLOBALS["ISC_CLASS_DB"]->Error());
				$GLOBALS["ISC_CLASS_DB"]->RollbackTransaction();
				return false;
			}

			$orderAddress = array(
				'first_name' => $buyerFirstName,
				'last_name' => $buyerLastName,
				'company' => $buyerInfoShippingAddress['CompanyName'],
				'address_1' => $buyerInfoShippingAddress['Street1'],
				'address_2' => $buyerInfoShippingAddress['Street2'],
				'city' => $buyerInfoShippingAddress['CityName'],
				'zip' => $buyerInfoShippingAddress['PostalCode'],
				'country_iso2' => $buyerInfoShippingAddress['Country'],
				'phone' => $buyerInfoShippingAddress['Phone'],
				'total_items' => $order['TotalQuantityPurchased'],
				'email' => $buyerEmailAddress,
				'country_id' => (int) $buyerCountryId,
				'country' => $buyerInfoShippingAddress['CountryName'],
				'state_id' => (int) $buyerStateId,
				'state' => $buyerStateName,
				'order_id' => $newOrderId,
			);

			$addressId = $GLOBALS['ISC_CLASS_DB']->insertQuery('order_addresses', $orderAddress);
			if (!$addressId) {
				$this->log->LogSystemDebug('ebay', $GLOBALS["ISC_CLASS_DB"]->Error());
				$GLOBALS["ISC_CLASS_DB"]->RollbackTransaction();
				return false;
			}

			// Inserting order shipping
			$orderShipping = array(
				'order_address_id' => $addressId,
				'order_id' => $newOrderId,
				'base_cost' => $order['ShippingTotalCost'],
				'cost_inc_tax' => $order['ShippingTotalCost'],
				'cost_ex_tax' => $order['ShippingTotalCost'],
				'method' => 'Available on eBay',
			);

			if (!$GLOBALS['ISC_CLASS_DB']->insertQuery('order_shipping', $orderShipping)) {
				$this->log->LogSystemDebug('ebay', $GLOBALS["ISC_CLASS_DB"]->Error());
				$GLOBALS["ISC_CLASS_DB"]->RollbackTransaction();
				return false;
			}

			// Go thru each sold item in the order
			foreach ($order['Transaction'] as $eachTransaction) {
				// Get product Id
				try {
					$itemObj = new ISC_ADMIN_EBAY_ITEMS($eachTransaction['ItemId']);
					$productId = $itemObj->getProductId();
				} catch (Exception $e) {
					$this->log->LogSystemDebug('ebay', $e->getMessage());
					return false;
				}

				// Inserting order product
				$productObj = new ISC_PRODUCT($productId);
				$newProduct = array(
					'orderorderid' => $newOrderId,
					'ordprodid' => $productId,
					'ordprodsku' => $productObj->GetSKU(),
					'ordprodname' => $productObj->GetProductName(),
					'ordprodtype' => $productObj->GetProductType(),
					'ordprodqty' => $eachTransaction['QuantityPurchased'],
					'base_price' => $eachTransaction['TransactionPrice'],
					'price_ex_tax' => $eachTransaction['TransactionPrice'],
					'price_inc_tax' => $eachTransaction['TransactionPrice'],
					'price_tax' => 0,
					'base_total' => $eachTransaction['TotalTransactionPrice'],
					'total_ex_tax' => $eachTransaction['TotalTransactionPrice'],
					'total_inc_tax' => $eachTransaction['TotalTransactionPrice'],
					'total_tax' => 0,
					'base_cost_price' => 0,
					'cost_price_inc_tax' => 0,
					'cost_price_inc_tax' => 0,
					'cost_price_tax' => 0,
					'ordprodweight' => $productObj->GetWeight(false),
					'ordprodoptions' => $eachTransaction['VariationOptionsString'],
					'ordprodvariationid' => $productObj->_prodvariationid,
					'ordprodwrapid' => 0,
					'ordprodwrapname' => '',
					'base_wrapping_cost' => 0,
					'wrapping_cost_ex_tax' => 0,
					'wrapping_cost_inc_tax' => 0,
					'wrapping_cost_tax' => 0,
					'ordprodwrapmessage' => '',
					'ordprodeventname' => '',
					'ordprodeventdate' => 0,
					'ordprodfixedshippingcost' => $productObj->GetFixedShippingCost(),
					'order_address_id' => $addressId,
					'ebay_item_id' => $eachTransaction['ItemId'],
					'ebay_transaction_id' => $eachTransaction['TransactionId'],
				);

				$orderProductId = $GLOBALS['ISC_CLASS_DB']->insertQuery('order_products', $newProduct);
				if (!$orderProductId) {
					$this->log->LogSystemDebug('ebay', $GLOBALS["ISC_CLASS_DB"]->Error());
					$GLOBALS["ISC_CLASS_DB"]->RollbackTransaction();
					return false;
				}

				if ($orderStatus == ORDER_STATUS_AWAITING_FULFILLMENT) {
					// update the item quantity in store
					$updatedData['quantity_remaining'] = $itemObj->getQuantityRemaining() - $eachTransaction['QuantityPurchased'];
					if (!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('ebay_items', $updatedData, "ebay_item_id='" . $eachTransaction['ItemId'] . "'")) {
						$this->log->LogSystemDebug('ebay', $GLOBALS["ISC_CLASS_DB"]->Error());
						$GLOBALS["ISC_CLASS_DB"]->RollbackTransaction();
						return false;
					}
				}
			}
			$GLOBALS["ISC_CLASS_DB"]->CommitTransaction();

			// update the store inventory if necessary
			if (GetConfig('UpdateInventoryLevels') == 1) {
				DecreaseInventoryFromOrder($newOrderId);
			}

			// Trigger new order notifications
			SendOrderNotifications($orderToken);

			$this->log->LogSystemDebug('ebay', 'An Item ('. $body['Item']['ItemID'] .') has been paid by the buyer and added to the store order (' . $newOrderId. ').');
			return true;
		}
		return false;
	}

	/**
	* Ebay: Sent to a seller when a bidder makes a best offer on an item opted into the Best Offer feature by the seller.
	*
	* My notes: of course only applies to items which were listed with BEST OFFER option on, which is only fixed price items I think?
	*
	* @param array $body
	*/
	protected function _handleBestOffer($body)
	{
		// The data fields the BestOffer notification returns are the same as those returned by GetBestOffersResponse. Check GetBestOffersResponse in the generated WsdlDoc documentation.
	}

	/**
	* Ebay: Sent to a subscribing third party for the seller when a user places a bid for an item.
	*
	* My notes: in other words, this notification happens when one of our seller's items receives a valid bid
	*
	* @param array $body
	* @return boolean Return true if database updated correct. Otherwise, return false
	*/
	protected function _handleBidReceived($body)
	{
		// The data fields the BidReceived notification returns are the same as those returned by GetItemResponse with the default detail specified.
		if (!empty ($body['Item']['ItemID']) && ISC_ADMIN_EBAY::validEbayItemId($body['Item']['ItemID'])) {
			$this->log->LogSystemDebug('ebay', 'An Item ('. $body['Item']['ItemID'] .') has just been bidded.');
			$updatedData = array();
			if (!empty ($body['Item']['SellingStatus']['CurrentPrice']['!currencyID']) && !empty ($body['Item']['SellingStatus']['CurrentPrice']['!'])) {
				$updatedData['current_price'] = $body['Item']['SellingStatus']['CurrentPrice']['!'];
				$updatedData['current_price_currency'] = $body['Item']['SellingStatus']['CurrentPrice']['!currencyID'];
				$updatedData['bid_count'] = $body['Item']['SellingStatus']['BidCount'];
			}
			return $GLOBALS['ISC_CLASS_DB']->UpdateQuery('ebay_items', $updatedData, "ebay_item_id='".$body['Item']['ItemID']."'");
		}
		return false;
	}

	/**
	* Ebay: Sent when an auction ends. An auction ends either when its duration expires or the buyer purchases an item with Buy It Now. Applies to all competitive-bid auctions.
	*
	* My notes: Triggered when one of our seller's AUCTION items ends, for all sorts of reasons, usually followed by other notifications that contain more specific details.
	*
	* For example, if our seller cancels an item which has no bids on it, the following notifications happen as separate http requests:
	*
	* EndOfAuction
	* ItemClosed
	* ItemUnsold
	* ... perhaps more paypal / checkout related ones
	*
	* @param array $body
	*/
	protected function _handleEndOfAuction($body)
	{
		// The EndOfAuction notification returns the same data as the GetItemTransactions call with the ReturnAll detail level.
	}

	/**
	* Ebay: Sent to third parties subscribed on a user's behalf when feedback comments are received by that user.
	*
	* My notes: Triggered when our seller receives feedback.
	*
	* Note from Gwilym: I'm not sure if there is item-specific information in this notification, because you can only send feedback if your account has been open for 5 days (even on the sandbox) so it's untested as of writing
	*
	* @param array $body
	*/
	protected function _handleFeedbackReceived($body)
	{
		// The data fields the FeedbackReceived notification returns are the same as those returned by GetFeedbackResponse with the ReturnAll detail level.
	}

	/**
	* Ebay: Sent to a seller when a fixed-price item is sold and the buyer completes the checkout process. Not sent when a fixed-price item's duration expires without purchase.
	*
	* My notes: I haven't been able to test it on the sandbox due to ebay sandbox <-> paypal sandbox integration issues which I've reported to ebay with no response so far
	*
	* @param array $body
	*/
	protected function _handleFixedPriceEndOfTransaction($body)
	{
		// The FixedPriceEndOfTransaction notification returns the same data as the GetItemTransactions call with the default detail level.
	}

	/**
	* Ebay: Sent to a seller when a buyer purchases a fixed-price item.
	*
	* My notes: Vague, much? This seems to be triggered after a buyer has comitted to buying one of our seller's FIXED PRICE items, at the start of the checkout process, but before it has actually been paid for
	*
	* @param array $body
	*/
	protected function _handleFixedPriceTransaction($body)
	{
		// The FixedPriceTransaction notification returns the same data as the GetItemTransactions call with the ReturnAll detail level.
	}

	/**
	* Ebay docs: Specifies that an ItemClosed notification event has occurred. This event is triggered by ItemWon, ItemSold, and ItemUnsold events.
	*
	* My notes: Vague, much? I have seen this event in the following situations so far:
	* - Seller cancelled a fixed-price item
	* - Seller cancelled an auction item without bids
	* - Seller cancelled an auction item with bids (without choosing to sell the item)
	*
	* This notification may happen when the paypal process finishes but I haven't been able to test this.
	*
	* @param array $body
	*/
	protected function _handleItemClosed($body)
	{
		// The data fields the ItemClosed notification returns are the same as those returned by GetItemResponse with the default detail level.
	}

	/**
	* Specifies an ItemExtended notification event, when a seller has extended the duration of a listing.
	*
	* My notes: Untested, I don't know when this fires but I assume there's some method of extending the ending time of an auction if there's no bids yet. I could not find any docs on the xml data for this, I assume it is the same as ItemListed and ItemRevised.
	*
	* @param array $body
	*/
	protected function _handleItemExtended($body)
	{
		// payload: ?
	}

	/**
	* Ebay: Sent to an eBay partner on behalf of a seller when a seller has listed an item. Sent for each item the seller lists.
	*
	* My notes: The 'eBay partner' is the ISC store in this case. This notification happens when an item is successfully listed on eBay.
	*
	* @param array $body
	* @return boolean Return true if database updated correct. Otherwise, return false
	*/
	protected function _handleItemListed($body)
	{
		// The data fields the ItemListed notification returns are the same as those returned by GetItem with the default detail level. Check GetItemResponse (which contains Item) in the generated WSDLDoc documentation.
		if (!empty ($body['Item']['ItemID']) && ISC_ADMIN_EBAY::validEbayItemId($body['Item']['ItemID'])) {
			$this->log->LogSystemDebug('ebay', 'An Item ('. $body['Item']['ItemID'] .') has just been listed on eBay.');
			$updatedData = array();
			$updatedData['listing_status'] = 'active';
			$updatedData['ebay_item_link'] = $body['Item']['ListingDetails']['ViewItemURL'];
			return $GLOBALS['ISC_CLASS_DB']->UpdateQuery('ebay_items', $updatedData, "ebay_item_id='".$body['Item']['ItemID']."'");
		}
		return false;
	}

	/**
	* Ebay: Sent to an eBay partner on behalf of a seller when a seller has revised an item.
	*
	* @param array $body
	* @return boolean Return true if database updated correct. Otherwise, return false
	*/
	protected function _handleItemRevised($body)
	{
		// The data fields the ItemRevised notification returns are the same as those returned by GetItem with the default detail level. Check GetItemResponse (which contains Item) in the generated WSDLDoc documentation.
		if (!empty ($body['Item']['ItemID']) && ISC_ADMIN_EBAY::validEbayItemId($body['Item']['ItemID'])) {
			$this->log->LogSystemDebug('ebay', 'An Item ('. $body['Item']['ItemID'] .') has just been revised.');
			$updatedData = array();
			if (!empty ($body['Item']['SellingStatus']['CurrentPrice']['!currencyID']) && !empty ($body['Item']['SellingStatus']['CurrentPrice']['!'])) {
				$updatedData['current_price'] = $body['Item']['SellingStatus']['CurrentPrice']['!'];
				$updatedData['current_price_currency'] = $body['Item']['SellingStatus']['CurrentPrice']['!currencyID'];
			}
			if (!empty ($body['Item']['BuyItNowPrice']['!currencyID']) && !empty ($body['Item']['BuyItNowPrice']['!'])) {
				$updatedData['buyitnow_price'] = $body['Item']['BuyItNowPrice']['!'];
				$updatedData['buyitnow_price_currency'] = $body['Item']['BuyItNowPrice']['!currencyID'];
			}
			if ($body['Item']['ListingType'] == 'FixedPriceItem') {
				$updatedData = array('quantity_remaining' => $body['Item']['Quantity']);
			}
			$updatedData['title'] = $body['Item']['Title'];
			return $GLOBALS['ISC_CLASS_DB']->UpdateQuery('ebay_items', $updatedData, "ebay_item_id='".$body['Item']['ItemID']."'");
		}
		return false;
	}

	/**
	* Ebay: Sent to an eBay partner on behalf of a seller when a seller has revised an item and added charity.
	*
	* My notes: I couldn't find where to test this so I'm not sure if this fires instead of ItemRevised, or if both fire. I could not find any docs on the xml data for this, I assume it is the same as ItemRevised.
	*
	* @param array $body
	*/
	protected function _handleItemRevisedAddCharity($body)
	{
		// payload: ?
	}

	/**
	* Ebay: Specifies an ItemSold notification event, triggered when an eBay listing ends in a sale.
	*
	* My notes: This happens before any payment stuff, it just means that the auction ended with a winner
	*
	* @param array $body
	* @return boolean Return true if database updated correct. Otherwise, return false
	*/
	protected function _handleItemSold($body)
	{
		// The data fields the ItemSold notification returns are the same as those returned by GetItemResponse. Check GetItemResponse in the generated WsdlDoc documentation.
		if (!empty ($body['Item']['ItemID']) && ISC_ADMIN_EBAY::validEbayItemId($body['Item']['ItemID'])) {
			$updatedData = array();
			if (!empty ($body['Item']['ListingType'])) {
				switch ($body['Item']['ListingType']) {
					case 'Chinese':
						// Update the item status to "won"
						$updatedData = array('listing_status' => 'won');
						$this->log->LogSystemDebug('ebay', 'An Item ('. $body['Item']['ItemID'] .') on eBay has been won.');
						break;
					case 'FixedPriceItem':
						// Update the item status to "sold"
						$updatedData['listing_status'] = 'sold';
						$updatedData['quantity_remaining'] = $body['Item']['Quantity'];
						$this->log->LogSystemDebug('ebay', 'An Item ('. $body['Item']['ItemID'] .') on eBay has been sold.');
						break;
				}
				if (!empty ($updatedData)) {
					return $GLOBALS['ISC_CLASS_DB']->UpdateQuery('ebay_items', $updatedData, "ebay_item_id='".$body['Item']['ItemID']."'");
				}
			}
		}
		return false;
	}

	/**
	* Ebay: Specifies an ItemSuspended notification event. Subscribe to this event to be notified when eBay has taken down a listing for a listing problem, for example, listing in the wrong category.
	*
	* My notes: I could not find any docs on the xml data for this, but I assume there's an itemid in there somewhere so we should be able to flag the auction as incorrect.
	*/
	protected function _handleItemSuspended($body)
	{
		// payload: ?
	}

	/**
	* Ebay: Sent to a subscribing third party for the seller when an item was not sold.
	*
	* My notes: I've seen this notification when items are cancelled, but I assume it also happens when the auction timer ends with no bids.
	*
	* @param array $body
	* @return boolean Return true if database updated correct. Otherwise, return false
	*/
	protected function _handleItemUnsold($body)
	{
		// The data fields the ItemUnsold notification returns are the same as those returned by GetItemResponse with the default detail level.
		if (!empty ($body['Item']['ItemID']) && ISC_ADMIN_EBAY::validEbayItemId($body['Item']['ItemID'])) {
			$updatedData = array('listing_status' => 'unsold');
			$this->log->LogSystemDebug('ebay', 'An Item ('. $body['Item']['ItemID'] .') on eBay has been unsold.');
			return $GLOBALS['ISC_CLASS_DB']->UpdateQuery('ebay_items', $updatedData, "ebay_item_id='".$body['Item']['ItemID']."'");
		}
		return false;
	}
}
