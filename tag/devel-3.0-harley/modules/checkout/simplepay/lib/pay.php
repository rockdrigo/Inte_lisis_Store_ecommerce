<?php

class SIMPLEPAY_PAY
{
	public function __construct($data = null)
	{
		$this->_fields = array ('amount'=>null, 'description'=>null, 'referenceId'=>null);
	}

	public function getAmount()
	{
		return $this->_fields['amount'];
	}

	public function setAmount($value)
	{
		$this->_fields['amount'] = $value;
	}

	public function getDescription()
	{
		return $this->_fields['description'];
	}

	public function setDescription($value)
	{
		$this->_fields['description'] = $value;
	}

	public function getReferenceId()
	{
		return $this->_fields['referenceId'];
	}

	public function setReferenceId($value)
	{
		$this->_fields['referenceId'] = $value;
	}
}
