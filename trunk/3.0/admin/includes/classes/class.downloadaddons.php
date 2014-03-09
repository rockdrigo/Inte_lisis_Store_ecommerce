<?php

	/**
	* ISC_ADMIN_DOWNLOADADDONS
	* View existing and download new addon packages from Interspire
	*
	* @author Mitchell Harper
	* @copyright Interspire Pty. Ltd.
	* @date	27th Jan 2008
	*/

	class ISC_ADMIN_DOWNLOADADDONS extends ISC_ADMIN_BASE
	{

		/**
		* HandleToDo
		* Work out which function to run
		*
		* @param String $Do Which action to run
		* @return Void
		*/
		public function HandleToDo($Do)
		{
			if(GetConfig('DisableAddons') == true) {
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			}

			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('addons');

			switch (isc_strtolower($Do)) {
				case "purchasedownloadaddons": {
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Customers)) {
						$this->PurchaseAddonForm();
					}
					break;
				}
				default: {
					if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Customers)) {

						$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Addons') => "index.php?ToDo=viewDownloadAddons");

						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
						$this->ListAddons();
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					} else {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}
					break;
				}
			}
		}

		/**
		* GetExistingAddons
		* Get a list of existing addon packages to display on the addons page
		*
		* @param Array $AddonsIds An array in which the addons ids will be stored
		* @return String
		*/
		private function _GetExistingAddons(&$AddonsIds)
		{

			$output = "";
			$addon_root = sprintf("%s/addons", GetConfig("ShopPath"));
			$addons = GetAvailableAddonModules();
			$AddonsIds = array();

			if(!empty($addons)) {
				foreach($addons as $addon) {
					array_push($AddonsIds, $addon['object']->GetId());

					// Is the addon enabled and configured? If so it can be ran
					if(AddonsModuleIsEnabled($addon['object']->GetId())) {
						$run_command = sprintf("document.location.href='index.php?ToDo=runAddon&addon=%s';", $addon['object']->GetId());
					}
					else {
						$run_command = sprintf("if(confirm('%s')) { document.location.href='index.php?ToDo=viewAddonSettings'; } ", sprintf(GetLang("AddonModuleMustBeEnabled"), $addon['object']->GetName()));
					}

					$addonDirectory = str_replace("addon_", "", $addon['object']->GetId());
					$logo = $addon['object']->GetImage();
					if($logo) {
						$logo = '<img src="'.$logo.'" width="200" height="92" />';
					}
					else {
						$logo = '<div style="width: 200px; height: 92px; border: 1px solid #000; text-align: center; font-size: 150%;"><div style="padding-top: 30px;">'.$addon['object']->GetName().'</div></div>';
					}
					$output .= sprintf('<div style="text-align: center; float:left; margin-right:10px; height:145px">
									%s
									<div style="padding-top:10px;">
										<input type="button" value="%s" onclick="%s" /><br />
										<a href="index.php?ToDo=viewAddonSettings">%s</a>
									</div>
								</div>', $logo, GetLang("RunThisAddon"), $run_command, GetLang("AddonSettings"));
				}
			}
			else {
				$output = GetLang("NoAddons");
			}

			return $output;
		}

		/**
		* ListAddons
		* Show a list of existing addons and new ones available for download
		*
		* @return Void
		*/
		public function ListAddons()
		{

			// Have we just downloaded a new addon?
			if(isset($_GET['newDownloaded'])) {
				$GLOBALS['Message'] = MessageBox(GetLang('NewAddonDownloaded'), MSG_SUCCESS);
			}

			$existing_addons = array();
			$GLOBALS['ExistingAddons'] = $this->_GetExistingAddons($existing_addons);
			if(!GetConfig('DisableAddonDownloading')) {
				$new_addons = $this->GetDownloadableAddons($existing_addons);
				if ($new_addons === false) {
					$GLOBALS['DownloadableAddons'] = GetLang("CouldNotReachAddonServer");
				} else if (strlen($new_addons) == 0) {
					$GLOBALS['DownloadableAddons'] = GetLang("NoNewAddons");
				} else {
					$GLOBALS['DownloadableAddons'] = $new_addons;
				}
			}
			else {
				$GLOBALS['HideDownloadAddons'] = 'display: none';
			}

			$this->template->display('addons.manage.tpl');
		}

		/**
		* GetDownloadableAddons
		* Query Interspire.com for a list of available addons
		*
		* @param Array $ExistingAddons An array of addons which have already been installed
		* @return mixed Returns HTML output (as a string) of the addon section, or a blank string if no downloads are available, or FALSE (boolean) if there was an issue contacting the download server
		*/
		public function GetDownloadableAddons($ExistingAddons)
		{
			$result = PostToRemoteFileAndGetResponse(GetConfig('AddonXMLFile'));
			if (!$result) {
				return false;
			}

			$xml = new SimpleXMLElement($result);
			$output = "";

			foreach($xml->addon as $addon) {
				// Have they already downloaded this addon?
				if(!in_array(str_replace("isc_", "", $addon->prodCode), $ExistingAddons)) {

					if((int)$addon->prodPrice == 0) {
						$button_text = GetLang("DownloadAddonFree");
					}
					else {
						$button_text = sprintf(GetLang("DownloadAddonPaid"), number_format($addon->prodPrice));
					}

					$output .= sprintf('<div style="text-align: center; float:left; margin-right:10px">
									<a href="%s" target="_blank"><img src="%s" width="200" height="92" border="0" /></a>
									<div style="padding-top:10px; width:250px">
										%s (<a target="_blank" href="%s">%s</a>)<br /><br />
										<input type="button" value="%s" onclick="tb_show(\'\', \'index.php?ToDo=purchaseDownloadAddons&prodId=%d&prodPrice=%s&width=300&height=255\')" /><br />
									</div>
								</div>', $addon->prodAddonLink, $addon->prodAddonLogo, $addon->prodAddonSummary, $addon->prodAddonLink, GetLang("AddonMoreInfo"), $button_text, $addon->pk_prodId, $addon->prodPrice);
				}
			}

			return $output;
		}

		/**
		* PurchaseAddonForm
		* Show the template that allows the user to purchase the addon or enter their license key
		*
		* @return Void
		*/
		public function PurchaseAddonForm()
		{
			if(isset($_GET['prodId']) && isset($_GET['prodPrice'])) {
				$prodId = $_GET['prodId'];
				$prodPrice = $_GET['prodPrice'];
				$GLOBALS['ProductPrice'] = $prodPrice;
				if($prodPrice == 0) {
					$GLOBALS['HideAddonPurchaseForm'] = 'display: none';
					$GLOBALS['ForceAddonDownload'] = 1;
				}
				else {
					$GLOBALS['ForceAddonDownload'] = 0;
				}
				$GLOBALS['AddonId'] = $prodId;
				$GLOBALS['BuyLink'] = "http://www.go2market.mx=";
				$GLOBALS['AddonPurchaseText'] = sprintf(GetLang("AddonPurchaseText"), (int)$prodPrice);
				$this->template->display('addons.purchase.tpl');
			}
		}
	}