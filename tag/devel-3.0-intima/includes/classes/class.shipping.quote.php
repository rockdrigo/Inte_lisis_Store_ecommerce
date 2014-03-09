<?php

	/**
	* The Interspire Shopping Cart shipping quote class, used by the shipping base class.
	* A shipping provider will return one or more quote objects when a
	* shipping quote is requested from Interspire Shopping Cart.
	*/
	class ISC_SHIPPING_QUOTE
	{

		/*
			The price of the shipping quote
		*/
		private $_price = 0;

		/*
			The description of the shipping quote
		*/
		private $_desc = "";

		/*
			The id of the shipping company
		*/
		private $_shipperid = "";

		/*
			The name of the shipping company
		*/
		private $_shippername = "";

		/*
			The transit time for the shipping quote
		*/
		private $_transit = "";

		/*
			Setup item variables in the constructor
		*/
		public function __construct($shipper_id, $shipper_name, $price, $desc="", $transit_time="")
		{
			$this->_price = doubleval(str_replace(',', '', $price));
			$this->_shipperid = $shipper_id;
			$this->_shippername = $shipper_name;
			$this->_desc = $desc;
			$this->_transit = $transit_time;
		}

		/*
			Return the price of the quote
		*/
		public function getprice()
		{
			return $this->_price;
		}

		/*
			Set the price of the quote
		*/
		public function setprice($Price)
		{
			$this->_price = $Price;
		}

		/*
			Return the description of the quote. If $include_shipper_name is true
			then the quote will be returned like so: Canada Post (Expedited).
			If not, then just the "Expedited" part will be returned.
		*/
		public function getdesc($include_shipper_name=false)
		{

			// Hack for Intershipper and FedEx
			$this->_desc = str_replace("FedEx FedEx", "FedEx", $this->_desc);
			$this->_desc = str_replace("FedEx FDX", "FedEx", $this->_desc);

			if($this->_desc != "") {
				if($include_shipper_name) {
					return sprintf("%s (%s)", $this->_shippername, $this->_desc);
				}
				else {
					return $this->_desc;
				}
			}
			else {
				return $this->_shippername;
			}
		}

		/*
			Return the transit time of the quote (in days)
		*/
		public function gettransit()
		{
			return $this->_transit;
		}
	}