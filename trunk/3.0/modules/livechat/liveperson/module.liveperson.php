<?php
/**
 * LivePerson chat integration module for Interspire Shopping Cart.
 */
class LIVECHAT_LIVEPERSON extends ISC_LIVECHAT
{
	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->SetName(GetLang('LivePersonName'));
		$this->SetImage('logo.gif');
	}

	/**
	 * Define the configurable settings for the LivePerson integration.
	 */
	public function SetCustomVars()
	{
		// If the module hasn't been configured before (ie, we've just enabled it) then we show a different
		// set of fields
		$siteId = $this->GetValue('siteid');
		if(empty($this->moduleVariables) || !$this->GetValue('monitortag')) {
			$fieldsVisible = false;
			$this->SetHelpText(GetLang('LivePersonHelp'));
			$this->_variables['action'] = array(
				'name' => 'LivePerson Account',
				'type' => 'custom',
				'callback' => 'ShowRegistrationFields'
			);
		}
		else {
			$infoMsg = GetLang('LivePersonHelpIntegrated');
			$this->SetHelpText(GetLang('LivePersonHelpIntegrated'), 'info');
			$fieldsVisible = true;

			$this->_variables['temp'] = array(
				'name' => GetLang('UpgradeLivePerson'),
				'type' => 'label',
				'label' => "<a id=\"_lpChatBtn\" href='http://base.liveperson.net/hc/5296924/?cmd=file&amp;file=visitorWantsToChat&amp;site=5296924&amp;byhref=1&amp;SESSIONVAR!skill=Sales&amp;VISITORVAR!varid=4970&imageUrl=http://base.liveperson.net/hcp/woman/4/en/' target='chat5296924'  onclick=\"javascript:window.open('http://base.liveperson.net/hc/5296924/?cmd=file&file=visitorWantsToChat&site=5296924&SESSIONVAR!skill=Sales&VISITORVAR!varid=4970&imageUrl=http://base.liveperson.net/hcp/woman/4/en/&referrer='+escape(document.location),'chat5296924','width=475,height=400,resizable=yes');return false;\" >".GetLang('ChatWithLivePerson')."</a> ".GetLang('ChatWithLivePerson2')."</div><!-- END LivePerson Button code -->"
			);
		}

		$visible = false;
		if($siteId) {
			$visible = true;
		}
		$this->_variables['siteid'] = array(
			'name' => GetLang('LivePersonSiteId'),
			'type' => 'textbox',
			'default' => '',
			'visible' => $visible,
			'readonly' => true
		);

		$this->_variables['monitortag'] = array(
			'name' => GetLang('LivePersonMonitorTag'),
			'type' => 'textarea',
			'help' => GetLang('LivePersonMonitorTagHelp'),
			'default' => '',
			'required' => true,
			'visible' => $fieldsVisible
		);

		$this->_variables['buttontag'] = array(
			'name' => GetLang('LivePersonButtonTag'),
			'type' => 'textarea',
			'help' => GetLang('LivePersonButtonTagHelp'),
			'default' => '',
			'required' => true,
			'visible' => $fieldsVisible
		);

		$this->_variables['position'] = array(
			'name' => GetLang('LivePersonPosition'),
			'type' => 'dropdown',
			'help' => GetLang('LivePersonPositionHelp'),
			'default' => 'panel',
			'options' => array(
				GetLang('LivePersonPositionSide') => 'panel',
				GetLang('LivePersonPositionHeader') => 'header'
			),
			'required' => true,
			'visible' => $fieldsVisible
		);
	}

	/**
	 * Get the live chat tracking code for this module for the specified page position.
	 *
	 * @param string The position (header or panel) to fetch the tracking code for. If not the position that's
	 *				 enabled for this module, then this method should return an empty string.
	 * @return string String containing the live chat code.
	 */
	public function GetLiveChatCode($position)
	{
		if($position == 'footer') {
			return $this->GetValue('monitortag');
		}

		if($position == $this->GetValue('position')) {
			return $this->GetValue('buttontag');
		}
	}

	/**
	 * Show the form to register/use an existing Live Person account.
	 */
	public function ShowRegistrationFields()
	{
		return $this->ParseTemplate('liveperson_registration', true);
	}

	public function PerformLivePersonRegistrationAction()
	{
		if(!isset($_REQUEST['site'])) {
			exit;
		}
		$GLOBALS['SiteId'] = $_REQUEST['site'];
		Interspire_Template::getInstance('admin')->display('pageheader.popup.tpl');
		$this->ParseTemplate('liveperson_done');
		Interspire_Template::getInstance('admin')->display('pagefooter.popup.tpl');
		exit;
	}

	public function ShowLivePersonRegistrationAction()
	{
		$user = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetUser();
		$GLOBALS['CurrentUser'] = $user['username'];
		$GLOBALS['CurrentEmail'] = $user['useremail'];

		Interspire_Template::getInstance('admin')->display('pageheader.popup.tpl');
		$this->ParseTemplate('liveperson_form');
		Interspire_Template::getInstance('admin')->display('pagefooter.popup.tpl');
		exit;
	}
}