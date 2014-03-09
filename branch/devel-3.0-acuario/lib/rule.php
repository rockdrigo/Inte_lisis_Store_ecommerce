<?php
	require_once(dirname(__FILE__).'/module.php');
	require_once(dirname(__FILE__) . "/../includes/classes/class.rule.php");

	function getDiscountRulesById(array $ruleIds)
	{
		$rules = array();
		$query = "
			SELECT *
			FROM [|PREFIX|]discounts
			WHERE discountid IN (".implode(',', $ruleIds).")
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		while($rule = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			$ruleObject = null;
			getModuleById('rule', $ruleObject, $rule['discountruletype']);
			if(!is_object($ruleObject)) {
				continue;
			}

			$ruleObject->initialize($rule);
			$rules[$rule['discountid']] = $ruleObject;
		}

		return $rules;
	}


	/**
	 * Update Rule Uses
	 *
	 * This method will update the rules usage in the database.
	 *
	 * @access public
	 * @param array $ruleUses - An array of all the rules to be updated
	 */
	function UpdateRuleUses($ruleUses=array())
	{
		if (!is_array($ruleUses) || empty($ruleUses)) {
			return;
		}

		$query = "
			UPDATE [|PREFIX|]discounts
			SET discountcurrentuses=discountcurrentuses + 1
			WHERE discountid IN (".implode(',', $ruleUses).")
		";
		$GLOBALS['ISC_CLASS_DB']->query($query);
	}

	/**
	 * Get Rule Module Info
	 *
	 * Retrieves a list of discount rules enabled by the customer
	 *
	 * @access public
	 * @param string $type - The type of the rules
	 * @return array Returns an array of initialized rules
	 */
	function GetRuleModuleInfo($type='all')
	{
		static $cache = array();
		if(isset($cache[$type])) {
			return $cache[$type];
		}

		if ($type == 'all') {
			$query = "
				SELECT *
				FROM [|PREFIX|]discounts ORDER BY sortorder";
		}
		else {
			$query = "
				SELECT *
				FROM [|PREFIX|]discounts
				WHERE discountruletype='rule_".$type . "'
				ORDER BY sortorder";
		}

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		while($var = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {

			GetModuleById('rule', $object, $var['discountruletype']);

			$object->initialize($var);

			$cache[$type][] = $object;
		}

		if (isset($cache[$type])) {
			return $cache[$type];
		} else {
			return array();
		}
	}