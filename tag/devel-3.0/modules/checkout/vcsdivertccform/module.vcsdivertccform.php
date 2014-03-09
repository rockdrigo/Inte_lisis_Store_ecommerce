<?php
class CHECKOUT_VCSDIVERTCCFORM extends ISC_CHECKOUT_PROVIDER
{

	/*
		Does this payment provider require SSL?
	*/
	protected $requiresSSL = false;


	/*
	 * Check if this checkout module can be enabled or not.
	 *
	 * @return boolean True if this module is supported on this install, false if not.
	 */
	public function IsSupported()
	{
		$query = "Select currencycode from [|PREFIX|]currencies Where currencyid = '".$GLOBALS['ISC_CLASS_DB']->Quote(GetConfig('DefaultCurrencyID'))."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$currencycode = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

		if ($currencycode != 'ZAR') {
			$this->SetError(GetLang('VCSOnlySupportZAR'));
		}

		if($this->HasErrors()) {
			return false;
		}
		else {
			return true;
		}
	}

	/*
		Checkout class constructor
	*/
	public function __construct()
	{
		// Setup the required variables for the VCSDIVERTCCFORM checkout module
		parent::__construct();
		$this->_name = GetLang('VCSName');
		$this->_image = "virtualterminal.jpg";
		$this->_description = GetLang('VCSDesc');
		$this->_help = sprintf(GetLang('VCSHelp'), $GLOBALS['ShopPathSSL'],$GLOBALS['ShopPathSSL'],$GLOBALS['ShopPathSSL']);
	}

	/**
	* Custom variables for the checkout module. Custom variables are stored in the following format:
	* array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
	* variable_type types are: text,number,password,radio,dropdown
	* variable_options is used when the variable type is radio or dropdown and is a name/value array.
	*/
	public function SetCustomVars()
	{

		$this->_variables['displayname'] = array("name" => GetLang('DisplayName'),
		   "type" => "textbox",
		   "help" => GetLang('DisplayNameHelp'),
		   "default" => $this->GetName(),
		   "required" => true
		);

		$this->_variables['vcsterminalid'] = array("name" => GetLang('VCSTerminalID'),
		   "type" => "textbox",
		   "help" => GetLang('VCSTerminalIDHelp'),
		   "default" => "",
		   "required" => true
		);
	}

	/**
	*	Redirect the customer to VCS's site to enter their payment details
	*/
	public function TransferToProvider()
	{
		$VCSTerminalID = $this->GetValue("vcsterminalid");
		$order = LoadPendingOrderByToken($_COOKIE['SHOP_ORDER_TOKEN']);

		$vcs_url = "https://www.vcs.co.za/vvonline/ccform.asp";
		$amount = $this->gettotal();

		// vcs can't process amount exceeding 10 digits (ISC-1003)
		if ($amount >= 100000.0000) {
			// drop last 2 decimal
			$amount = number_format($amount, 2);
		}

		$hiddenFields = array(
			'p1'	=> $VCSTerminalID,
			'p2'	=> $order['orderid'],
			'p3'	=> getLang('YourOrderFromX', array('storeName' => getConfig('StoreName'))),
			'p4'	=> $amount,
			'p5'	=> 'ZAR',
			'p10'	=> $GLOBALS['ShopPathSSL'].'/finishorder.php',
			'm_1'	=> $this->_calculateSecurityHash($order, $amount),
			'CardholderEmail'	=> isc_html_escape($order['ordbillemail']),
		);
		$this->RedirectToProvider($vcs_url, $hiddenFields);
	}

	/**
	*	Return the unique order token which was saved as a cookie pre-payment
	*/
	public function GetOrderToken()
	{
		return @$_COOKIE['SHOP_ORDER_TOKEN'];
	}

	private function _calculateSecurityHash($order, $amount)
	{
		$amount = number_format($amount, 2);
		return md5($order['ordtoken'].$order['ordipaddress'].$amount.getConfig('EncryptionToken'));
	}

	/**
	*	Verify the order by posting back to VCS.
	*/
	public function VerifyOrder(&$PendingOrder)
	{
		$status = substr(strtolower(trim($_REQUEST['p3'])), 6, 15);
		if($status == 'approved') {

			if (strtolower(trim($_REQUEST['p4']))=='duplicate') {
				$invalidMsg = sprintf(GetLang('VCSDuplicateTransaction'), $PendingOrder['orderid'], $_REQUEST['p3']);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $invalidMsg);
				return false;
			}

			if ($_REQUEST['p2'] != $PendingOrder['orderid']) {
				$invalidMsg = sprintf(GetLang('VCSOrderIDNotMatch'), $PendingOrder['orderid'], $_REQUEST['p2'], $_REQUEST['p3']);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $invalidMsg);
				return false;
			}

			$hash = $this->_calculateSecurityHash($PendingOrder, $_REQUEST['p6']);
			if (!isset($_REQUEST['m_1']) || $hash != $_REQUEST['m_1']) {
				$invalidMsg = sprintf(GetLang('VCSErrorInvalidMsg'), $PendingOrder['orderid'], GetLang('VCSSecurityHashMismatch'));
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $invalidMsg);
				return false;
			}

			$PendingOrder['paymentstatus'] = PAYMENT_STATUS_PAID;
			// if still here, transaction is successful
			$successPaymentMsg = sprintf(GetLang('VCSSuccess'), $PendingOrder['orderid'], $_REQUEST['p3']);
			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), $successPaymentMsg);
			return true;
		} else {
			$invalidMsg = sprintf(GetLang('VCSErrorInvalidMsg'), $PendingOrder['orderid'], $_REQUEST['p3']);
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $invalidMsg);
			return false;
		}
	}
}