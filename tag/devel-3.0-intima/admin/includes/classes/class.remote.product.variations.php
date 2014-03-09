<?php

if (!defined('ISC_BASE_PATH')) {
	die();
}

/**
 * A class processing the remote request for eBay functions
 */
class ISC_ADMIN_REMOTE_PRODUCT_VARIATIONS extends ISC_ADMIN_REMOTE_BASE {

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

	private function initRebuildVariationsAction()
	{
		$sessionId = $_POST['session'];

		if (!isset($_SESSION['variations'][$sessionId])) {
			exit;
		}

		$session = &$_SESSION['variations'][$sessionId];

		$session['lastProductId'] = 0;

		$query = "
			SELECT
				COUNT(*)
			FROM
				[|PREFIX|]products
			WHERE
				prodvariationid = " . $session['variationId'] . "
		";

		$res = $this->db->Query($query);
		$productCount = $this->db->FetchOne($res);

		$this->template->assign('sessionId', $sessionId);
		$this->template->assign('totalProducts', $productCount);
		$this->template->display('products.variations.ajaxupdate.tpl');
	}

	private function continueRebuildVariationsAction()
	{
		$sessionId = $_POST['session'];

		if (!isset($_SESSION['variations'][$sessionId])) {
			ISC_JSON::output('session ' . $sessionId . ' not found', false);
		}

		$session = &$_SESSION['variations'][$sessionId];

		// get the next product id
		$query = "
			SELECT
				productid
			FROM
				[|PREFIX|]products
			WHERE
				productid > " . $session['lastProductId'] . " AND
				prodvariationid = " . $session['variationId'] . "
			ORDER BY
				productid
			LIMIT
				1
		";

		$res = $this->db->Query($query);
		$productId = $this->db->FetchOne($res);

		// no more products to process? done.
		if (empty($productId)) {
			unset($_SESSION['variations'][$sessionId]);
			if (empty($_SESSION['variations'])) {
				unset($_SESSION['variations']);
			}
			ISC_JSON::output('', true, array('done' => true));
		}

		if ($this->db->StartTransaction() === false) {
			ISC_JSON::output('failed to start transaction', false);
		}

		$existingData = $session['existingData'];

		// were new option values (eg a new colour) added? we'll need to create some blank combinations to fill in the missing gaps.
		if (!empty($session['newValues'])) {
			$newValues = $session['newValues'];

			// iterate over the new option values
			foreach ($newValues as $optionName => $newValueIds) {
				foreach ($newValueIds as $newValueId) {
					// build combination id set
					$optionIdSets = array();

					foreach ($existingData['options'] as $optionIndex => $option) {
						if ($optionName == $option['name']) {
							$optionIdSets[$optionIndex][] = $newValueId;
							continue;
						}

						foreach ($option['values'] as $valueIndex => $value) {
							$optionIdSets[$optionIndex][] = $value['valueid'];
						}
					}

					// build a cartesian product of all the combinations that we need to generate
					$cartesian = Interspire_Array::generateCartesianProduct($optionIdSets);

					// iterate over each combination and insert to DB for all products using this variation
					foreach ($cartesian as $combination) {
						$combinationString = implode(',', $combination);

						$newCombination = array(
							'vcproductid'		=> $productId,
							'vcvariationid'		=> $session['variationId'],
							'vcoptionids'		=> $combinationString,
							'vclastmodified'	=> time(),
						);

						$this->db->InsertQuery('product_variation_combinations', $newCombination);
					}
				}
			}
		}

		// process new option set (eg. Material)
		if (!empty($session['newOptionValues'])) {
			$valuesForNewOption = $session['newOptionValues'];

			$likeMatch = str_repeat(",%", count($existingData['options']) - 2);
			$likeMatch = "%" . $likeMatch;

			foreach ($valuesForNewOption as $newOptionIndex => $newValueIds) {
				$newOptionCount = 0;

				foreach ($newValueIds as $newValueId) {
					// for the first new option value, we don't want to insert new combinations, but update the existing ones
					// store the option id for later and continue on
					if ($newOptionCount == 0) {
						$delayForUpdate = $newValueId;

						$newOptionCount++;
						continue;
					}

					$query = "
						INSERT INTO
							[|PREFIX|]product_variation_combinations (vcproductid, vcproducthash, vcvariationid, vcenabled, vcoptionids, vcsku, vcpricediff, vcprice, vcweightdiff, vcweight, vcimage, vcimagezoom, vcimagestd, vcimagethumb, vcstock, vclowstock, vclastmodified)
							SELECT
								vcproductid,
								vcproductid,
								vcvariationid,
								vcenabled,
								CONCAT(vcoptionids, ',', " . $newValueId . "),
								vcsku,
								vcpricediff,
								vcprice,
								vcweightdiff,
								vcweight,
								vcimage,
								vcimagezoom,
								vcimagestd,
								vcimagethumb,
								vcstock,
								vclowstock,
								" . time() . "
							FROM
								[|PREFIX|]product_variation_combinations
							WHERE
								vcproductid = " . $productId . " AND
								vcproducthash = ''
					";

					$this->db->Query($query);

					$newOptionCount++;
				}
			}

			// for the first new option id, add it onto the remaining existing row
			if (!empty($delayForUpdate)) {
				$query = "
					UPDATE
						[|PREFIX|]product_variation_combinations
					SET
						vcoptionids = CONCAT(vcoptionids, ',', " . $delayForUpdate . ")
					WHERE
						vcproductid = " . $productId . " AND
						vcproducthash = ''
				";

				$this->db->Query($query);
			}

			// blank the hash
			$query = "
				UPDATE
					[|PREFIX|]product_variation_combinations
				SET
					vcproducthash = ''
				WHERE
					vcproductid = " . $productId . "
			";
			$this->db->Query($query);
		}

		if ($this->db->CommitTransaction() === false) {
			$this->db->RollbackTransaction();
			ISC_JSON::output('failed to commit transaction', false);
		}

		$session['lastProductId'] = $productId;

		ISC_JSON::output('', true);
	}
}
