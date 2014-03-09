<?php

if (!defined('ISC_BASE_PATH')) {
	die();
}

class ISC_ADMIN_REMOTE_LINKER extends ISC_ADMIN_BASE
{
	public function __construct()
	{
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('linker');

		parent::__construct();
	}

	public function HandleToDo()
	{
		/**
		 * Convert the input character set from the hard coded UTF-8 to their
		 * selected character set
		 */
		convertRequestInput();

		GetLib('class.json');

		$what = isc_strtolower(@$_REQUEST['w']);

		switch ($what) {
			case "loadlinker":
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->IsLoggedIn()) {
					$this->loadLinker();
				}
				exit;
				break;
			case "search":
				if ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->IsLoggedIn()) {
					$this->search();
				}
				exit;
				break;
		}
	}

	private function loadLinker()
	{
		if (isset($_GET['tabs'])) {
			$tabs = $_GET['tabs'];
			if (is_string($tabs)) {
				$tabs = explode(',', $tabs);
			} else if (!is_array($tabs)) {
				$tabs = array($tabs);
			}
		} else {
			$tabs = array('product','category','brand','page');
		}

		$this->template->assign('tabs', $tabs);
		$this->template->assign('firstTab', $tabs[0]);
		$this->template->display('linker.tpl');
	}

	private function search()
	{
		if (!isset($_GET['d'])) {
			return;
		}

		switch ($_GET['d']) {
			case "categories":
				$this->GetCategories();
				break;
			case "brands":
				$this->GetBrands();
				break;
			case "pages":
				$this->GetPages();
				break;
			case "products":
				$this->GetProducts();
				break;
		}
	}

	private function GetCategories()
	{
		header('Content-type: text/xml');
		// Return a list of categories
		echo '<?xml version="1.0"?>';
		echo '<results>';
		$GLOBALS['ISC_CLASS_ADMIN_CATEGORY'] = GetClass('ISC_ADMIN_CATEGORY');
		$categories = $GLOBALS['ISC_CLASS_ADMIN_CATEGORY']->getCats("");
		foreach($categories as $catid => $catname) {
			$catpadding = substr_count($catname, '&nbsp') * 6;
			if($catpadding > 0) {
				$catpadding = sprintf('padding="%d"', $catpadding);
			}
			else {
				$catpadding = '';
			}
			$catname = preg_replace('/^(&nbsp;)*/', '', $catname);
			$catname = preg_replace('/(&nbsp;)*$/', '', $catname);
			$catlink = CatLink($catid, $catname);
			echo sprintf('<result title="%s" icon="images/category.gif" catid="%s" id="%s" %s><![CDATA[%s]]></result>', isc_html_escape($catname), $catid, $catid, $catpadding, $catlink);
		}
		echo '</results>';
	}

	private function GetBrands()
	{
		header('Content-type: text/xml');
		echo '<?xml version="1.0"?>';
		echo '<results>';

		$hasBrands = false;
		$query = "SELECT * FROM [|PREFIX|]brands ORDER BY brandname ASC";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			echo sprintf('<result title="%s" icon="images/brand.gif" id="%s"><![CDATA[%s]]></result>', isc_html_escape(isc_html_escape($row['brandname'])), $row['brandid'], BrandLink($row['brandname']));
			$hasBrands = true;
		}
		if(!$hasBrands) {
			echo "<error>".GetLang('DevEditLinkerNoBrands')."</error>";
		}
		echo '</results>';
	}

	private function GetPages()
	{
		header('Content-type: text/xml');
		echo '<?xml version="1.0"?>';
		echo '<results>';

		$GLOBALS['ISC_CLASS_ADMIN_AUTH'] = GetClass('ISC_ADMIN_AUTH');
		$GLOBALS['ISC_CLASS_ADMIN_PAGES'] = GetClass('ISC_ADMIN_PAGES');

		$pages = $GLOBALS['ISC_CLASS_ADMIN_PAGES']->_getPagesArray();

		//$GLOBALS['ISC_CLASS_LOG']->LogSystemDebug('php', 'array', var_export($pages, true));

		if(empty($pages)) {
			echo "<error>".GetLang('DevEditLinkerNoPages')."</error>";
		}
		else {
			foreach ($pages as $page) {
				if($page['pagetype'] != 1) {
					$pageLink = PageLink($page['pageid'], $page['pagetitle']);
				}
				else {
					$pageLink = $page['pagelink'];
				}

				echo sprintf('<result title="%s" icon="images/page.gif" padding="' . (($page['depth'] * 18) + 6) . '" id="%s"><![CDATA[%s]]></result>', isc_html_escape(isc_html_escape($page['pagetitle'])), $page['pageid'], $pageLink);
			}
		}

		echo '</results>';
	}

	private function GetProducts()
	{
		header('Content-type: text/xml');
		echo '<?xml version="1.0"?>';
		echo '<results>';

		if(!isset($_REQUEST['searchQuery']) && !isset($_REQUEST['category']) || (isset($_REQUEST['searchQuery']) && isc_strlen($_REQUEST['searchQuery']) <= 3)) {
			echo "<error>".GetLang('DevEditLinkerEnterSearchTerms')."</error>";
		}
		else {
			$_REQUEST['category'] = array($_REQUEST['category']);
			$ResultCount = 0;
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE'] = GetClass('ISC_ADMIN_ENGINE');
			$GLOBALS['ISC_CLASS_ADMIN_AUTH'] = GetClass('ISC_ADMIN_AUTH');
			$GLOBALS['ISC_CLASS_ADMIN_PRODUCT'] = GetClass('ISC_ADMIN_PRODUCT');
			$products = $GLOBALS['ISC_CLASS_ADMIN_PRODUCT']->_GetProductList(0, 'prodname', 'asc', $ResultCount, 'p.productid,p.prodname', false);

			if($ResultCount == 0) {
				if(isset($_REQUEST['searchQuery'])) {
					echo "<error>".GetLang('DevEditLinkerNoProducts')."</error>";
				}
				else {
					echo "<error>".GetLang('DevEditLinkerNoCategoryProducts')."</error>";
				}
			}
			else {
				while($product = $GLOBALS['ISC_CLASS_DB']->Fetch($products)) {
					echo sprintf('<result title="%s" icon="images/product.gif" id="%s"><![CDATA[%s]]></result>', isc_html_escape(isc_html_escape($product['prodname'])), $product['productid'], ProdLink($product['prodname']));
				}
			}
		}
		echo '</results>';
	}
}