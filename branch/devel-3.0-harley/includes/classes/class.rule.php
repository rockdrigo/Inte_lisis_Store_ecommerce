<?php
require_once(dirname(__FILE__).'/class.module.php');

class ISC_RULE extends ISC_MODULE
{
	/**
	 * @var string The type of module this is.
	 */
	protected $type = 'rule';

	/**
	 * @var string The name of the module.
	 */
	protected $displayName = '';

	/**
	 * @var string The category of the rule (Order, Item, ect). Used for display purposes
	 */
	protected $ruleType = 'Default';

	/**
	 * @var int A unique id for the rule. Uses the id from the database
	 */
	protected $dbid = null;

	/**
	 * @var int The amount that the subtotal changed by
	 */
	protected $subtotal = 0;

	/**
	 * @var int The percentage that the subtotal changed by
	 */
	protected $subtotalpercent = 0;

	/**
	 * @var int The total amount the item was modified by
	 */
	protected $itemdiscount = 0;

	/**
	 * @var boolean The type of module this is.
	 */
	protected $halt = false;

	/**
	 * @var boolean The type of module this is.
	 */
	protected $enabled = false;

	/**
	 * @var int When the rule expires
	 */
	protected $expiry = 0;

	/**
	 * @var int The amount of items that the rule applied to. Used to update the uses counter
	 */
	protected $uses = 0;

	/**
	 * @var int The maximum amount of uses available
	 */
	protected $maxuses = 0;

	/**
	 * @var boolean Does the rule support vendors?
	 */
	protected $vendorSupport = false;

	/**
	 * @var array A list of built in javascript validations that apply to the rule
	 */
	protected $javascriptValidations = array();

	/**
	 * @var array Array containing one or more banners set by this discount rule.
	 */
	protected $banners = array();

	/**
	 * @var array Array containing the data of free shipping eligibility (only apply to free shipping related discount rules).
	 */
	protected $freeShippingEligibilityData = array();

	public function getSubTotalChanges()
	{
		return $this->subtotal;
	}

	public function getSubTotalPercentChanges()
	{
		return $this->subtotalpercent;
	}

	public function getItemDiscounts()
	{
		return $this->itemdiscount;
	}

	public function getUses()
	{
		return $this->uses;
	}

	public function getDbId()
	{
		return $this->dbid;
	}

	public function checkHalt()
	{
		return $this->halt;
	}

	public function vendorSupport()
	{
		return $this->vendorSupport;
	}

	/**
	 * Enabled
	 *
	 * Checks the rule maxuses and expiry date to see if it's valid
	 *
	 * @access public
	 * @return bool enabled
	 */
	public function enabled()
	{

		if ($this->maxuses != 0 && $this->uses >= $this->maxuses) {
			return false;
		}
		// We add 86399 to the expiry because the expiry is stored as the start of the day
		// 86399 adds 23 hours and 59 minutes and 59 seconds to the expiry date
		if ($this->expiry != 0 && isc_mktime() > ($this->expiry + 86399)) {
			return false;
		}

		return $this->enabled;
	}

	public function initializeAdmin()
	{

	}

	public function getRuleType()
	{
		return $this->ruleType;
	}

	public function addActionType($type)
	{
		$this->actionTypes[] = $type;
	}

	public function getDisplayName()
	{
		return $this->displayName;
	}

	public function initialize($data)
	{

		$this->dbid 	= $data['discountid'];
		$this->halt 	= $data['halts'];
		$this->enabled 	= $data['discountenabled'];
		$this->expiry 	= $data['discountexpiry'];
		$this->uses 	= $data['discountcurrentuses'];
		$this->maxuses 	= $data['discountmaxuses'];
		$this->freeShippingMessage = $data['free_shipping_message'];
		$this->freeShippingMessageLocation = array();
		if (trim($data['free_shipping_message_location'])) {
			$this->freeShippingMessageLocation 	= unserialize($data['free_shipping_message_location']);
		}


	}

	/**
	 * Get Javascript Validation
	 *
	 * This gets the javascript validation code for all the rules available.
	 *
	 * @access public
	 * @return string A string of javascript content
	 */
	public function getJavascriptValidation()
	{

		$jsString = 'function ' . strtolower('rule_'.$this->getName()) . '() {';

		foreach ($this->javascriptValidations as $jV) {
			$jsString .= $jV;
		}

		return $jsString . 'return true; }

				';
	}

	/**
	 * Add Javascript Validation
	 *
	 * This creates the javascript code used to validate the modules on the admin section
	 *
	 * @access public
	 * @param string $id - The id of the rule
	 * @param string $type - The type of the rule
	 * @param int $min - The maximum value allowed in the field
	 * @param int $max - The minimum value allowed in the field
	 */
	public function addJavascriptValidation($id, $type, $min=0, $max=10000)
	{

		if ($type == 'int') {

			$this->javascriptValidations[] = '

					var '.$id.' = document.getElementById("'.$id.'");

					if (isNaN(parseInt('.$id.'.value))) {
						alert("'.sprintf(GetLang($this->GetName().'EnterDiscount'.$id), $min, $max).'");
						'.$id.'.focus();
						'.$id.'.select();
						return false;
					}
					if (parseInt('.$id.'.value) < '.$min.') {
						alert("'.sprintf(GetLang($this->GetName().'EnterMin'.$id), $min, $max).'");
						'.$id.'.focus();
						'.$id.'.select();
						return false;
					}
					if (parseInt('.$id.'.value) > '.$max.') {
						alert("'.sprintf(GetLang($this->GetName().'EnterMax'.$id), $min, $max).'");
						'.$id.'.focus();
						'.$id.'.select();
						return false;
					}';


		} else if ($type == 'string') {

			$this->javascriptValidations[] = '

					var '.$id.' = $("#'.$id.'");

					if ('.$id.'.val() == "") {
						alert("'.GetLang($this->GetName().'EnterDiscount'.$id).'");
						'.$id.'.focus();
						'.$id.'.select();
						return false;
					}';
		} else if ($type == 'array') {

			$this->javascriptValidations[] = '

					var '.$id.' = document.getElementById("var_'.$id.'");

						if ('.$id.'.selectedIndex == -1) {
						alert("'.GetLang($this->GetName().'EnterDiscount'.$id).'");
						return false;
					}';
		}
	}

	public function getBanners()
	{
		return $this->banners;
	}

	public function resetState()
	{

	}

	public function checkFreeShippingEligibility(ISC_QUOTE $quote)
	{

	}

	/**
	 * This function reset the free shipping eligibility message
	 */
	public function resetFreeShippingEligibility()
	{
		$this->freeShippingEligibilityData = array();
	}

	/**
	 * This function return the free shipping eligibility message
	 */
	public function getFreeShippingEligibilityData()
	{
		return $this->freeShippingEligibilityData;
	}
}