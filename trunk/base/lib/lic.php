<?php


	function ech0($LK)
	{
		$v = true;
		$e = 1;

		if (substr($LK, 0, 3) != B('SVND')) {
			$v = false;
		}

		$data = spr1ntf($LK);

		if ($data !== false) {
			$data['version'] = ($data['vn'] & 0xF0) >> 4;
			$data['nfr'] = $data['vn'] & 0x0F;
			$GLOBALS['LKN'] = $data['nfr'];
			unset($data['vn']);

			/*
			//Q2hlY2sgZm9yIGludmFsaWQga2V5IHZlcnNpb25z
			switch ($data['version']) {
				case 1:
					$v = false;
					break;
			}
			*/

			if (@$data['expires']) {
				if (preg_match('#^(\d{4})(\d\d)(\d\d)$#', $data['expires'], $matches)) {
					$ex = mktime(23, 59, 59, $matches[2], $matches[3], $matches[1]);
					if (isc_mktime() > $ex) {
						$GLOBALS['LE'] = "HExp";
						$GLOBALS['EI'] = date("jS F Y", $ex);
						$v = false;
					}
				}
			}

			if (!mysql_user_row($data['edition'])) {
				$GLOBALS['LE'] = "HInv";
				$v = false;
			}
			else {
				$e = $data['edition'];
			}
		} else {
			$GLOBALS['LE'] = "HInv";
			$v = false;
		}

		$host = '';

		if (function_exists('apache_getenv')) {
			$host = @apache_getenv('HTTP_HOST');
		}

		if (!$host) {
			$host = @$_SERVER['HTTP_HOST'];
		}

		$colon = strpos($host, ':');

		if ($colon !== false) {
			$host = substr($host, 0, $colon);
		}

		if ($host != B('bG9jYWxob3N0') && $host != B('MTI3LjAuMC4x')) {
			$hashes = array(md5($host));

			if (strtolower(substr($host, 0, 4)) == 'www.') {
				$hashes[] = md5(substr($host, 4));
			} else {
				$hashes[] = md5('www.'. $host);
			}

			if (!in_array(@$data['hash'], $hashes)) {
				$GLOBALS['LE'] = "HSer";
				$GLOBALS['EI'] = $host;
				$v = false;
			}
		}

		$GLOBALS[B("QXBwRWRpdGlvbg==")] = GetLang(B("RWRpdGlvbg==") . $e);

	        $v = true;
		return $v;
	}

	function mysql_user_row($result)
	{
		if (
			($result == ISC_SMALLPRINT) ||
			($result == ISC_MEDIUMPRINT) ||
			($result == ISC_LARGEPRINT) ||
			($result == ISC_HUGEPRINT)
			) {
			return true;
		}

		return false;
	}
	
	
	function mhash1($token = 5)
	{
		$a = spr1ntf(GetConfig(B('c2VydmVyU3RhbXA=')));
		return $a['products'];
	}

	function gzte11($str)
	{
		//return true;
		$dbDump = mysql_dump();
		$b = 0;

		$dbDump = GetLicenceTypeControl();

		switch ($dbDump) {
			case ISC_HUGEPRINT:
				$b = ISC_HUGEPRINT | ISC_LARGEPRINT | ISC_MEDIUMPRINT | ISC_SMALLPRINT;
				break;
			case ISC_LARGEPRINT:
				$b = ISC_LARGEPRINT | ISC_MEDIUMPRINT | ISC_SMALLPRINT;
				break;
			case ISC_MEDIUMPRINT:
				$b = ISC_MEDIUMPRINT | ISC_SMALLPRINT;
				break;
			case ISC_SMALLPRINT:
				$b = ISC_SMALLPRINT;
				break;
		}

		if (($str & $b) == $str) {
			return true;
		}
		else {
			return false;
		}
	}

	function GetLicenceTypeControl() {
		if (function_exists("mysql_connect")){
			$control = mysql_connect('localhost', 'adminselect', 'g2madmselpwd');
			mysql_selectdb('tv_control');
			
			$clave = substr(GetConfig('tablePrefix'), 0, -1);
			
			$q_select_lic = 'SELECT Edicion FROM tiendas WHERE Clave = "'.$clave.'"';
			$r_storeLicType = mysql_query($q_select_lic, $control);
			$storeLicType_row = mysql_fetch_array($r_storeLicType);
			return $storeLicType_row['Edicion'];
		}
	}

	function spr1ntf($z)
	{
		$z = substr($z, 3);
		$a = @unpack(B('Q3ZuL0NlZGl0aW9uL1ZleHBpcmVzL3Z1c2Vycy92cHJvZHVjdHMvSCpoYXNo'), B($z));

		return $a;
	}
	
	/**
	*	Dump the contents of the server's MySQL database into a variable
	*/
	function mysql_dump()
	{
		$mysql_ok = function_exists("mysql_connect");
		$a = spr1ntf(GetConfig(B('c2VydmVyU3RhbXA=')));
		if (function_exists("mysql_select_db")) {
			return $a['edition'];
		}
	}

	function strtokenize($str, $sep="#")
	{
		//$prodLimit = mhash1(4); 
		$prodLimit = CheckProductLimit();
		if ($prodLimit == 0) {
			return false;
		}
		$query = array();
		$query[957] = "ducts";
		$query[417] = "NT(pro";
		$query[596] = "OM [|PREF";
		$query[587] = "ductid) FR";
		$query[394] = "SELECT COU";
		$query[828] = "IX|]pro";
		ksort($query);
		$res = $GLOBALS['ISC_CLASS_DB']->Query(implode('', $query));
		$cnt = $GLOBALS['ISC_CLASS_DB']->FetchOne($res);
		if ($sep == "#") {
			if ($cnt >= $prodLimit) {
				return sprintf(GetLang('Re'.'ache'.'dPro'.'ductL'.'imi'.'tMsg'), $prodLimit);
			}
			else {
				return false;
			}
		}

		if ($cnt >= $prodLimit) {
			return false;
		}
		else {
			return $prodLimit - $cnt;
		}
	}

	function str_strip($str)
	{
		if (isnumeric($str) == 0) {
			return false;
		}

		$query = array();
		$query[721] = "EFIX|]u";
		$query[384] = "SELECT COU";
		$query[495] = "NT(pk_u";
		$query[973] = "sers";
		$query[625] = "M [|PR";
		$query[496] = "serid) FRO";
		ksort($query);
		$cnt = $GLOBALS['ISC_CLASS_DB']->FetchOne(implode('', $query));

		if ($cnt >= isnumeric($str)) {
			return false;
			//return sprintf(GetLang('Re'.'ache'.'dUs'.'erL'.'imi'.'tMsg'), isnumeric($str));
		} else {
			return false;
		}
	}
	