<?php
class ACCOUNTING_STONEEDGE extends ISC_ACCOUNTING
{
	public function __construct()
	{
		// Setup the required variables for the module
		parent::__construct();
		$this->_id				= 'accounting_stoneedge';
		$this->_name			= GetLang('StoneEdgeName');
		$this->_description		= GetLang('StoneEdgeDesc');
		$this->_help			= sprintf(GetLang('StoneEdgeHelp'),$GLOBALS['ShopPathSSL']);
	}
	/**
	 * Initialise the module
	 *
	 * Method will run the necessary operations for initialising the module. Each accounting module will have this module
	 *
	 * @access protected
	 * @return bool true if is the initialising was asuccessful, FALSE otherwise
	 */
	public function initModule()
	{}
	/**
	 * Custom variables for the checkout module. Custom variables are stored in the following format:
	 * array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
	 * variable_type types are: text,number,password,radio,dropdown
	 * variable_options is used when the variable type is radio or dropdown and is a name/value array.
	 */
	public function SetCustomVars()
	{
		$this->_variables['displayname'] = array(
			"name" => GetLang('StoneEdgeSettings') . "<span style=\"color:#f1f1f1\"><br>"
		 	);
	}
	public function HandleGateway()
	{
		include_once("modules/accounting/stoneedge/xml.php");
	}
}
