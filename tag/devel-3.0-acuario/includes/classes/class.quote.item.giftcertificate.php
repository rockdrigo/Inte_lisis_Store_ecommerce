<?php
class ISC_QUOTE_ITEM_GIFTCERTIFICATE extends ISC_QUOTE_ITEM
{
	protected $type = PT_GIFTCERTIFICATE;
	protected $recipient = array(
		'name' => '',
		'email' => '',
	);

	protected $sender = array(
		'name' => '',
		'email' => '',
	);

	protected $message = '';

	protected $theme;

	/**
	* Overload of ISC_QUOTE_ITEM method of same name to enforce fixed value for gift certificates.
	*
	* @return double
	*/
	public function getWeight()
	{
		return 0;
	}

	/**
	* Overload of ISC_QUOTE_ITEM method of same name to enforce fixed value for gift certificates.
	*
	* @return double
	*/
	public function getFixedShippingCost()
	{
		return 0;
	}

	public function setRecipientName($name)
	{
		$this->recipient['name'] = $name;
		return $this;
	}

	public function getRecipientName()
	{
		return $this->recipient['name'];
	}

	public function getRecipientEmail()
	{
		return $this->recipient['email'];
	}

	public function setRecipientEmail($email)
	{
		$this->recipient['email'] = $email;
		return $this;
	}

	public function setSenderName($name)
	{
		$this->sender['name'] = $name;
		return $this;
	}

	public function getSenderName()
	{
		return $this->sender['name'];
	}

	public function setSenderEmail($email)
	{
		$this->sender['email'] = $email;
		return $this;
	}

	public function getSenderEmail()
	{
		return $this->sender['email'];
	}

	public function setTheme($theme)
	{
		$this->theme = $theme;
		return $this;
	}

	public function getTheme()
	{
		return $this->theme;
	}

	public function setMessage($message)
	{
		$this->message = $message;
		return $this;
	}

	public function getMessage()
	{
		return $this->message;
	}

	public function setQuantity($quantity)
	{
		$this->quantity = $quantity;
		return $this;
	}

	public function getType()
	{
		return $this->type;
	}

	public function generateId()
	{
		$hash = array(
			$this->getPrice(),
			$this->getRecipientName(),
			$this->getRecipientEmail(),
			$this->getSenderName(),
			$this->getSenderEmail(),
			$this->getTheme(),
			$this->getMessage()
		);
		$this->id = md5(serialize($hash));
		return $this;
	}

	public function setBasePrice($price, $isCustomPrice = true)
	{
		// $isCustomPrice is always true for gift certificates
		return parent::setBasePrice($price, true);
	}

	public function getPrice()
	{
		return $this->getBasePrice();
	}
}