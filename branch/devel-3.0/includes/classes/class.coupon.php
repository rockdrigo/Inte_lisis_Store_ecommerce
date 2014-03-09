<?php

class ISC_COUPON
{
	/**
	 * Identify same "customer" as someone with the same customer id, or ip address or email address.
	 *
	 * @return array
	 */
	public function getCustomerIdentifiers()
	{
		$ip = getIp();
		$quote = getCustomerQuote();
		$customerid = $quote->getCustomerId();
		$email = $quote->getBillingAddress()->getEmail();
		if ($email == '') {
			$customer = getCustomer($customerid);
			$email = $customer['custconemail'];
		}

		$identifiers = array(
			$ip,
			$customerid,
			$email,
		);

		return $identifiers;
	}


	/**
	 * Retrieve per customer usage detail for a particular coupon
	 *
	 * @param int $couponid reference to coupon id
	 *
	 * @return array
	 */
	public function getPerCustomerUsage($couponid)
	{
		$identifiers = $this->getCustomerIdentifiers();
		$usages = array();
		$query = "
			SELECT
				*
			FROM
				[|PREFIX|]coupon_usages
			WHERE
				coupon_id = ".$GLOBALS['ISC_CLASS_DB']->quote($couponid)." AND
				customer IN ('".implode("','", $GLOBALS['ISC_CLASS_DB']->Quote($identifiers))."')";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		while($row = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			$usages[$row['customer']] = $row;
		}

		return $usages;
	}


	/**
	 * Check if per customer usage limit has been hit
	 *
	 * @param int $couponid reference to coupon id
	 * @param int $limit    usage limit per customer
	 *
	 * @return boolean
	 */
	public function isPerCustomerUsageLimitReached($couponid, $limit='')
	{
		if ($limit === '') {
			// not provided, try to retrieve from db
			$query = '
				SELECT
					couponmaxusespercus
				FROM
					[|PREFIX|]coupons
				WHERE
					couponid = '.(int)$couponid;
			$result = $GLOBALS['ISC_CLASS_DB']->fetchOne($query);
			if ($result == false) {
				$limit = 0;
			} else {
				$limit = (int)$result;
			}
		}

		if ($limit === 0) {
			// disabled or coupon not found
			return false;
		}

		$usages = $this->getPerCustomerUsage($couponid, $this->getCustomerIdentifiers());
		foreach ($usages as $u) {
			if ($u['numuses'] >= $limit) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Update the coupon usage count per customer, insert if it is a new entry
	 *
	 * @param int $couponid reference to coupon id
	 *
	 * @return void
	 */
	public function updatePerCustomerUsage($couponid)
	{
		$identifiers = $this->getCustomerIdentifiers();
		$usages = $this->getPerCustomerUsage($couponid, $identifiers);
		foreach ($identifiers as $i) {
			if ($i === 0 || $i === '0' || empty($i) || $i === '') {
				// guest or empty email
				continue;
			}

			if (!isset($usages[$i])) {
				// add new entry
				$entry = array(
					'coupon_id' => $couponid,
					'customer' => $i,
					'numuses' => 1,
				);
				$GLOBALS['ISC_CLASS_DB']->insertQuery('coupon_usages', $entry);
			} else {
				// increment existing entry
				$query = '
					UPDATE
						[|PREFIX|]coupon_usages
					SET
						numuses = numuses+1
					WHERE
						id = '.(int)$usages[$i]['id'];
				$GLOBALS['ISC_CLASS_DB']->query($query);
			}
		}
	}

	/**
	 * A function to check if the coupon is free shipping or just a normal discount coupon
	 *
	 * @param int $couponType the coupon type.
	 * @return boolean Return true the coupon type is freeshipping
	 */
	public function isFreeShippingCoupon($couponType)
	{
		$freeShippingTypesIds = array(3,4);
		if (in_array($couponType, $freeShippingTypesIds)) {
			return true;
		}
		return false;
	}
}