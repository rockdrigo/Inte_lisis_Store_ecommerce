<?php
require_once(ISC_BASE_PATH.'/lib/class.xml.php');
abstract class ISC_ADMIN_REMOTE_BASE extends ISC_XML_PARSER
{
	/**
	 * @var MySQLDb A reference of the database class to work with locally.
	 */
	protected $db = null;

	/**
	 * @var Interspire_Template A reference of the template class to work with locally.
	 */
	protected $template = null;

	/**
	 * @var ISC_ADMIN_AUTH A reference of the authorization class to work with locally.
	 */
	protected $auth = null;

	/**
	 * @var ISC_ADMIN_ENGINE A reference of the processing/engine class to work with locally.
	*/
	protected $engine = null;

	public function __construct()
	{
		/**
		 * Convert the input character set from the hard coded UTF-8 to their
		 * selected character set
		 */
		convertRequestInput();

		$this->template = Interspire_Template::getInstance('admin');
		$this->db = $GLOBALS['ISC_CLASS_DB'];
		$this->auth = getClass('ISC_ADMIN_AUTH');
		$this->engine = getClass('ISC_ADMIN_ENGINE');
		parent::__construct();
	}
}