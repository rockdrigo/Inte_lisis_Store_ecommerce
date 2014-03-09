<?php
class API_CUSTOMERS extends API_BASE
{
	/**
	* GetCustomers
	* Gets a list of customers that match the searchinfo parameters sent
	* with the request. These are identical to the options available
	* from the advanced customer search in the control panel and that's the
	* exact system we tie into
	*/
	public function Action_GetCustomers()
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

		$addLimit = ISC_CUSTOMERS_PER_PAGE;
		if(isset($_REQUEST['noPaging']) && (int)$_REQUEST['noPaging'] == 1) {
			// no limit
			$addLimit = false;
		}

		$customers = GetClass('ISC_ADMIN_CUSTOMERS');
		$customer_grid = $customers->_GetCustomerList($start, "customerid", "asc", $num_customers, $addLimit);
		$customer_array = array();

		if($num_customers > 0) {
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($customer_grid)) {
				$customer_array['item'][] = $row;
			}
		}

		return array(
			'start' => $start,
			'end' => min($start + ISC_CUSTOMERS_PER_PAGE, $num_customers),
			'numResults' => $num_customers,
			'results' => $customer_array
		);
	}
}