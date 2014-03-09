<?php

class RULE_XPERCENTOFFFORREPEATCUSTOMERS extends ISC_RULE
{
	private $orders;
	private $amount;

	public function __Construct()
	{
		parent::__construct();

		$this->setName('XPERCENTOFFFORREPEATCUSTOMERS');
		$this->displayName = GetLang($this->getName().'displayName');

		$this->addJavascriptValidation('orders', 'int');
		$this->addJavascriptValidation('amount', 'int', 1, 100);

		$this->addActionType('orderdiscount');
		$this->ruleType = 'Order';
	}

	public function initialize($data)
	{
		parent::initialize($data);

		$tmp = unserialize($data['configdata']);

		$this->amount = $tmp['var_amount'];
		$this->orders = $tmp['var_orders'];
	}

	public function initializeAdmin()
	{
		$quantity = 1;

		if (isset($GLOBAL['var_orders'])) {
			$quantity = $GLOBAL['var_orders'];
		}

		// If we're using a cart quantity drop down, load that
		if (GetConfig('TagCartQuantityBoxes') == 'dropdown') {
			$GLOBALS['SelectId'] = "orders";
			$GLOBALS['Qty0'] = Interspire_Template::getInstance('admin')->render('Snippets/DiscountItemQtySelect.html');
		// Otherwise, load the textbox
		} else {
			$GLOBALS['SelectId'] = "orders";
			$GLOBALS['Qty0'] = Interspire_Template::getInstance('admin')->render('Snippets/DiscountItemQtyText.html');
		}

		if (!isset($GLOBALS['var_ps'])) {
			$GLOBALS['var_ps'] = GetLang('ChooseAProduct');
		}

	}

	public function resetState(ISC_QUOTE $quote)
	{
		$quote->addDiscount($this->getDbId(), 0);

		// Remove the discount amounts for this rule from all products
		foreach($quote->getItems() as $item) {
			$item->addDiscount($this->getDbId(), 0);
		}
	}

	public function applyRule(ISC_QUOTE $quote)
	{
		if (!customerIsSignedIn()) {
			return null;
		}

		$customerId = getClass('ISC_CUSTOMER')->getCustomerId();
		$query = "
			SELECT COUNT(*)
			FROM [|PREFIX|]orders
			WHERE ordcustid='".$customerId."' AND ordstatus > 0 AND deleted = 0
			LIMIT 1
		";
		$orderCount = $GLOBALS['ISC_CLASS_DB']->fetchOne($query);

		// Discount does not apply
		if($orderCount <= $this->orders) {
			return false;
		}

		$items = $quote->getItems();
		$totalDiscount = 0;
		// The discount needs to come off each item, so that tax is also affected.
		foreach($items as $item) {
			$discountAmount = $item->getDiscountedBaseTotal() * ($this->amount / 100);
			$item->addDiscount($this->getDbId(), $discountAmount);
			$totalDiscount += $discountAmount;
		}

		$quote->addDiscount($this->getDbId(), $totalDiscount);
		$this->banners[] = sprintf(getLang($this->getName().'DiscountMessage'), $this->amount);
		return true;
	}

	public function haltReset()
	{
		return false;
	}

}
