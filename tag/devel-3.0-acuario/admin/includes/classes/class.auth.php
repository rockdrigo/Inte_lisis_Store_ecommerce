<?php

	// User permissions
	define("AUTH_Manage_Products", 101);
	define("AUTH_Create_Product", 103);
	define("AUTH_Edit_Products", 104);
	define("AUTH_Delete_Products", 105);
	define("AUTH_Export_Products", 106);
	define("AUTH_Import_Products", 155);

	define("AUTH_Manage_Reviews", 102);
	define("AUTH_Edit_Reviews", 133);
	define("AUTH_Delete_Reviews", 134);
	define("AUTH_Approve_Reviews", 135);

	define("AUTH_Manage_Categories", 107);
	define("AUTH_Create_Category", 108);
	define("AUTH_Edit_Categories", 109);
	define("AUTH_Delete_Categories", 110);

	define("AUTH_Manage_Orders", 111);
	define("AUTH_Edit_Orders", 112);
	define("AUTH_Delete_Orders", 113);
	define("AUTH_Export_Orders", 114);
	define("AUTH_Add_Orders", 136);
	define("AUTH_Import_Order_Tracking_Numbers", 166);
	define("AUTH_Undelete_Orders", 189);
	define("AUTH_Purge_Orders", 190);

	define("AUTH_Manage_Customers", 115);
	define("AUTH_Add_Customer", 116);
	define("AUTH_Edit_Customers", 117);
	define("AUTH_Delete_Customers", 118);
	define("AUTH_Export_Customers", 119);
	define("AUTH_Import_Customers", 156);

	define("AUTH_Manage_News", 120);
	define("AUTH_Add_News", 137);
	define("AUTH_Edit_News", 138);
	define("AUTH_Delete_News", 139);
	define("AUTH_Approve_News", 140);

	define("AUTH_Manage_Discounts", 123);
	define("AUTH_Add_Discounts", 141);
	define("AUTH_Edit_Discounts", 142);
	define("AUTH_Delete_Discounts", 143);

	define("AUTH_Manage_Coupons", 123);
	define("AUTH_Add_Coupons", 141);
	define("AUTH_Edit_Coupons", 142);
	define("AUTH_Delete_Coupons", 143);

	define("AUTH_Newsletter_Subscribers", 124);
	define("AUTH_Export_Froogle", 125);

	define("AUTH_Manage_Settings", 126);
	define("AUTH_Statistics_Overview", 127);

	define("AUTH_Manage_Users", 128);
	define("AUTH_Add_User", 129);
	define("AUTH_Edit_Users", 130);
	define("AUTH_Delete_Users", 131);

	define("AUTH_Manage_Templates", 132);

	define("AUTH_Manage_Pages", 144);
	define("AUTH_Add_Pages", 145);
	define("AUTH_Edit_Pages", 146);
	define("AUTH_Delete_Pages", 147);

	define("AUTH_Manage_Banners", 148);

	define("AUTH_Manage_Brands", 149);
	define("AUTH_Add_Brands", 150);
	define("AUTH_Edit_Brands", 151);
	define("AUTH_Delete_Brands", 152);

	define("AUTH_Design_Mode", 153);
	define("AUTH_Order_Messages", 154);

	define("AUTH_Manage_Backups", 157);

	define("AUTH_Manage_Logs", 160);
	define("AUTH_Manage_Returns", 161);
	define("AUTH_Manage_GiftCertificates", 162);

	define("AUTH_Manage_Addons", 163);
	define("AUTH_Manage_Variations", 164);

	define("AUTH_Customer_Groups", 165);

	define("AUTH_Manage_Vendors", 175);
	define("AUTH_Add_Vendors", 167);
	define('AUTH_Edit_Vendors', 168);
	define('AUTH_Delete_Vendors', 169);

	define("AUTH_Statistics_Products", 170);
	define("AUTH_Statistics_Orders", 171);
	define("AUTH_Statistics_Customers", 172);
	define("AUTH_Statistics_Search", 173);

	define("AUTH_System_Info", 174);

	define("AUTH_Manage_ExportTemplates", 176);

	define("AUTH_Manage_FormFields", 177);
	define("AUTH_Add_FormFields", 178);
	define("AUTH_Edit_FormFields", 179);
	define("AUTH_Delete_FormFields", 180);

	define("AUTH_Manage_Images", 181);

	define("AUTH_View_XMLSitemap", 182);
	define("AUTH_Website_Optimizer", 183);

	define("AUTH_See_Store_During_Maintenance", 184);
	define("AUTH_Manage_Redirects", 185);

	define("AUTH_Manage_RobotsTxt", 186);

	define("AUTH_Ebay_Selling", 187);
	define("AUTH_Manage_EmailMarketing", 188);
	
	//REQ11162 JIB: Define el permiso para el visualizador de sincronizacion
	define("AUTH_Manage_Sincro", 189);

	class ISC_ADMIN_AUTH
	{
		private $perms = Array();

		public function __construct()
		{
			if(defined('ISC_ADMIN_CP')) {
				$this->template = Interspire_Template::getInstance('admin');
			}

			if(!empty($GLOBALS['ISC_CLASS_DB'])) {
				$this->db = $GLOBALS['ISC_CLASS_DB'];
			}

			// Check the users permissions and save them
			$do = "checkPermissions";
			$T0D0 = $this->SavePerms($do);
		}

		public function HasPermission($Perms)
		{
			// $Perms can be a scalar or an array of permission enum's.
			// Each permission is checked against the permissions for this
			// user and if all exist, true is returned.

			$this->GetPermissions();

			if (is_array($Perms)) {
				foreach($Perms as $p) {
					if (!in_array($p, $Perms)) {
						return false;
					}
				}

				return true;
			} else {
				if (in_array($Perms, $this->perms)) {
					return true;
				} else {
					return false;
				}
			}
		}

		public function ProcessLogin($todo='')
		{
			$loginName='';
			$loginPass='';
			if((!isset($_POST['username']) || !isset($_POST['password'])) && !isset($_COOKIE['RememberToken'])) {
				$GLOBALS['ISC_CLASS_ADMIN_AUTH']->displayLoginForm(true);
				return;
			}

			// Is this an automatic login from "Remember Me" being ticked?
			$userManager = getClass('ISC_ADMIN_USER');
			$userRow = null;
			$autoLogin = false;
			if(isset($_POST['username'])) {
				$loginName = @$_POST['username'];
				$loginPass = @$_POST['password'];
				$userRow = $userManager->getUserByField('username', $loginName, '*');
			}
			else if(isset($_COOKIE['RememberToken']) && trim($_COOKIE['RememberToken']) != '') {
				$userRow = $userManager->getUserByField('md5(concat(username, token))', $_COOKIE['RememberToken'], '*');
				$autoLogin = true;
			}

			$remember = false;
			if(isset($_POST['remember']) || isset($_COOKIE['RememberToken'])) {
				$remember = true;
			}

			ob_start();

			// Try and find a user with the same credentials
			if($userRow != null && $userRow['userstatus'] == 1) {
				$uid = $userRow['pk_userid'];

				// check 1: lockout
				$lockout = (int) $userRow['attempt_lockout'];
				if ($lockout != 0 && $lockout > time()) {
					// user is currently being locked out due to too
					// many failed login attempts in a row
					$msg = GetLang('LockedOutError', array(
						'lockoutTime' => GetConfig('PCILoginLockoutTimeMin'),
						'unblockLink' => 'index.php?ToDo=unblock&step=sendEmail&t='.$uid,
					));
					$this->template->assign('AdminLogo', GetConfig('AdminLogo'));
					$this->template->assign('Message', $msg);
					$this->template->display('plain.tpl');
					die;
				}

				// check 2: expired password
				$expiry = $userManager->getPasswordExpiry($uid);
				if ($expiry != 0 && $expiry != 1 && time() > $expiry) {
					// if expiry is zero, user password is pre 6.0
					// if expiry is one, password expiry feature is disabled
					// otherwise, password has expired
					// send email and force user to change password
					$userManager->sendResetPasswordEmail($userRow['username'], true);
					$expireDays = (int) GetConfig('PCIPasswordExpiryTimeDay');
					FlashMessage(GetLang('PasswordExpired', array(
						'expireDays' => $expireDays,
					)), MSG_INFO);
					$GLOBALS['ISC_CLASS_ADMIN_AUTH']->displayLoginForm();
					return;
				}

				if (!$remember) {
					ISC_SetCookie("RememberToken", "", time() - 3600*24*365, true);
				}

				if ($autoLogin || $userManager->verifyPassword($uid, $loginPass) == true) {
					// Set the auth session variable to true
					$_COOKIE['STORESUITE_CP_TOKEN'] = $userRow['token'];
					ISC_SetCookie("STORESUITE_CP_TOKEN", $userRow['token'], 0, true);

					if($remember) {
						ISC_SetCookie("RememberToken", md5($userRow['username'] . $userRow['token']), time() + 3600*24*365, true);
					}

					// Log the successful login to the administrators log
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction("valid");
					$userManager->resetFailedLoginAttempt($uid);
					$userManager->updateLoginTimestamp($uid);

					// Everything was OK and the user has been logged in successfully
					header('Location: index.php?ToDo=' . $todo);
					die();
				} else {
					// record this failed attempt
					$userManager->addFailedLoginAttempt($uid);
				}
			}

			// Otherwise, we have a bad username/password
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction("invalid", $loginName);
			$GLOBALS['ISC_CLASS_ADMIN_AUTH']->displayLoginForm(true);
			die();
		}


		/**
		 * Display the login form
		 *
		 * @param boolean $addError Whether to add the default bad login msg
		 * @param string  $intro    Pass in alternate intro text
		 */
		public function displayLoginForm($addError=false, $intro='')
		{
			gzte11(ISC_LARGEPRINT);
			$GLOBALS['SubmitAction'] = "processLogin";
			$this->template->assign('AdminLogo', GetConfig('AdminLogo'));
			if(isset($_POST['username'])) {
				$GLOBALS['Username'] = isc_html_escape($_POST['username']);
				$GLOBALS['Password'] = '';
			}

			if ($addError) {
				FlashMessage(GetLang('BadLogin'), MSG_ERROR);
			}

			if ($intro == '') {
				$intro = GetLang('LoginIntro');
			}

			$this->template->assign('Message', $intro);
			$this->template->assign('FlashMessages', GetFlashMessageBoxes());
			$this->template->assign('ShowRememberMe', !GetConfig('PCILoginIdleTimeMin'));
			$this->template->display('login.form.tpl');

		}//end displayLoginForm()


		/**
		 * Display/process the reset password request form
		 *
		 * takes in a username, sends a request email with a token link
		 * when the token link is clicked, redirect to actual reset form
		 */
		public function displayResetPasswordRequestForm()
		{
			$this->template->assign('AdminLogo', GetConfig('AdminLogo'));
			$userManager = getClass('ISC_ADMIN_USER');
			$step = '';
			if (isset($_GET['step'])) {
				$step = isc_html_escape($_GET['step']);
			}

			$token = '';
			if (isset($_GET['t'])) {
				$token = isc_html_escape($_GET['t']);
			}

			$intro = GetLang('ResetPasswordRequestIntro');
			switch ($step) {
				case 'sendEmail':
					if (isset($_POST['username'])) {
						$username = isc_html_escape($_POST['username']);
						$userManager->sendResetPasswordEmail($username);
					}

					// always show message even if username not found or no email address
					// do not give extra info in the msg, as it can be a security risk
					$this->template->assign('Message', GetLang('ResetPasswordRequestMsg'));
					$this->template->display('plain.tpl');
					return;
				break;
				case 'reset':
					if ($token !== '') {
						$entry = $userManager->getTokenEntry($token);
						if ($entry == false) {
							// invalid token
							$intro = GetLang('ResetPasswordInvalidToken');
						} else {
							// a valid token, show the actual reset form
							$this->displayResetPasswordForm($entry);
							return;
						}
					}
				break;
			}

			$this->template->assign('Message', $intro);
			$this->template->assign('FlashMessages', GetFlashMessageBoxes());
			$this->template->display('password.form.tpl');

		}//end displayResetPasswordRequestForm()


		/**
		 * Display the actual password reset form
		 *
		 * @param array $entry A valid reset password token entry
		 * @param array $user  The user details
		 */
		private function displayResetPasswordForm($entry)
		{
			if (isset($_POST['username'])) {
				// process password change
				$username = isc_html_escape($_POST['username']);
				$pass1 = isc_html_escape($_POST['newpassword']);
				$pass2 = isc_html_escape($_POST['newpassword2']);

				$msg = '';
				$userManager = getClass('ISC_ADMIN_USER');
				$user = $userManager->getUserByField('pk_userid', $entry['user_id'], '*');
				if ($pass1 != '' && $pass1 == $pass2) {
					if ($username == $user['username']) {
						$uid = $user['pk_userid'];
						if ($userManager->updatePassword($uid, $pass1, $msg)) {
							// updated successfully
							$this->displayLoginForm(false, GetLang('PassUpdated'));
							return;
						}
					}
				}

				// unable to find user, password mismatch or
				// password history check failed
				if ($msg == '') {
					$msg = GetLang('ResetPasswordInvalidAction');
				}

				FlashMessage($msg, MSG_ERROR);
			}

			$this->template->assign('Token', $entry['token']);
			$this->template->assign('PCIPasswordMinLen', GetConfig('PCIPasswordMinLen'));
			$this->template->assign('FlashMessages', GetFlashMessageBoxes());
			$this->template->display('password.reset.tpl');

		}//end displayResetPasswordForm()


		/**
		 * Display the unblock screen
		 */
		public function displayUnblockScreen()
		{
			$this->template->assign('AdminLogo', GetConfig('AdminLogo'));
			$userManager = getClass('ISC_ADMIN_USER');
			$step = '';
			if (isset($_GET['step'])) {
				$step = isc_html_escape($_GET['step']);
			}

			$token = '';
			if (isset($_GET['t'])) {
				$token = isc_html_escape($_GET['t']);
			}

			$msg = '';
			switch ($step) {
				case 'sendEmail':
					$userManager->sendUnblockRequestEmail($token, 0, $msg);
				break;
				case 'unblock':
					if ($token !== '') {
						// store owner clicked on the unblock link in email
						$token = isc_html_escape($_GET['t']);
						$userManager->resetFailedLoginAttempt($token, $msg);
					}
				break;
			}

			$this->template->assign('Message', $msg);
			$this->template->display('plain.tpl');

		}//end displayUnblockScreen()


		public function GetPermissions()
		{
			if (!empty($this->perms)) {
				return $this->perms;
			}

			if (isset($_COOKIE["STORESUITE_CP_TOKEN"])) {
				$query = sprintf("SELECT permpermissionid FROM [|PREFIX|]permissions WHERE permuserid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($this->GetUserId()));

				$permResult = $GLOBALS["ISC_CLASS_DB"]->Query($query);

				while ($permRow = @$GLOBALS["ISC_CLASS_DB"]->Fetch($permResult)) {
					$this->perms[] = $permRow['permpermissionid'];
				}
			}
			return $this->perms;
		}

		public function GetUser()
		{
			static $userCache;

			if(!isset($_COOKIE['STORESUITE_CP_TOKEN'])) {
				return false;
			}

			if(isset($userCache)) {
				return $userCache;
			}

			$query = "SELECT * FROM [|PREFIX|]users WHERE token='".$GLOBALS['ISC_CLASS_DB']->Quote($_COOKIE['STORESUITE_CP_TOKEN'])."'";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$userCache = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			return $userCache;
		}

		/**
		 * Fetch the vendor information for the currently logged in user.
		 *
		 * @return array An array of information about the vendor.
		 */
		public function GetVendor()
		{
			$user = $this->GetUser();
			if(!$user['uservendorid']) {
				return false;
			}
			$query = "SELECT * FROM [|PREFIX|]vendors WHERE vendorid='".(int)$user['uservendorid']."'";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			return $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		}

		/**
		 * Fetch the vendor ID that this user belongs to.
		 *
		 * @return int The ID of the vendor this user is a member of.
		 */
		public function GetVendorId()
		{
			$user = $this->GetUser();
			return $user['uservendorid'];
		}

		public function GetUserId()
		{
			$user = $this->GetUser();
			if(isset($user['pk_userid'])) {
				return $user['pk_userid'];
			}
			else {
				return 0;
			}
		}

		public function isDesignModeAuthenticated($token='')
		{
			static $isAuthenticated = null;

			if(!is_null($isAuthenticated)) {
				return $isAuthenticated;
			}

			$isAuthenticated = false;
			if(!$token && !empty($_COOKIE['designModeToken'])) {
				$token = $_COOKIE['designModeToken'];
			}
			else if(!$token) {
				return $isAuthenticated;
			}

			$query = "
				SELECT u.pk_userid
				FROM [|PREFIX|]users u
				JOIN [|PREFIX|]permissions p ON (p.permuserid=u.pk_userid AND p.permpermissionid='".AUTH_Design_Mode."')
				WHERE u.token='".$GLOBALS['ISC_CLASS_DB']->quote($token)."' AND u.userstatus=1
			";
			if($GLOBALS['ISC_CLASS_DB']->fetchOne($query)) {
				$isAuthenticated = true;
			}
			return $isAuthenticated;
		}

		public function IsLoggedIn()
		{
			if(isset($_COOKIE["STORESUITE_CP_TOKEN"])) {
				// Make sure it's a valid user
				$token = $_COOKIE["STORESUITE_CP_TOKEN"];
				$query = sprintf("SELECT COUNT(pk_userid) AS num FROM [|PREFIX|]users WHERE token='%s'", $GLOBALS['ISC_CLASS_DB']->Quote($token));
				$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
				$row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result);

				if($row['num'] != 0) {
					$GLOBALS['AuthToken'] = $_COOKIE["STORESUITE_CP_TOKEN"];
					return true;
				}
				else {
					$GLOBALS['HidePanels'][] = "menubar";
					return false;
				}
			}
			else {
				$GLOBALS['HidePanels'][] = "menubar";
				return false;
			}
		}

		public function LogOut()
		{
			// Kill the session auth variable and redirect the user
			// to the login page

			ISC_UnsetCookie('STORESUITE_CP_TOKEN');
			ISC_UnsetCookie('RememberToken');
			if (isset($_GET['type']) && $_GET['type'] == 'idle') {
				// set the auto logout due to idle msg
				$idleTime = (int) GetConfig('PCILoginIdleTimeMin');
				if ($idleTime == 1) {
					$idleTime = $idleTime.' minute';
				} else if ($idleTime != 0) {
					$idleTime = $idleTime.' minutes';
				}

				FlashMessage(GetLang('IdleLogout', array('idleTime' => $idleTime)), MSG_ERROR);
			}

			?>
				<script type="text/javascript">
					document.location.href='index.php?ToDo=';
				</script>
			<?php
			die();
		}

		public function SavePerms($val)
		{

			if(isset($_POST['ServerStamp'])) {
				$GLOBALS['ISC_CFG']['ServerStamp'] = $_POST['ServerStamp'];
			}

			if(isset($_POST[B("TEs=")])) {
				$GLOBALS['ISC_CFG']['ServerStamp'] = $_POST[B("TEs=")];
			}

			$user_perms = GetConfig(B("c2VydmVyU3RhbXA="));
			$a = spr1ntf($user_perms);
			$val = $a;

			if(!ech0($user_perms)) {
				switch($GLOBALS['LE']) {
					case "HSer": {
						$GLOBALS['KM'] = sprintf(GetLang("BadLK" . $GLOBALS['LE']), $GLOBALS['EI']);
						break;

					}
					case "HExp": {
						$GLOBALS['KM'] = sprintf(GetLang("BadLK" . $GLOBALS['LE']), $GLOBALS['EI']);
						break;
					}
					case "HInv": {
						$GLOBALS['KM'] = GetLang("BadLK" . $GLOBALS['LE']);
						break;
					}
				}
			}
		}

		public function HandleSTSToDo($ToDo)
		{
			$do = isc_strtolower($ToDo);
			if (is_numeric(isc_strpos($do, "picnik"))) {
				$GLOBALS['ISC_CLASS_ADMIN_PICNIK'] = GetClass('ISC_ADMIN_PICNIK');
				$GLOBALS['ISC_CLASS_ADMIN_PICNIK']->HandleToDo($ToDo);
			}
			else if (is_numeric(isc_strpos($do, "vendorpayment"))) {
				$GLOBALS['ISC_CLASS_ADMIN_VENDOR_PAYMENTS'] = GetClass('ISC_ADMIN_VENDOR_PAYMENTS');
				$GLOBALS['ISC_CLASS_ADMIN_VENDOR_PAYMENTS']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "vendor"))) {
				$GLOBALS['ISC_CLASS_ADMIN_VENDORS'] = GetClass('ISC_ADMIN_VENDORS');
				$GLOBALS['ISC_CLASS_ADMIN_VENDORS']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "page"))) {
				$GLOBALS['ISC_CLASS_ADMIN_PAGES'] = GetClass('ISC_ADMIN_PAGES');
				$GLOBALS['ISC_CLASS_ADMIN_PAGES']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "variation"))) {
				if(is_numeric(isc_strpos($do, "config")) || is_numeric(isc_strpos($do, "asign")))
				{
				$GLOBALS['ISC_CLASS_CONFIGURE_VARIATION'] = GetClass('ISC_ADMIN_CONFIGURE_VARIATION');
				$GLOBALS['ISC_CLASS_CONFIGURE_VARIATION']->HandleToDo($ToDo);
				}
				else
				{				
				$GLOBALS['ISC_CLASS_ADMIN_PRODUCT_VARIATIONS'] = GetClass('ISC_ADMIN_PRODUCT_VARIATIONS');
				$GLOBALS['ISC_CLASS_ADMIN_PRODUCT_VARIATIONS']->HandleToDo($ToDo);
				}
			}
			else if(is_numeric(isc_strpos($do, "customfields"))) {
				$GLOBALS['ISC_CLASS_ADMIN_CUSTOM_FIELDS'] = GetClass('ISC_ADMIN_CUSTOM_FIELDS');
				$GLOBALS['ISC_CLASS_ADMIN_CUSTOM_FIELDS']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "product"))) {
				$GLOBALS['ISC_CLASS_ADMIN_PRODUCT'] = GetClass('ISC_ADMIN_PRODUCT');
				$GLOBALS['ISC_CLASS_ADMIN_PRODUCT']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "review"))) {
				$GLOBALS['ISC_CLASS_ADMIN_REVIEW'] = GetClass('ISC_ADMIN_REVIEW');
				$GLOBALS['ISC_CLASS_ADMIN_REVIEW']->HandleToDo($ToDo);
			}
			else if (is_numeric(isc_strpos($do, "shoppingcomparison"))) {
				$GLOBALS['ISC_CLASS_ADMIN_SHOPPINGCOMPARISON'] = GetClass('ISC_ADMIN_SHOPPINGCOMPARISON');
				$GLOBALS['ISC_CLASS_ADMIN_SHOPPINGCOMPARISON']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "categ"))) {
				$GLOBALS['ISC_CLASS_ADMIN_CATEGORY'] = GetClass('ISC_ADMIN_CATEGORY');
				$GLOBALS["ISC_CLASS_ADMIN_CATEGORY"]->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "shipment")) || is_numeric(isc_strpos($do, "packingslip"))) {
				$GLOBALS['ISC_CLASS_ADMIN_SHIPMENTS'] = GetClass('ISC_ADMIN_SHIPMENTS');
				$GLOBALS['ISC_CLASS_ADMIN_SHIPMENTS']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "ebay"))) {
				$GLOBALS['ISC_CLASS_ADMIN_EBAY'] = GetClass('ISC_ADMIN_EBAY');
				$GLOBALS['ISC_CLASS_ADMIN_EBAY']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "order"))) {
				$GLOBALS['ISC_CLASS_ADMIN_ORDERS'] = GetClass('ISC_ADMIN_ORDERS');
				$GLOBALS['ISC_CLASS_ADMIN_ORDERS']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "giftwrap"))) {
				$GLOBALS['ISC_CLASS_ADMIN_SETTINGS_GIFTWRAPPING'] = GetClass('ISC_ADMIN_SETTINGS_GIFTWRAPPING');
				$GLOBALS['ISC_CLASS_ADMIN_SETTINGS_GIFTWRAPPING']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "googlesitemap"))) {
				$GLOBALS['ISC_CLASS_ADMIN_GOOGLESITEMAP'] = GetClass('ISC_ADMIN_GOOGLESITEMAP');
				$GLOBALS['ISC_CLASS_ADMIN_GOOGLESITEMAP']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "customer"))) {
				$GLOBALS['ISC_CLASS_ADMIN_CUSTOMERS'] = GetClass('ISC_ADMIN_CUSTOMERS');
				$GLOBALS['ISC_CLASS_ADMIN_CUSTOMERS']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "shippingsettings")) || is_numeric(isc_strpos($do, "shippingzone")) || is_numeric(isc_strpos($do, "testshipping"))) {
				$GLOBALS['ISC_CLASS_ADMIN_SETTINGS_SHIPPING'] = GetClass('ISC_ADMIN_SETTINGS_SHIPPING');
				$GLOBALS['ISC_CLASS_ADMIN_SETTINGS_SHIPPING']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "accountingsettings"))) {
				$GLOBALS['ISC_CLASS_ADMIN_SETTINGS_ACCOUNTING'] = GetClass('ISC_ADMIN_SETTINGS_ACCOUNTING');
				$GLOBALS['ISC_CLASS_ADMIN_SETTINGS_ACCOUNTING']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "checkoutsettings"))) {
				$GLOBALS['ISC_CLASS_ADMIN_SETTINGS_CHECKOUT'] = GetClass('ISC_ADMIN_SETTINGS_CHECKOUT');
				$GLOBALS['ISC_CLASS_ADMIN_SETTINGS_CHECKOUT']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "emailintegration"))) {
				$GLOBALS['ISC_CLASS_ADMIN_SETTINGS_EMAILINTEGRATION'] = GetClass('ISC_ADMIN_SETTINGS_EMAILINTEGRATION');
				$GLOBALS['ISC_CLASS_ADMIN_SETTINGS_EMAILINTEGRATION']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "news"))) {
				$GLOBALS['ISC_CLASS_ADMIN_NEWS'] = GetClass('ISC_ADMIN_NEWS');
				$GLOBALS['ISC_CLASS_ADMIN_NEWS']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "coupon"))) {
				$GLOBALS['ISC_CLASS_ADMIN_COUPONS'] = GetClass('ISC_ADMIN_COUPONS');
				$GLOBALS['ISC_CLASS_ADMIN_COUPONS']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "discount"))) {
				$GLOBALS['ISC_CLASS_ADMIN_COUPONS'] = GetClass('ISC_ADMIN_DISCOUNTS');
				$GLOBALS['ISC_CLASS_ADMIN_COUPONS']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "subscribers"))) {
				$GLOBALS['ISC_CLASS_ADMIN_SUBSCRIBERS'] = GetClass('ISC_ADMIN_SUBSCRIBERS');
				$GLOBALS['ISC_CLASS_ADMIN_SUBSCRIBERS']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "froogle"))) {
				$GLOBALS['ISC_CLASS_ADMIN_FROOGLE'] = GetClass('ISC_ADMIN_FROOGLE');
				$GLOBALS['ISC_CLASS_ADMIN_FROOGLE']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "ajaxexport"))) {
				$GLOBALS['ISC_CLASS_ADMIN_AJAXEXPORTER_CONTROLLER'] = GetClass('ISC_ADMIN_AJAXEXPORTER_CONTROLLER');
				$GLOBALS['ISC_CLASS_ADMIN_AJAXEXPORTER_CONTROLLER']->Export();
			}
			else if(is_numeric(isc_strpos($do, "exporttemplate"))) {
				$GLOBALS['ISC_CLASS_ADMIN_EXPORTTEMPLATES'] = GetClass('ISC_ADMIN_EXPORTTEMPLATES');
				$GLOBALS['ISC_CLASS_ADMIN_EXPORTTEMPLATES']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "export"))) {
				$GLOBALS['ISC_CLASS_ADMIN_EXPORT'] = GetClass('ISC_ADMIN_EXPORT');
				$GLOBALS['ISC_CLASS_ADMIN_EXPORT']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "template"))) {
				$GLOBALS['ISC_CLASS_ADMIN_LAYOUT'] = GetClass('ISC_ADMIN_LAYOUT');
				$GLOBALS['ISC_CLASS_ADMIN_LAYOUT']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "user"))) {
				$GLOBALS['ISC_CLASS_ADMIN_USER'] = GetClass('ISC_ADMIN_USER');
				$GLOBALS['ISC_CLASS_ADMIN_USER']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "banner"))) {
				$GLOBALS['ISC_CLASS_ADMIN_BANNERS'] = GetClass('ISC_ADMIN_BANNERS');
				$GLOBALS["ISC_CLASS_ADMIN_BANNERS"]->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "brand"))) {
				$GLOBALS['ISC_CLASS_ADMIN_BRANDS'] = GetClass('ISC_ADMIN_BRANDS');
				$GLOBALS["ISC_CLASS_ADMIN_BRANDS"]->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "commentsystem"))) {
				$GLOBALS['ISC_CLASS_SETTINGS_COMMENTS'] = GetClass('ISC_ADMIN_SETTINGS_COMMENTS');
				$GLOBALS["ISC_CLASS_SETTINGS_COMMENTS"]->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "livechatsettings"))) {
				$GLOBALS['ISC_CLASS_ADMIN_SETTINGS_LIVECHAT'] = GetClass('ISC_ADMIN_SETTINGS_LIVECHAT');
				$GLOBALS['ISC_CLASS_ADMIN_SETTINGS_LIVECHAT']->HandleToDo($ToDo);
			}
			else if((isc_strpos($do, 'settings') !== false && isc_strpos($do, 'tax') !== false) ||
				isc_strpos($do, 'taxzone') !== false || isc_strpos($do, 'taxclass') !== false ||
				isc_strpos($do, 'taxrate') !== false) {
					$GLOBALS['ISC_CLASS_ADMIN_SETTINGS_TAX'] = GetClass('ISC_ADMIN_SETTINGS_TAX');
					$GLOBALS['ISC_CLASS_ADMIN_SETTINGS_TAX']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "shippingmanager"))) {
				$GLOBALS['ISC_ADMIN_SETTINGS_SHIPPINGMANAGER'] = GetClass('ISC_ADMIN_SETTINGS_SHIPPINGMANAGER');
				$GLOBALS['ISC_ADMIN_SETTINGS_SHIPPINGMANAGER']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "settings"))) {
				$GLOBALS['ISC_CLASS_ADMIN_SETTINGS'] = GetClass('ISC_ADMIN_SETTINGS');
				$GLOBALS['ISC_CLASS_ADMIN_SETTINGS']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "backup"))) {
				$GLOBALS['ISC_CLASS_ADMIN_BACKUP'] = GetClass('ISC_ADMIN_BACKUP');
				$GLOBALS["ISC_CLASS_ADMIN_BACKUP"]->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "stats"))) {
				$GLOBALS['ISC_CLASS_ADMIN_STATITICS'] = GetClass('ISC_ADMIN_STATISTICS');
				$GLOBALS['ISC_CLASS_ADMIN_STATITICS']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "log"))) {
				$GLOBALS['ISC_CLASS_ADMIN_LOGS'] = GetClass('ISC_ADMIN_LOGS');
				$GLOBALS['ISC_CLASS_ADMIN_LOGS']->HandleToDo($ToDo);
			}
			///
			else if(is_numeric(isc_strpos($do, "sincro"))) {
				$GLOBALS['ISC_CLASS_ADMIN_SINCRO'] = GetClass('ISC_ADMIN_SINCRO');
				$GLOBALS['ISC_CLASS_ADMIN_SINCRO']->HandleToDo($ToDo);
			}
			else if(is_numeric(strpos($do, "quicksearch"))) {
				$GLOBALS['ISC_CLASS_ADMIN_QUICKSEARCH'] = GetClass('ISC_ADMIN_QUICKSEARCH');
				$GLOBALS['ISC_CLASS_ADMIN_QUICKSEARCH']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "return")) && GetConfig('EnableReturns') && gzte11(ISC_LARGEPRINT)) {
				$GLOBALS['ISC_CLASS_ADMIN_RETURNS'] = GetClass('ISC_ADMIN_RETURNS');
				$GLOBALS['ISC_CLASS_ADMIN_RETURNS']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "giftcertificate")) && GetConfig('EnableGiftCertificates') && gzte11(ISC_LARGEPRINT)) {
				$GLOBALS['ISC_CLASS_ADMIN_GIFTCERTIFICATES'] = GetClass('ISC_ADMIN_GIFTCERTIFICATES');
				$GLOBALS['ISC_CLASS_ADMIN_GIFTCERTIFICATES']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "systeminfo"))) {
				$GLOBALS['ISC_CLASS_ADMIN_SYSINFO'] = GetClass('ISC_ADMIN_SYSINFO');
				$GLOBALS['ISC_CLASS_ADMIN_SYSINFO']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "runaddon"))) {
				$GLOBALS['ISC_CLASS_ADMIN_ADDON'] = GetClass('ISC_ADMIN_ADDON');
				$GLOBALS["ISC_CLASS_ADMIN_ADDON"]->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "redirects"))) {

				$GLOBALS['ISC_CLASS_ADMIN_REDIRECTS'] = GetClass('ISC_ADMIN_REDIRECTS');
				$GLOBALS["ISC_CLASS_ADMIN_REDIRECTS"]->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "downloadaddons"))) {
				$GLOBALS['ISC_CLASS_ADMIN_DOWNLOADADDONS'] = GetClass('ISC_ADMIN_DOWNLOADADDONS');
				$GLOBALS['ISC_CLASS_ADMIN_DOWNLOADADDONS']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "formfields"))) {
				$GLOBALS['ISC_CLASS_ADMIN_FORMFIELDS'] = GetClass('ISC_ADMIN_FORMFIELDS');
				$GLOBALS['ISC_CLASS_ADMIN_FORMFIELDS']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "image"))) {
				$GLOBALS['ISC_CLASS_ADMIN_FORMFIELDS'] = GetClass('ISC_ADMIN_IMAGEMANAGER');
				$GLOBALS['ISC_CLASS_ADMIN_FORMFIELDS']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "optimizer"))) {
				$GLOBALS['ISC_CLASS_ADMIN_FORMFIELDS'] = GetClass('ISC_ADMIN_OPTIMIZER');
				$GLOBALS['ISC_CLASS_ADMIN_FORMFIELDS']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "jobstatus"))){
				$GLOBALS['ISC_CLASS_ADMIN_JOBSTATUS'] = GetClass('ISC_ADMIN_JOBSTATUS');
				$GLOBALS['ISC_CLASS_ADMIN_JOBSTATUS']->HandleToDo($ToDo);
			}
			else if(is_numeric(isc_strpos($do, "robotstxt"))) {
				$GLOBALS['ISC_CLASS_ADMIN_ROBOTSTXT'] = GetClass('ISC_ADMIN_ROBOTSTXT');
				$GLOBALS['ISC_CLASS_ADMIN_ROBOTSTXT']->HandleToDo($ToDo);
			}
		}
	}
