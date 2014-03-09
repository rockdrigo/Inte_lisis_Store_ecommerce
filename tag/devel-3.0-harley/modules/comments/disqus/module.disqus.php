<?php
class COMMENTS_DISQUS extends ISC_COMMENTS {
	protected $availableCommentTypes = array(self::PRODUCT_COMMENTS, self::NEWS_COMMENTS, self::PAGE_COMMENTS);

	public function __construct()
	{
		parent::__construct();

		$this->SetName(GetLang('DisqusName'));
		$this->SetDescription(GetLang('DisqusDescription'));
		$this->SetHelpText(GetLang('DisqusHelp', array('siteURL' => GetConfig('ShopPathNormal'))));
		$this->SetImage('disqus.gif');
	}

	public function SetCustomVars()
	{
		$this->_variables['disqusscript'] = array(
			"name" => GetLang('DisqusCode'),
			"type" => "textarea",
			"help" => '',
			"default" => "",
			"required" => true
		);
	}

	public function getCommentsHTMLForType($commentType, $objectReference)
	{
		return $this->GetValue('disqusscript');
	}
}