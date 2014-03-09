<?php

class Interspire_EmailIntegration_Subscription_Newsletter extends Interspire_EmailIntegration_Subscription
{
	public $subemail;
	public $subfirstname;

	public function __construct($subemail, $subfirstname)
	{
		$this->setDoubleOptIn(GetConfig('EmailIntegrationNewsletterDoubleOptin'));
		$this->setSendWelcome(GetConfig('EmailIntegrationNewsletterSendWelcome'));
		$this->setSubscriptionIP(GetIP());

		$this->subemail = $subemail;
		$this->subfirstname = $subfirstname;
	}

	public function getSubscriptionEventId()
	{
		return 'onNewsletterSubscribed';
	}

	public function getSubscriptionTypeLang()
	{
		return GetLang('EmailIntegration_Subscription_Newsletter');
	}

	public function getSubscriptionFields()
	{
		// can't rely on ISC_ADMIN_ENGINE or admin lang stuff from here because this code may be run by the task manager
		$languagePath = ISC_BASE_PATH . '/language/' . GetConfig('Language') . '/admin';
		ParseLangFile($languagePath . '/common.ini');
		ParseLangFile($languagePath . '/settings.emailintegration.ini');

		$groups = array();

		$groups[GetLang('NewsletterFields')] = array(
			'subemail' => new Interspire_EmailIntegration_Field_Email('subemail', GetLang('EmailAddress')),
			'subfirstname' => new Interspire_EmailIntegration_Field_String('subfirstname', GetLang('FirstName')),
		);

		return $groups;
	}

	public function getSubscriptionEmailField()
	{
		return 'subemail';
	}

	public function getSubscriptionEmail()
	{
		return $this->subemail;
	}

	public function getSubscriptionData()
	{
		return array(
			'subemail' => $this->subemail,
			'subfirstname' => $this->subfirstname,
		);
	}
}
