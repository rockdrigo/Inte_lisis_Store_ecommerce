<?php

class RULE_BUYXGETX extends ISC_RULE
{
	private $amount;
	private $prodids;
	private $ps;
	private $amount_free;
	protected $vendorSupport = true;

	protected $banner = array();

	public function __Construct()
	{
		parent::__construct();

		$this->setName('BUYXGETX');
		$this->displayName = GetLang($this->getName().'displayName');

		$this->addJavascriptValidation('amount', 'int');
		$this->addJavascriptValidation('amount_free', 'int');
		$this->addJavascriptValidation('prodids', 'string');

		$this->addActionType('freeitem');
		$this->ruleType = 'Product';
	}

	public function initializeAdmin()
	{
		$quantity = 1;
		$quantity_free = 1;

		if (isset($GLOBAL['var_amount'])) {
			$quantity = $GLOBAL['var_amount'];
		}
		if (isset($GLOBAL['var_amount_free'])) {
			$quantity_free = $GLOBAL['var_amount_free'];
		}

		// If we're using a cart quantity drop down, load that
		if (GetConfig('TagCartQuantityBoxes') == 'dropdown') {
			$GLOBALS['SelectId'] = "amount";
			$GLOBALS['Qty0'] = Interspire_Template::getInstance('admin')->render('Snippets/DiscountItemQtySelect.html');
		// Otherwise, load the textbox
		} else {
			$GLOBALS['SelectId'] = "amount";
			$GLOBALS['Qty0'] = Interspire_Template::getInstance('admin')->render('Snippets/DiscountItemQtyText.html');
		}

		if (!isset($GLOBALS['var_ps'])) {
			$GLOBALS['var_ps'] = GetLang('ChooseAProduct');
		}

		// If we're using a cart quantity drop down, load that
		if (GetConfig('TagCartQuantityBoxes') == 'dropdown') {
			$GLOBALS['SelectId'] = "amount_free";
			$GLOBALS['Qty1'] = Interspire_Template::getInstance('admin')->render('Snippets/DiscountItemQtySelect.html');
		// Otherwise, load the textbox
		} else {
			$GLOBALS['SelectId'] = "amount_free";
			$GLOBALS['Qty1'] = Interspire_Template::getInstance('admin')->render('Snippets/DiscountItemQtyText.html');
		}

		if (!isset($GLOBALS['var_ps_free'])) {
			$GLOBALS['var_ps_free'] = GetLang('ChooseAProduct');
		}
	}

	public function initialize($data)
	{
		parent::initialize($data);

		$tmp = unserialize($data['configdata']);

		$this->amount = $tmp['var_amount'];
		$this->prodids = $tmp['var_prodids'];
		$this->ps = $tmp['var_ps'];
		$this->amount_free = $tmp['var_amount_free'];
	}

	public function applyRule(ISC_QUOTE $quote)
	{
		$applies = false;
		$items = $quote->getItems();
		foreach($items as $item) {
			// Skip items that have a parent ID or don't match the product ID necessary
			if($item->getParentId() || $item->getProductId() != $this->prodids) {
				continue;
			}

			$quantity = $item->getQuantity();
			$freeItems = 0;

			while ($quantity >= $this->amount) {
				$quantity = $quantity - $this->amount;
				$freeItems += $this->amount_free;
			}

			foreach($items as $subItem) {
				// If the product already exists in the cart, then we can update it instead
				if($subItem->getParentId() != $item->getId()) {
						continue;
				}

				if($freeItems == 0) {
					$quote->removeItem($subItem->getId());
				}
				else {
					try {
						$subItem->setQuantity($freeItems);
					}
					catch(ISC_QUOTE_EXCEPTION $e) {
						$freeItems = 0;
						continue;
					}
					$this->banners[] = sprintf(getLang($this->getName().'DiscountMessage'), $freeItems, $subItem->getName());
					$freeItems = 0;
					$applies = true;
				}

			}

			if($freeItems > 0) {
				// Still need to add a free item to the cart
				try {
					$newItem = clone $item;
					$newItem
						->setBasePrice(0, true)
						->setQuantity($freeItems)
						->setParentId($item->getId())
						->removeGiftWrapping();
					$quote->addItem($newItem, false);

					$this->banners[] = sprintf(getLang($this->getName().'DiscountMessage'), $freeItems, $item->getName());
					$applies = true;
				}
				catch(ISC_QUOTE_EXCEPTION $e) {
					$applies = false;
					continue;
				}
			}
		}

		return $applies;
	}

	public function haltReset(ISC_QUOTE $quote)
	{
		$items = $quote->getItems();
		foreach($items as $item) {
			if($item->getParentId() || $item->getProductId() != $this->prodids) {
				continue;
			}

			foreach($items as $subItem) {
				if($subItem->getParentId() == $item->getId()) {
					$quote->removeItem($subItem->getId());
				}
			}
		}
	}

}