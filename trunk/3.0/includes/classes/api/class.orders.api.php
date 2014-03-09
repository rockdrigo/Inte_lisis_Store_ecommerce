<?php
class API_ORDERS extends API_BASE
{
	/**
	* GetOrders
	* Gets a list of orders that match the searchinfo parameters sent
	* with the request. These are identical to the options available
	* from the advanced order search in the control panel and that's the
	* exact system we tie into
	*/
	public function Action_GetOrders()
	{
		foreach($this->router->request->details as $field => $val) {
			foreach($val as $k => $v) {
				$_REQUEST[$k] = (string)$v;
			}
		}

		$start = 0;
		if(!empty($_REQUEST['start'])) {
			$start = (int)$_REQUEST['start'];
		}
		if(!isset($_REQUEST['noPaging'])) {
			$addLimit = ISC_ORDERS_PER_PAGE;
		}
		else {
			$addLimit = false;
		}

		$orders = GetClass('ISC_ADMIN_ORDERS');
		$order_grid = $orders->_GetOrderList($start, "orderid", "asc", $num_orders, $addLimit);
		$order_array = array();

		if($num_orders > 0) {
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($order_grid)) {
				unset($row['extrainfo'], $row['customertoken'], $row['custpassword'], $row['customerpasswordresettoken'], $row['custimportpassword']);
				$order_array['item'][] = $row;
			}
		}

		return array(
			'start' => $start,
			'end' => min($start + ISC_ORDERS_PER_PAGE, $num_orders),
			'numResults' => $num_orders,
			'results' => $order_array
		);
	}

	public function Action_GetOrder()
	{
		if(empty($this->router->request->details->orderId)) {
			$this->BadRequest('The details->orderId node is missing');
		}

		$orderId = (int)$this->router->request->details->orderId;

		$query = "
			SELECT o.*, s.statusdesc AS ordstatusname
			FROM [|PREFIX|]orders o
			LEFT JOIN [|PREFIX|]order_status s ON (s.statusid=o.ordstatus)
			WHERE o.orderid='".(int)$orderId."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$order = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		unset($order['extrainfo']);
		if(!$order) {
			return array();
		}

		// Fetch the items for the order
		$query = "
			SELECT *
			FROM [|PREFIX|]order_products
			WHERE orderorderid='".$order['orderid']."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$ordAddressIds = array();
		while($item = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$ordAddressIds[] = $item['order_address_id'];
			$options = unserialize($item['ordprodoptions']);
			unset($item['ordprodoptions']);
			if (!empty($options)) {
				$ops = array();
				foreach ($options as $k => $v) {
					$ops[] = $k.': '.$v;
				}

				// format this product option nicely into 1 string
				$item['ordprodoptions'] = implode(', ', $ops);
			} else {
				$item['ordprodoptions'] = '';
			}
			$order['items']['item'][] = $item;
		}

		// Fetch the order shipping address and method
		$query = "
			SELECT
				*
			FROM
				[|PREFIX|]order_addresses a
			LEFT JOIN
				[|PREFIX|]order_shipping s
			ON
				a.id = s.order_address_id
			WHERE
				a.id IN ('".implode(',', $ordAddressIds)."')
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$order['order_addresses'] = array();
		while ($ordAddress = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			unset($ordAddress['order_id']);
			unset($ordAddress['order_address_id']);
			unset($ordAddress['form_session_id']);
			$order['order_addresses']['item'][] = $ordAddress;
		}

		// Fetch the taxes applied to this order
		$query = "
			SELECT *
			FROM [|PREFIX|]order_taxes
			WHERE order_id='".$order['orderid']."'
		";
		$order['taxes'] = array();
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($tax = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$order['taxes']['item'][] = $tax;
		}

		// Fetch any configurable items
		$query = "
			SELECT *
			FROM [|PREFIX|]order_configurable_fields
			WHERE orderid='".$order['orderid']."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($field = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			if($field['filename']) {
				$order['configurablefields']['item'][] = array(
					'ordprodid' => $field['ordprodid'],
					'fieldname' => $field['fieldname'],
					'file' => GetConfig('ShopPathNormal').'/'.GetConfig('ImageDirectory').'/configured_products/'.$field['filename']
				);
			}
			else {
				$order['configurablefields']['item'][] = array(
					'ordprodid' => $field['ordprodid'],
					'fieldname' => $field['fieldname'],
					'textcontents' => $field['textcontents']
				);
			}
		}

		return $order;
	}
}
