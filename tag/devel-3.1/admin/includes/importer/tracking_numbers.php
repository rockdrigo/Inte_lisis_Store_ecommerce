<?php
require_once(dirname(__FILE__) . "/../classes/class.batch.importer.php");

class ISC_BATCH_IMPORTER_TRACKING_NUMBERS extends ISC_BATCH_IMPORTER_BASE
{
	/**
	 * @var string The type of content we're importing. Should be lower case and correspond with template and language variable names.
	 */
	protected $type = "ordertrackingnumbers";

	protected $_RequiredFields = array(
		"ordernumber",
		"ordertrackingnumber"
	);

	public function __construct()
	{
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('batch.importer');

		/**
		 * @var array Array of importable fields and their friendly names.
		 */
		$this->_ImportFields = array(
			"ordernumber" => GetLang('OrderNumber'),
			"ordertrackingnumber" => GetLang('OrdTrackingNo'),
		);

		parent::__construct();
	}

	/**
	 * Custom step 1 code specific to tracking number importing. Calls the parent ImportStep1 funciton.
	 */
	protected function _ImportStep1($MsgDesc="", $MsgStatus="")
	{
		if ($MsgDesc != "" && !isset($GLOBALS['Message'])) {
			$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
		}
		// Set up generic import options
		parent::_ImportStep1();
	}

	/**
	 * Custom step 2 code specific to product importing. Calls the parent ImportStep2 funciton.
	 */
	protected function _ImportStep2($MsgDesc="", $MsgStatus="")
	{
		// Set up generic import options
		if ($MsgDesc != "") {
			$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
		}
		$this->ImportSession['updateOrderStatus'] = $_POST['updateOrderStatus'];
		parent::_ImportStep2();
	}

	/**
	 * Imports an tracking numbers in to the database.
	 *
	 * @param array Array of record data
	 */
	protected function _ImportRecord($record)
	{
		if(trim($record['ordernumber']) == "") {
			$this->ImportSession['Results']['Failures'][] = implode(",", $record['original_record'])." ".GetLang('ImportMissingOrderNumber');
			return;
		}

		$record['ordertrackingnumber'] = trim($record['ordertrackingnumber']);
		if($record['ordertrackingnumber'] == "") {
			$this->ImportSession['Results']['Failures'][] = implode(",", $record['original_record'])." ".GetLang('ImportMissingTrackingNumber');
			return;
		}

		if(isc_strlen($record['ordertrackingnumber']) > 50) {
			$this->ImportSession['Results']['Failures'][] = implode(",", $record['original_record'])." ".GetLang('ImportTrackingNumberTooLong');
			return;
		}

		// Does the order number exist in the database?
		$query = "SELECT orderid FROM [|PREFIX|]orders WHERE orderid='".(int)$record['ordernumber']."' AND ordisdigital = 0 AND deleted = 0";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$order = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if(!$order['orderid']) {
			$this->ImportSession['Results']['Failures'][] = implode(",", $record['original_record'])." ".GetLang('ImportInvalidOrderNumber');
			return;
		}

		// Order exists and has physical items

		// Tracking numbers are now on shipments, not orders, so are there any un-shipped items in this order?
		$unshippedProducts = array();
		$query = "
			SELECT
				op.orderprodid,
				op.order_address_id,
				op.ordprodqty,
				op.ordprodqtyshipped,
				os.method,
				os.module
			FROM
				[|PREFIX|]order_products op,
				[|PREFIX|]order_shipping os
			WHERE
				op.orderorderid = " . $order['orderid'] . "
				AND op.ordprodtype = 'physical'
				AND op.ordprodqty > op.ordprodqtyshipped
				AND os.order_address_id = op.order_address_id
			ORDER BY
				op.order_address_id,
				op.orderprodid
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($product = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$unshippedProducts[] = $product;
		}

		if (empty($unshippedProducts) && (!isset($this->ImportSession['OverrideDuplicates']) || $this->ImportSession['OverrideDuplicates'] != 1)) {
			// cannot apply tracking number to order with all items shipped unless override duplicates is set
			$this->ImportSession['Results']['Duplicates'][] = $record['ordernumber']." ".$record['ordertrackingnumber'];
			return;
		}

		// the import format only allows for one tracking number per order so this tracking number gets applied to all shipments

		$existingSuccess = true;
		if (isset($this->ImportSession['OverrideDuplicates']) && $this->ImportSession['OverrideDuplicates'] == 1) {
			$query = "
				UPDATE [|PREFIX|]shipments
				SET shiptrackno = '" . $GLOBALS['ISC_CLASS_DB']->Quote($record['ordertrackingnumber']) . "'
				WHERE shiporderid = " . $order['orderid'] . "
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			if (!$result) {
				$existingSuccess = false;
				$this->ImportSession['Results']['Failures'][] = implode(",", $record['original_record'])." ".GetLang('ImportUpdateShipmentsFailed');
			}
		}

		/** @var ISC_ADMIN_SHIPMENTS */
		$shipments = GetClass('ISC_ADMIN_SHIPMENTS');

		// create shipments for unshipped products
		$totalShipments = 0;
		$totalSuccess = 0;
		$totalFail = 0;

		$quantity = array();
		reset($unshippedProducts);
		while ($product = current($unshippedProducts)) {
			next($unshippedProducts);
			$nextProduct = current($unshippedProducts);

			// add product=>qty to shipment
			$quantity[$product['orderprodid']] = $product['ordprodqty'] - $product['ordprodqtyshipped'];

			if ($nextProduct && $nextProduct['order_address_id'] == $product['order_address_id']) {
				// next product is for the same address, skip shipment creation for now
				continue;
			}

			// next product is a different shipment so commit this one before proceeding
			$shipment = array(
				'orderId' => $order['orderid'],
				'shiptrackno' => $record['ordertrackingnumber'],
				'addressId' => $product['order_address_id'],
				'shipping_module' => $product['module'],
				'shipmethod' => $product['method'],
				'shipcomments' => '',
				'quantity' => $quantity,
			);

			if (isset($this->ImportSession['updateOrderStatus']) && $this->ImportSession['updateOrderStatus']!=0) {
				$shipment['ordstatus'] = (int)$this->ImportSession['updateOrderStatus'];
			}

			$totalShipments++;
			if ($shipments->CommitShipment($shipment)) {
				// commit success
				$this->ImportSession['Results']['Updates'][] = $record['ordernumber']." ".$record['ordertrackingnumber'];
				$totalSuccess++;
			} else {
				// fail
				$this->ImportSession['Results']['Failures'][] = implode(",", $record['original_record'])." ".GetLang('ImportCreateShipmentFailed');
				$totalFail++;
			}

			// reset
			$quantity = array();
		}

		if ($existingSuccess && $totalSuccess == $totalShipments) {
			// all success or no new shipments were needed
			$orderData = array(
				"orddateshipped" => isc_gmmktime(),
			);
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery("orders", $orderData, "orderid='".$order['orderid']."'");
			++$this->ImportSession['Results']['SuccessCount'];
		} else {
			// total or partial failure
			$this->ImportSession['Results']['Failures'][] = implode(",", $record['original_record'])." ".GetLang('ImportInvalidOrderNumber');
			return;
		}
	}
}
