<?php
	require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'class.api.php');

	class API_DISCOUNT extends API
	{
		protected $fields = array (
			'discountid',
			'discountname',
			'discountruletype',
			'discountmaxuses',
			'discountcurrentuses',
			'discountexpiry',
			'discountenabled',
			'halts',
			'configdata',
			'sortorder',
			'free_shipping_message',
			'free_shipping_message_location',
		);

		public $pk = 'discountid';
		protected $discountid = 0;
		protected $discountruletype = '';
		protected $discountname = '';
		protected $discountmaxuses = 0;
		protected $discountcurrentuses = 0;
		protected $discountexpiry = 0;
		protected $discountenabled = 0;
		protected $halts = 0;
		protected $configdata = '';
		protected $sortorder = 0;
		protected $free_shipping_message = '';
		protected $free_shipping_message_location = '';

		public function create()
		{
			return parent::create();
		}

		public function getSortOrder()
		{
			return $this->sortorder;
		}

		public function DiscountExists($id)
		{
			if (!$this->db) {
				return -1;
			}

			if (empty($id)) {
				return 0;
			}

			$query = 'SELECT COUNT(*)
				FROM '.$this->table."
				WHERE discountid='".$this->db->Quote($id)."'";

			return $this->db->FetchOne($query);
		}

	}