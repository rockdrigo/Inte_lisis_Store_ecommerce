<?php
abstract class ISC_ADMIN_BASE
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

	/**
	* @var ISC_LOG A reference to the logging system
	*/
	protected $log = null;

	public function __construct()
	{
		if(defined('ISC_ADMIN_CP')) {
			$this->template = Interspire_Template::getInstance('admin');
			$this->auth = getClass('ISC_ADMIN_AUTH');
			$this->engine = getClass('ISC_ADMIN_ENGINE');
		}

		$this->db = $GLOBALS['ISC_CLASS_DB'];

		if (isset($GLOBALS['ISC_CLASS_LOG'])) {
			// the logging global isn't available during installation
			$this->log = $GLOBALS['ISC_CLASS_LOG'];
		}
	}
}
