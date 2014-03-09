<?php

class Store_CreditCard
{
	/**
	 * Get the credit card type based on the account number.
	 * $ccnum - The account number for the credit card.
	 * $includeJCBLaser - Flag for whether we should also include JCB & Laser in the list of valid credit card types. False by default.
	 * @return string card type
	 */
	public function getCardType($ccnum,$includeJCBLaser=false)
	{
		/**
		 * The account number patterns
		 */

		$card_types = array(
			'Visa' => array(
				'type' => 'Visa',
				'regexp' => '^4[0-9]{15,18}$',
				'requiresCVV2' => true
			),
			'AMEX' => array(
				'type' => 'AMEX',
				'regexp' => '^(34|37)[0-9]{13}$',
				'requiresCVV2' => true
			),
			'Mastercard' => array(
				'type' => 'Mastercard',
				'regexp' => '^5[1-5]{1}[0-9]{14}$',
				'requiresCVV2' => true
			),
			'DinersClub' => array(
				'type' => 'DinersClub',
				'regexp' => '^(30|36|38|55)[0-9]{12}([0-9]{2})?$',
				'requiresCVV2' => false
			),
			'Discover' => array(
				'type' => 'Discover',
				'regexp' => '^6011[0-9]{12}$',
				'requiresCVV2' => true
			),
			'Solo' => array(
				'type' => 'Solo',
				'regexp' => '^6767[0-9]{12}([0-9]{2,3})?$',
				'requiresCVV2' => true,
				'hasIssueNo' => true,
				'hasIssueDate' => true
			),
			'Maestro' => array(
				'type' => 'Maestro',
				'regexp' => '^(50[0-9]{4}|5[6-8][0-9]{4}|6[0-9]{5})[0-9]{6,13}$',
				'requiresCVV2' => true,
				'hasIssueNo' => true,
				'hasIssueDate' => true
			),
			'Switch' => array(
				'type' => 'Switch',
				'regexp' => '^6759[0-9]{12}([0-9]{2,3})?$',
				'requiresCVV2' => true,
				'hasIssueNo' => true,
				'hasIssueDate' => true
			),
		);

		if ($includeJCBLaser == true) {
			$card_types['Laser'] = array(
				'type' => 'Laser',
				'regexp' => '^(6304|6706|6771|6709)[0-9]{12,15}?$'
			);
			$card_types['JCB'] = array(
				'type' => 'JCB',
				'regexp' => '^35(2[8-9]|[3-8][0-9])[0-9]{12}$',
				'requiresCVV2' => true
			);
		}

		/**
		 * validate number against types
		 */
		foreach ($card_types as $cardType) {
			if(preg_match("/".$cardType['regexp']."/", $ccnum)) {
				return $cardType['type'];
			}
		}
		return 'Generic 1';
	}
}