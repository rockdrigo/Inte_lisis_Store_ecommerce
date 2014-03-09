<?php

class RULE_BUYXGETFREESHIPPING extends ISC_RULE
{
	private $amount;
	private $prodids;
	protected $vendorSupport = true;

	public function __Construct($amount=0, $prodids=array())
	{
		parent::__construct();

		$this->amount = $amount;
		$this->prodids = $prodids;

		$this->setName('BUYXGETFREESHIPPING');
		$this->displayName = GetLang($this->getName().'displayName');

		$this->addJavascriptValidation('amount', 'int');
		$this->addJavascriptValidation('prodids', 'string');

		$this->ruleType = 'Order';

	}

	public function initialize($data)
	{
		parent::initialize($data);

		$tmp = unserialize($data['configdata']);

		$this->amount = $tmp['var_amount'];
		$this->prodids = $tmp['var_prodids'];
	}

	public function initializeAdmin()
	{
		$quantity = 1;

		if (isset($GLOBAL['var_amount'])) {
			$quantity = $GLOBAL['var_amount'];
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
	}

	public function resetState(ISC_QUOTE $quote)
	{
		$quote->setHasFreeShipping(false);
	}

	public function applyRule(ISC_QUOTE $quote)
	{
		foreach($quote->getItems() as $item) {
			if($this->prodids == $item->getProductId() && $item->getQuantity() >= $this->amount) {
				if (!$quote->getHasFreeShipping()) {
					$this->banners[] = getLang($this->getName().'DiscountMessage');
					$quote->setHasFreeShipping(true);
				}
				return true;
			}
		}
		return false;
	}

	public function haltReset(ISC_QUOTE $quote)
	{
		return false;
	}

	/**
	 * This function will check against the rules if there is any related purchase can be made in order to get the free shipping.
	 * @param ISC_QUOTE $quote The quote object that used to check the free shipping eligibility
	 * @return boolean Return true if there is we found free shipping eligibility. Otherwise, return false
	 */
	public function checkFreeShippingEligibility(ISC_QUOTE $quote)
	{
		foreach($quote->getItems() as $item) {
			if($this->prodids == $item->getProductId() && $item->getQuantity() < $this->amount) {
				$remainingQty = $this->amount - $item->getQuantity();
				$productName = $item->getName();
				$placeHolders = array(
					'%%PRODUCT_NAME%%' => $productName,
					'%%REMAINING_QUANTITY%%' => $remainingQty,
					'%%TOTAL_QUANTITY%%' => $this->amount,
					'%%CART_QUANTITY%%' => $item->getQuantity(),
					'%%REMAINING_AMOUNT%%' => '',
					'%%TOTAL_AMOUNT%%' => '',
					'%%CART_AMOUNT%%' => '',
				);
				$this->freeShippingEligibilityData = array(
					'productId' => (int)$this->prodids,
					'message' => str_replace(array_keys($placeHolders), array_values($placeHolders), $this->freeShippingMessage),
					'location' => $this->freeShippingMessageLocation,
					'name' => $this->getName(),
				);
				return true;
			}
		}
		return false;
	}
}