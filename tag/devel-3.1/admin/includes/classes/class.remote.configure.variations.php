<?php

if (!defined('ISC_BASE_PATH')) {
	die();
}

/**
 * A class processing the remote request for eBay functions
 */
class ISC_ADMIN_REMOTE_CONFIGURE_VARIATIONS extends ISC_ADMIN_REMOTE_BASE {

	/**
	 * The constructor.
	 */
	public function __construct()
	{
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('products');
		parent::__construct();

		GetLib('class.json');
	}

	public function HandleToDo()
	{
		if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Variations)) {
			exit;
		}

		$what = isc_strtolower(@$_REQUEST['w']);

		$methodName = $what . 'Action';
		if(!method_exists($this, $methodName)) {
			exit;
		}
	ISC_JSON::output('', true, array('done' => true));
		$this->$methodName();
	}
	
	private function getAffectedVariationsAction($output=true)
	{
		/**
		 * Make sure we have our variation
		 */
		$variatonIdx = array();
		if (isset($_REQUEST['variationIdx'])) {
			$variatonIdx = explode(',', $_REQUEST['variationIdx']);
			$variatonIdx = array_filter($variatonIdx, 'isId');
		}

		if (empty($variatonIdx)) {
			print '';
			exit;
		}

		/**
		 * Also make sure that we were given a type (either 'edit' or 'delete') because without this then we do not
		 * know what is being removed
		 */
		$type = '';
		if (isset($_REQUEST['actionType'])) {
			$type = strtolower($_REQUEST['actionType']);
		}

		/**
		 * See if we were passed any option value Ids to cross check with
		 */
		$valueIdx = array();
		if (isset($_REQUEST['optionValueIdx'])) {
			$valueIdx = explode(',', $_REQUEST['optionValueIdx']);
			$valueIdx = array_filter($valueIdx, 'isId');
		}

		$affected = "";
		if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Edit_Products)) {
			$canEdit = true;
		} else {
			$canEdit = false;
		}


		/**
		 * If we are deleting or adding then just work on the $variatonIdx. 'Add' goes in here aswell because if a value is added then ALL existing combintaions
		 * for that variation are invalid
		 */
		$tmpVarId = null;
		$tmpVarName = '';
		$products = array();

		$query = "SELECT v.variationid, v.vname, p.productid, p.prodname
					FROM [|PREFIX|]product_variations v
					JOIN [|PREFIX|]products p ON v.variationid = p.prodvariationid
					WHERE v.variationid IN(" . implode(',', $variatonIdx) . ")
					ORDER BY v.variationid";

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			if (is_null($tmpVarId) || $tmpVarId !== $row['variationid']) {
				if (isId($tmpVarId)) {
					$GLOBALS['ProductName'] = $tmpVarName;
					$GLOBALS['ProductVariationList'] = '<li>' . implode('</li><li>', $products) . '</li>';
					$affected .= $this->template->render('Snippets/VariationAffectedProducts.html');
				}

				$tmpVarId = $row['variationid'];
				$tmpVarName = $row['vname'];
				$products = array();
			}

			if ($canEdit) {
				$products[] = '<a class="Action" target="_blank" href="index.php?ToDo=editProduct&amp;productId=' . (int)$row['productid'] . '" title="' . isc_html_escape($row['prodname']) . '">' . isc_html_escape($row['prodname']) . '</a>';
			} else {
				$products[] = isc_html_escape($row['prodname']);
			}
		}

		/**
		 * Get the last one
		 */
		if (!empty($products)) {
			$GLOBALS['ProductName'] = $tmpVarName;
			$GLOBALS['ProductVariationList'] = '<li>' . implode('</li><li>', $products) . '</li>';
			$affected .= $this->template->render('Snippets/VariationAffectedProducts.html');
		}

		$GLOBALS['AffectedProducts'] = $affected;
		$GLOBALS['ProductVariationPopupIntro'] = GetLang('ProductVariationPopup' . ucfirst(strtolower($type)) . 'Intro');

		if ($output == true) {
			// if no product is affected, skip
			if (empty($affected)) {
				echo '0';
			} else {
				echo '1';
			}
		}
	}

	private function viewAffectedVariationsAction()
	{
		$this->getAffectedVariationsAction(false);
		$this->template->display('products.variation.affected.popup.tpl');
		exit;
	}

	private function initRecaulculateCombinationsAction()
	{
		$variationId = $_GET['variationId'];

		$productIDx = array();
		if (isset($_REQUEST['productIDx'])) {
			$productIDx = explode(',', $_REQUEST['productIDx']);
			$productIDx = array_filter($productIDx, 'isId');
		}

		if (empty($productIDx)) {
			print '';
			exit;
		}

		$productList = '';
		foreach($productIDx as $key => $value)
		{
			$productList .= $value.', ';
		}
		//$productList = substr($productList, strlen($productList-1));
		//print("--".$productList."--<br />");
		$this->template->assign('productList', $productList);
		$this->template->assign('variationId', $variationId);
		$this->template->assign('totalProducts', count($productIDx));
		$this->template->display('configure.variation.recalculate.popup.tpl');
	}

	private function continueRecalculateCombinationsAction()
	{
		$productId = $_REQUEST['productId'];
//ISC_JSON::output("--".$productId."--<br />", true);
		// no more products to process? done.

	}
}
