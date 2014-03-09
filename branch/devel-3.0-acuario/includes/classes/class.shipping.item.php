<?php

	/**
	* The Interspire Shopping Cart shipping item class, used by the shipping base class.
	* All products in an order are added as SHIPPING_ITEM objects before
	* the shipping calculation is made by each shipping module.
	*/
	class ISC_SHIPPING_ITEM
	{

		/*
			The weight of the item to be shipped
		*/
		private $_weight = 0;

		/*
			The length of the item to be shipped
		*/
		private $_length = 0;

		/*
			The width of the item to be shipped
		*/
		private $_width = 0;

		/*
			The height of the item to be shipped
		*/
		private $_height = 0;

		/*
			The quantity of the item to be shipped
		*/
		private $_qty = 1;

		/*
			The description of the item to be shipped
		*/
		private $_desc = "";

		private $_cost = 0;

		/*
			Setup item variables in the constructor
		*/
		public function __construct($weight, $length=0, $width=0, $height=0, $qty=1, $desc="", $cost=0)
		{
			$this->_weight = $weight;
			$this->_length = $length;
			$this->_width = $width;
			$this->_height = $height;
			$this->_qty = $qty;
			$this->_desc = $desc;
			$this->_cost = $cost;
		}

		/*
			Return the weight of this shipping item
		*/
		public function getweight()
		{
			return $this->_weight;
		}

		/*
			Return the length of this shipping item
		*/
		public function getlength()
		{
			return $this->_length;
		}

		/*
			Return the width of this shipping item
		*/
		public function getwidth()
		{
			return $this->_width;
		}

		/*
			Return the height of this shipping item
		*/
		public function getheight()
		{
			return $this->_height;
		}

		/*
			Return the quantity of this shipping item
		*/
		public function getquantity()
		{
			return $this->_qty;
		}

		/*
			Return the description of this shipping item
		*/
		public function getdesc()
		{
			return $this->_desc;
		}
	}