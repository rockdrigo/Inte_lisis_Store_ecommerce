<?php
	require_once(dirname(__FILE__).'/class.module.php');
	/**
	* The Interspire Shopping Cart notification base class, used by all notification modules
	*/
	class ISC_NOTIFICATION extends ISC_MODULE
	{
		/*
			Should we show a "Test Notification Method" link? Defaults to yes
		*/
		protected $_showtestlink = true;

		/**
		* @var string $type The type of module this is
		*/
		protected $type = 'notification';

		/*
			The height of the popup window to get a shipping quote
		*/
		protected $_height = 150;

		/*
			The id of the order that was just placed
		*/
		protected $_orderid = 0;

		/*
			The total of the order that was just placed
		*/
		protected $_ordertotal = 0;

		/*
			The number of items in the order that was just placed
		*/
		protected $_ordernumitems = 0;

		/*
			Return the height for the popup quote generator window
		*/
		public function GetHeight()
		{
			return $this->_height;
		}

		protected function CheckEnabled()
		{
			$notification_methods = explode(",", GetConfig('NotificationMethods'));
			if(in_array($this->GetId(), $notification_methods)) {
				return true;
			}
			else {
				return false;
			}
		}

		/*
			Return a HTML-formatted list of properties for this notification module
		*/
		public function GetPropertiesSheet($tab_id)
		{
			parent::PreparePropertiesSheet($tab_id, 'ShipperId', 'NotificationJavaScript', 'notification_selected');

			$query = sprintf("select count(variableid) as is_setup from [|PREFIX|]module_vars where modulename='%s' and variablename='is_setup' and variableval='1'", $GLOBALS['ISC_CLASS_DB']->Quote($this->GetId()));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			// Add the test notification link
			if($this->_showtestlink) {
				if($row['is_setup'] > 0) {
					$GLOBALS['PropertyBox'] = sprintf("<a href='javascript:void(0)' onclick='openwin(\"index.php?ToDo=testNotificationMethodSettings&module=%s\", \"%s\", 500, %s)'>%s</a>", $this->GetId(), $this->GetId(), $this->getheight(), GetLang('TestNotificationMethod'));
				}
				else {
					$GLOBALS['PropertyBox'] = sprintf("<a href='javascript:void(0)' onclick='alert(\"%s\")'>%s</a>", GetLang('NotificationProviderNotSetup'), GetLang('TestNotificationMethod'));
				}

				$help_id = rand(1000,100000);
				$GLOBALS['PropertyName'] = "";
				$GLOBALS['Required'] = "";
				$GLOBALS['PanelBottom'] = "PanelBottom";
				$GLOBALS['HelpTip'] = sprintf("<img onmouseout=\"HideHelp('d%d')\" onmouseover=\"ShowHelp('d%d', '%s', '%s')\" src=\"images/help.gif\" width=\"24\" height=\"16\" border=\"0\"><div style=\"display:none\" id=\"d%d\"></div>", $help_id, $help_id, GetLang('TestNotificationMethod'), GetLang('TestNotificationProviderHelp'), $help_id);

				$GLOBALS['Properties'] .= Interspire_Template::getInstance('admin')->render('module.property.tpl');
			}

			return Interspire_Template::getInstance('admin')->render('module.propertysheet.tpl');
		}

		public function SetOrderId($orderid)
		{
			$this->_orderid = $orderid;
		}

		protected function GetOrderId()
		{
			return $this->_orderid;
		}

		public function SetOrderTotal($total)
		{
			$this->_ordertotal = $total;
		}

		protected function GetOrderTotal()
		{
			return $this->_ordertotal;
		}

		public function SetOrderNumItems($numitems)
		{
			$this->_ordernumitems = $numitems;
		}

		protected function GetOrderNumItems()
		{
			return $this->_ordernumitems;
		}

		public function SetOrderPaymentMethod($method)
		{
			$this->_orderpaymentmethod = $method;
		}

		protected function GetOrderPaymentMethod()
		{
			return $this->_orderpaymentmethod;
		}
	}
