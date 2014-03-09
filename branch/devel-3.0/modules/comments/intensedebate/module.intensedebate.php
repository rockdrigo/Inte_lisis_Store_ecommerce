<?php
class COMMENTS_INTENSEDEBATE extends ISC_COMMENTS {
	protected $availableCommentTypes = array(self::PRODUCT_COMMENTS, self::NEWS_COMMENTS, self::PAGE_COMMENTS);

	public function __construct()
	{
		parent::__construct();

		$this->SetName(GetLang('IntenseDebateName'));
		$this->SetDescription(GetLang('IntenseDebateDescription'));
		$this->SetHelpText(GetLang('IntenseDebateHelp', array('siteURL' => GetConfig('ShopPathNormal'))));
		$this->SetImage('intensedebate.gif');
	}

	public function SetCustomVars()
	{
		$this->_variables['intensedebatescript'] = array(
			"name" => GetLang('IntenseDebateCode'),
			"type" => "textarea",
			"help" => '',
			"default" => "",
			"required" => true
		);
	}

	public function getCommentsHTMLForType($commentType, $objectReference)
	{
		return $this->GetValue('intensedebatescript');
	}
}