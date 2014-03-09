<?php

class Interspire_EmailIntegration_Rule_OrderCompleted extends Interspire_EmailIntegration_Rule
{
	public $eventId = 'onOrderCompleted';

	/**
	* Returns true if the subscription qualifies for this rule based on this rule's event criteria
	*
	* @return bool
	*/
	public function qualifySubscription(Interspire_EmailIntegration_Subscription $subscription)
	{
		if (!is_array($this->eventCriteria) || empty($this->eventCriteria) || !isset($this->eventCriteria['orderType']) || !$this->eventCriteria['orderType'] || $this->eventCriteria['orderType'] == 'any') {
			// no criteria defined = qualify, or criteria specifies 'any' order
			return true;
		}

		if (!($subscription instanceof Interspire_EmailIntegration_Subscription_Order)) {
			// specific order criteria can only be checked for Interspire_EmailIntegration_Subscription_Order types
			return false;
		}

		$method = 'qualifyOrder' . ucfirst($this->eventCriteria['orderType']);
		return $this->$method($subscription, $this->eventCriteria['orderCriteria']);
	}

	/**
	* Examine order contents stored in subscription data to see if it contains a specific brand.
	*
	* @param Interspire_EmailIntegration_Subscription_Order $subscription
	* @param int $brandId
	*/
	public function qualifyOrderBrand(Interspire_EmailIntegration_Subscription_Order $subscription, $brandId)
	{
		return $subscription->containsBrand($brandId);
	}

	/**
	* Examine order contents stored in subscription data to see if it contains a product from a specific category.
	*
	* @param Interspire_EmailIntegration_Subscription_Order $subscription
	* @param int $categoryId
	*/
	public function qualifyOrderCategory(Interspire_EmailIntegration_Subscription_Order $subscription, $categoryId)
	{
		return $subscription->containsCategory($categoryId);
	}

	/**
	* Examine order contents stored in subscription data to see if it contains a specific product.
	*
	* @param Interspire_EmailIntegration_Subscription_Order $subscription
	* @param int $productId
	*/
	public function qualifyOrderProduct(Interspire_EmailIntegration_Subscription_Order $subscription, $productId)
	{
		return $subscription->containsProduct($productId);
	}
}
