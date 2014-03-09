<?php

class ISC_SESSION
{

	private $_token = null;
	private $_data = null;

	public function __construct($sessionId="")
	{
		session_set_save_handler(array(&$this, "_SessionOpen"),
			array(&$this, "_SessionClose"),
			array(&$this, "_SessionRead"),
			array(&$this, "_SessionWrite"),
			array(&$this, "_SessionDestroy"),
			array(&$this, "_SessionGC")
		);

		@ini_set('url_rewriter.tags', '');
		@ini_set('session.gc_probability', 1);
		@ini_set('session.gc_divisor', 100);

		@ini_set('session.gc_maxlifetime', 604800); // 7 days
		@ini_set('session.referer_check', '');

		if (defined('FORCE_SESSION_COOKIE')) {
			@ini_set('session.use_cookies', 1);
		} else {
			@ini_set('session.use_cookies', 0);
		}

		// For PHP versions >= 5.2.0, mark session cookies as HttpOnly.
		if (version_compare(PHP_VERSION, '5.2.0') >= 0) {
			$cookieLifetime = (int)ini_get('session.cookie_lifetime');
			session_set_cookie_params($cookieLifetime, null, null, null, true);
		}

		if(defined('NO_SESSION')) {
			return;
		}

		if($sessionId != '') {
			session_id($sessionId);
		}
		else if(isset($_GET['tk']) && !empty($_GET['tk'])) {
			session_id($_GET['tk']);
			ISC_SetCookie("SHOP_SESSION_TOKEN", session_id(), time()+((int)@ini_get('session.gc_maxlifetime')), true);
		}
		else if(isset($_COOKIE['SHOP_SESSION_TOKEN'])) {
			session_id($_COOKIE['SHOP_SESSION_TOKEN']);
		}

		session_start();
	}

	public function LoadSessionByToken($token)
	{
		$query = "
			SELECT sessdata
			FROM [|PREFIX|]sessions
			WHERE sessionhash='".$GLOBALS['ISC_CLASS_DB']->quote($token)."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$session = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		return @session_decode($session['sessdata']);
	}

	public function CreateSession()
	{
		$this->_token = session_id();
		$newSession = array(
			"sessionhash" => $this->_token,
			"sessdata" => "",
			"sesslastupdated" => time()
		);
		$GLOBALS['ISC_CLASS_DB']->InsertQuery("sessions", $newSession);
		$this->_new_session = true;
		$this->_data = array();

		ISC_SetCookie("SHOP_SESSION_TOKEN", $this->_token, time()+((int)@ini_get('session.gc_maxlifetime')), true);
	}

	public function _SessionOpen()
	{
		return true;
	}

	public function _SessionClose()
	{
		return true;
	}

	public function _SessionRead($token)
	{
		$this->_token = $GLOBALS['ISC_CLASS_DB']->Quote($token);
		$query = sprintf("SELECT sessdata FROM [|PREFIX|]sessions WHERE sessionhash='%s'", $GLOBALS['ISC_CLASS_DB']->Quote($this->_token));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$session = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if(!empty($session)) {
			return $session['sessdata'];
		}
		else {
			$this->CreateSession();
		}
	}

	public function _SessionWrite($token, $data)
	{
		$updatedSession = array(
			"sessdata" => $data,
			"sesslastupdated" => time()
		);
		return $GLOBALS['ISC_CLASS_DB']->updateQuery('sessions', $updatedSession, "sessionhash='".$GLOBALS['ISC_CLASS_DB']->quote($token)."'");
	}

	public function _SessionDestroy($token)
	{
		$result = $GLOBALS['ISC_CLASS_DB']->deleteQuery('sessions', "WHERE sessionhash='".$GLOBALS['ISC_CLASS_DB']->quote($token)."'");
		ISC_UnsetCookie("SHOP_SESSION_TOKEN");
		$this->_OnSessionEnd($token);
		return $result;
	}

	public function _SessionGC($max)
	{
		$db = $GLOBALS['ISC_CLASS_DB'];
		$cutoff = time() - $max;

		// find the records that will be deleted so a session end can be triggered
		$query = "SELECT sessionhash FROM `[|PREFIX|]sessions` WHERE sesslastupdated < " . $cutoff;
		$result = $db->Query($query);
		if ($result) {
			while ($row = $db->Fetch($result)) {
				$this->_OnSessionEnd($row['sessionhash']);
			}
		}

		// delete the records
		$query = "DELETE FROM `[|PREFIX|]sessions` WHERE sesslastupdated < " . $cutoff;
		return $db->Query($query);
	}

	protected function _OnSessionEnd($sessionHash)
	{
		ISC_PRODUCT_VIEWS::onSessionEnd($sessionHash);
	}
}
