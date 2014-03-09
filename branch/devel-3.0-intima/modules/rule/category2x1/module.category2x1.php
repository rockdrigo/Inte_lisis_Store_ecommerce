<?php

class RULE_CATEGORY2X1 extends ISC_RULE
{
	private $catids;
	private $daysactive;
	protected $vendorSupport = true;

	public function __Construct()
	{
		parent::__construct();

		$this->setName('CATEGORY2X1');
		$this->displayName = GetLang($this->getName().'displayName');

		$this->addJavascriptValidation('catids', 'array');
		$this->addJavascriptValidation('daysactive', 'array');

		$this->addActionType('orderdiscount');
		$this->ruleType = 'Order';
	}
	
	private function getDaysSelector($SelectedCats = 0, $Container = "<option %s value='%d'>%s</option>", $Sel = "selected=\"selected\"", $Divider = "- ")
	{
		// Get a list of categories as <option> tags
		$cats = '';
	
		// Make sure $SelectedCats is an array
		if (!is_array($SelectedCats)) {
			$SelectedCats = array();
		}
	
		//date('N');
		// Get a formatted list of all the categories in the system
		$categories = array(
			1 => 'Lunes',
			2 => 'Martes',
			3 => 'Miercoles',
			4 => 'Jueves',
			5 => 'Viernes',
			6 => 'Sabado',
			0 => 'Domingo',
		);
	
		foreach ($categories as $cid => $cname) {
			if (in_array($cid, $SelectedCats)) {
				$s = $Sel;
			} else {
				$s = '';
			}
			$cats .= sprintf($Container, $s, $cid, $cname);
		}
	
		return $cats;
	}

	public function initializeAdmin()
	{
		if (!empty($this->catids)) {
			$selectedCategories = explode(',', $this->catids);
		} else {
			$selectedCategories = array();
		}
		
		if (!empty($this->daysactive)) {
			$selectedDays = explode(',', $this->daysactive);
		} else {
			$selectedDays = array();
		}
		

		$GLOBALS['ISC_CLASS_ADMIN_CATEGORY'] = GetClass('ISC_ADMIN_CATEGORY');
		$GLOBALS['CategoryList'] = $GLOBALS["ISC_CLASS_ADMIN_CATEGORY"]->GetCategoryOptions($selectedCategories, "<option %s value='%d'>%s</option>", 'selected="selected"', " ", false);
		$GLOBALS['DaysActive'] = $this->getDaysSelector($selectedDays, "<option %s value='%d'>%s</option>", 'selected="selected"', " ", false);
		
		if (count($selectedCategories) < 1) {
			$GLOBALS['AllCategoriesSelected'] = "selected=\"selected\"";
		}
	}


	public function initialize($data)
	{
		parent::initialize($data);

		$tmp = unserialize($data['configdata']);

		$this->catids = $tmp['var_catids'];
		$this->daysactive = $tmp['var_daysactive'];
	}

	public function applyRule(ISC_QUOTE $quote)
	{
		$applies = false;

		if(!in_array(isc_date('w'), explode(',', $this->daysactive))) return $applies;
		
		$items = $quote->getItems();
		$ruleCats = explode(',', $this->catids);
		/*
		 * contar el numero de articulos en las categorias indicadas, y dar la mitad gratis, de los productos mas baratos
		 */
		
		$numItems = count($items);
		$itemsApplied = array();
		//$quote->resetDiscountRules();
		//$this->banners= array();
		
		$howMany=0;
		foreach($items as $item) {
			$item->getBaseTotal();
			$categoryIds = $item->getCategoryIds();
			foreach($ruleCats as $categoryId) {
				if(!in_array($categoryId, $categoryIds) && $categoryId != 0) {
					continue;
				}

				$itemsApplied[$item->getPrice()] = $item;
				$howMany+=$item->getQuantity();
			}
		}

		if(!empty($itemsApplied)) {
			
			//Se aplica la regla para la mitad redondeada hacia abajo
			$howMany = floor($howMany / 2);
			
			krsort($itemsApplied);
			
			$totalDiscount = 0;
			
			while($howMany != 0) {
				$applies = true;
				$cheapestItem = array_pop($itemsApplied);

				if($howMany>=$cheapestItem->getQuantity()){
					$totalDiscount += $cheapestItem->getBaseTotal();
					$cheapestItem->addDiscount($this->getDbId(), $cheapestItem->getBaseTotal());
					$howMany-=$cheapestItem->getQuantity();
				}
				else {
					$totalDiscount+= $howMany * $cheapestItem->getPrice(true);
					$cheapestItem->addDiscount($this->getDbId(), $howMany * $cheapestItem->getPrice(true));
					$howMany=0;
				}
			}
		
		$quote->addDiscount($this->getDbId(), $totalDiscount);
		$this->banners[] = sprintf(getLang($this->getName().'DiscountMessage'), currencyConvertFormatPrice($totalDiscount));
		}

		return $applies;
	}

	public function haltReset(ISC_QUOTE $quote)
	{
		return false;
	}
}
