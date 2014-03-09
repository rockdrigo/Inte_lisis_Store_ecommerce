<?php
	class ISC_ADMIN_SETTINGS extends ISC_ADMIN_BASE
	{
		public $all_vars = array (
			'AllowPurchasing',
			'Language',
			'serverStamp',
			'HostingProvider',
			'UseWYSIWYG',
			'UseSSL',
			'SharedSSLPath',
			'SubdomainSSLPath',
			'ForceControlPanelSSL',
			'dbType',
			'dbEncoding',
			'dbServer',
			'dbUser',
			'dbPass',
			'dbDatabase',
			'tablePrefix',
			'StoreName',
			'StoreAddress',
			'LogoType',
			'StoreLogo',
			'ShopPath',
			'CharacterSet',
			'HomePagePageTitle',
			'MetaKeywords',
			'MetaDesc',
			'DownloadDirectory',
			'ImageDirectory',
			'template',
			'SiteColor',
			'CurrencyToken',
			'CurrencyLocation',
			'DecimalToken',
			'DecimalPlaces',
			'ThousandsToken',
			'InstallDate',
			'WeightMeasurement',
			'LengthMeasurement',
			'DisplayDateFormat',
			'ExportDateFormat',
			'ExtendedDisplayDateFormat',
			'HomeFeaturedProducts',
			'HomeNewProducts',
			// REQ11064 JIB: Agrege la variable de HomePopularProducts
			'HomePopularProducts',
			//?
			'ShowPriceGuest',
			'HomeBlogPosts',
			'CategoryProductsPerPage',
			'CategoryListDepth',
			'CategoryListStyle',
			'ProductReviewsPerPage',
			'TagCloudsEnabled',
			'ShowAddToCartQtyBox',
			'CaptchaEnabled',
			'ShowCartSuggestions',
			'AdminEmail',
			'OrderEmail',
			'LowInventoryNotificationAddress',
			'ShowThumbsInCart',
			'AutoApproveReviews',
			'SearchSuggest',
			'QuickSearch',
			'StartingOrderNumber',
			'DesignMode',
			'CompanyName',
			'CompanyAddress',
			'CompanyCity',
			'CompanyCountry',
			'CompanyState',
			'CompanyZip',
			'CheckoutMethods',
			'EmailIntegrationMethods',
			'EmailIntegrationNewsletterDoubleOptin',
			'EmailIntegrationNewsletterSendWelcome',
			'EmailIntegrationOrderDoubleOptin',
			'EmailIntegrationOrderSendWelcome',
			'ShowThumbsInControlPanel',
			'EnableSEOUrls',
			'ShowInventory',
			'ShowPreOrderInventory',
			'StoreTimeZone',
			'StoreDSTCorrection',
			'ShowDownloadTemplates',
			'RSSNewProducts',
			'RSSPopularProducts',
			'RSSFeaturedProducts',
			'RSSCategories',
			'RSSProductSearches',
			'RSSLatestBlogEntries',
			'RSSItemsLimit',
			'RSSCacheTime',
			'RSSSyndicationIcons',
			'BackupsLocal',
			'BackupsRemoteFTP',
			'BackupsRemoteFTPHost',
			'BackupsRemoteFTPUser',
			'BackupsRemoteFTPPass',
			'BackupsRemoteFTPPath',
			'BackupsAutomatic',
			'BackupsAutomaticMethod',
			'BackupsAutomaticDatabase',
			'BackupsAutomaticImages',
			'BackupsAutomaticDownloads',
			'GoogleMapsAPIKey',
			'NotificationMethods',
			'CurrencyMethods',
			'DefaultCurrencyID',
			'DefaultCurrencyRate',
			'MailAutomaticallyTickNewsletterBox',
			'MailAutomaticallyTickOrderBox',
			'AnalyticsMethods',
			'SystemLogging',
			'HidePHPErrors',
			'SystemLogTypes',
			'SystemLogSeverity',
			'SystemLogMaxLength',
			'AdministratorLogging',
			'AdministratorLogMaxLength',
			'DebugMode',
			'EnableReturns',
			'ReturnReasons',
			'ReturnActions',
			'ReturnCredits',
			'ReturnInstructions',
			'EmailOwnerOnReturn',
			'SendReturnConfirmation',
			'NotifyOnReturnStatusChange',
			'EnableGiftCertificates',
			'GiftCertificateAmounts',
			'GiftCertificateCustomAmounts',
			'GiftCertificateMinimum',
			'GiftCertificateMaximum',
			'GiftCertificateExpiry',
			'GiftCertificateThemes',
			'GiftCertificateCustomDirectory',
			'GiftCertificateMasterDirectory',
			'UpdateInventoryLevels',
			'UpdateInventoryOnOrderEdit',
			'UpdateInventoryOnOrderDelete',
			'OrderStatusNotifications',
			'AddonModules',
			'AKBIsConfigured',
			'AKBPath',
			'ARSPageIds',
			'ARSIntegrated',
			'ShowProductPrice',
			'ShowProductSKU',
			'ShowProductWeight',
			'ShowProductBrand',
			'ShowProductShipping',
			'ShowProductRating',
			'EncryptionToken',
			'EnableWishlist',
			'EnableAccountCreation',
			'EnableOrderComments',
			'EnableOrderTermsAndConditions',
			'OrderTermsAndConditionsType',
			'OrderTermsAndConditions',
			'OrderTermsAndConditionsLink',
			'EnablePersistentShoppingCart',
			'PersistentShoppingCartAmount',
			'PersistentShoppingCartType',
			'EnableProductComparisons',
			'LogoFields',
			'ForceWebsiteTitleText',
			'UseAlternateTitle',
			'AlternateTitle',
			'UsingTemplateLogo',
			'UsingLogoEditor',
			'TagCartQuantityBoxes',
			'ProductBreadcrumbs',
			'FastCartAction',
			'AffiliateConversionTrackingCode',
			'GuestCustomerGroup',
			'ForwardInvoiceEmails',
			'MailUseSMTP',
			'MailSMTPServer',
			'MailSMTPUsername',
			'MailSMTPPassword',
			'MailSMTPPort',
			'HTTPProxyServer',
			'HTTPProxyPort',
			'HTTPSSLVerifyPeer',
			'DimensionsDecimalToken',
			'DimensionsThousandsToken',
			'DimensionsDecimalPlaces',
			'DigitalOrderHandlingFee',
			'ProductImageMode',
			'CategoryDisplayMode',
			'CheckoutType',
			'GuestCheckoutEnabled',
			'GuestCheckoutCreateAccounts',
			'AccountingMethods',
			'QuickBooksPassword',
			'QuickBooksUsername',
			'QuickBooksFileID',
			'LiveChatModules',
			'CategoryPerRow',
			'CategoryImageWidth',
			'CategoryImageHeight',
			'CategoryDefaultImage',
			'BrandPerRow',
			'BrandImageWidth',
			'BrandImageHeight',
			'BrandDefaultImage',
			'ShowMailingListInvite',
			'ShowAddToCartLink',
			'ShowAddThisLink',
			'CategoryListingMode',
			'TagCloudMinSize',
			'TagCloudMaxSize',
			'BulkDiscountEnabled',
			'EnableProductTabs',
			'MultipleShippingAddresses',
			'VendorLogoSize',
			'VendorPhotoSize',
			'ShippingFactoringDimension',
			'DefaultProductImage',
			'GettingStartedCompleted',
			'Favicon',
			'SessionSavePath',
			'OptimizerMethods',
			'SearchDefaultProductSort',
			'SearchDefaultContentSort',
			'SearchProductDisplayMode',
			'SearchResultsPerPage',
			'SearchOptimisation',
			'AbandonOrderLifetime',
			'DownForMaintenance',
			'DownForMaintenanceMessage',
			'EnableCustomersAlsoViewed',
			'CustomersAlsoViewedCount',

			/** Ebay Settings **/
			'EbayDevId',
			'EbayAppId',
			'EbayCertId',
			'EbayUserToken',
			'EbayDefaultSite',
			'EbayStore',
			'EbayTestMode',
			'EbaySettingsValid',

			/** Product Image Settings **/
			'ProductImagesStorewideThumbnail_width',
			'ProductImagesStorewideThumbnail_height',
			'ProductImagesStorewideThumbnail_timeChanged',
			'ProductImagesProductPageImage_width',
			'ProductImagesProductPageImage_height',
			'ProductImagesProductPageImage_timeChanged',
			'ProductImagesGalleryThumbnail_width',
			'ProductImagesGalleryThumbnail_height',
			'ProductImagesGalleryThumbnail_timeChanged',
			'ProductImagesZoomImage_width',
			'ProductImagesZoomImage_height',
			'ProductImagesZoomImage_timeChanged',
			'ProductImagesTinyThumbnailsEnabled',
			'ProductImagesImageZoomEnabled',

			'JSCacheToken',

			'DefaultPreOrderMessage',
			'CommentSystemModule',
			'ShippingManagerModules',
			'RedirectWWW',

			// Tax Settings
			'taxLabel',
			'taxEnteredWithPrices',
			'taxCalculationBasedOn',
			'taxDefaultTaxDisplayCatalog',
			'taxDefaultTaxDisplayProducts',
			'taxDefaultTaxDisplayCart',
			'taxDefaultTaxDisplayOrders',
			'taxChargesOnOrdersBreakdown',
			'taxChargesInCartBreakdown',
			'taxDefaultCountry',
			'taxDefaultState',
			'taxDefaultZipCode',
			'taxPendingChanges',
			'taxShippingTaxClass',
			'taxGiftWrappingTaxClass',
			'taxPendingChanges',

			'ShoppingComparisonModules',

			/** PCI Settings **/
			'PCIPasswordMinLen',
			'PCIPasswordHistoryCount',
			'PCIPasswordExpiryTimeDay',
			'PCILoginAttemptCount',
			'PCILoginLockoutTimeMin',
			'PCILoginIdleTimeMin',
			'PCILoginInactiveTimeDay',

			// Mobile/Portable Template
			'enableMobileTemplate',
			'enableMobileTemplateDevices',
			'mobileTemplateLogo',

			'FacebookLikeButtonEnabled',
			'FacebookLikeButtonStyle',
			'FacebookLikeButtonPosition',
			'FacebookLikeButtonVerb',
			'FacebookLikeButtonShowFaces',
			'FacebookLikeButtonAdminIds',

			// Deleted orders handling
			'DeletedOrdersAction',

			// Category flyout menu configuration
			'categoryFlyoutMouseOutDelay',
			'categoryFlyoutDropShadow',
		
			// Added by Nissim
			'UseStoreHours',
			'StoreHoursFromHours',
			'StoreHoursFromMinutes',
			'StoreHoursToHours',
			'StoreHoursToMinutes',
			'StoreClosed',
		
			'CheckoutUseExtraFields',
			
			'CheckoutExtraFieldActive1', 
			'CheckoutExtraFieldName1', 
			'CheckoutExtraFieldType1',
			'CheckoutExtraFieldValue1',  
			'CheckoutExtraFieldRequired1',
			
			'CheckoutExtraFieldActive2', 
			'CheckoutExtraFieldName2', 
			'CheckoutExtraFieldType2',
			'CheckoutExtraFieldValue2', 
			'CheckoutExtraFieldRequired2',
			
			'CheckoutExtraFieldActive3', 
			'CheckoutExtraFieldName3', 
			'CheckoutExtraFieldType3',
			'CheckoutExtraFieldValue3', 
			'CheckoutExtraFieldRequired3',
			
			'CheckoutExtraFieldActive4', 
			'CheckoutExtraFieldName4', 
			'CheckoutExtraFieldType4', 
			'CheckoutExtraFieldValue4',
			'CheckoutExtraFieldRequired4',
			
			'CheckoutExtraFieldActive5', 
			'CheckoutExtraFieldName5', 
			'CheckoutExtraFieldType5', 
			'CheckoutExtraFieldValue5',
			'CheckoutExtraFieldRequired5',
		
			'syncDropboxActive',
			'syncDropboxOffline',
			'syncDropboxDir',
			'syncDropboxImagesDir',
			'syncFileNameInc',
			'syncFileNameOut',
			'syncPathToType',
			'syncTypeAtributeName',

			'LicenseTypeControl',
				
			'isIntelisis',
		
			'syncIWSurl',
			'syncIWShost',
			'syncIWSport',
			'syncIWSdbname',
			'syncIWSdbuser',
			'syncIWSdbpass',
			'syncIWSintelisisuser',
			'syncIWSintelisispass',
			'syncIWSintelisisempresa',
			'syncIWSintelisissucursal',
			'syncIWSintelisisstocktime',
		
			'showDeliveryDateFromStatus',
		
			'ignoreAddressID0',
				
			'syncArchiveDir',
				
			'AccountCreationInactiveUsers',
			
			'DisplayCheckBoxLimit',
			
			'ForcePasswordChangeNewUsers',
				
			'ShowProductBrandImage',
			'UseStoreOriginForStock',
				
			'UsersMountForTemplates',
		);

		public $timezones = array (
			'-11' => 'Minus1100',
			'-10' => 'Minus1000',
			'-9' => 'Minus900',
			'-8' => 'Minus800',
			'-7' => 'Minus700',
			'-6' => 'Minus600',
			'-5' => 'Minus500',
			'-4' => 'Minus400',
			'-3.5' => 'Minus330',
			'-3' => 'Minus300',
			'-2' => 'Minus200',
			'-1' => 'Minus100',
			'0' => '000',
			'1' => '100',
			'2' => '200',
			'3' => '300',
			'3.5' => '330',
			'4' => '400',
			'4.5' => '430',
			'5' => '500',
			'5.5' => '530',
			'6' => '600',
			'7' => '700',
			'8' => '800',
			'9' => '900',
			'9.5' => '930',
			'10' => '1000',
			'11' => '1100',
			'12' => '1200',
		);

		public $validCharacterSets = array (
			'UTF-8',
			'ISO-8859-1',
			'ISO-8859-15',
			'cp866',
			'cp1251',
			'cp1252',
			'KOI8-R',
			'Shift_JIS',
			'EUC-JP',
		);

		/**
		 * The constructor.
		 */
		public function __construct()
		{
			parent::__construct();
			if(!gzte11(ISC_LARGEPRINT)) {
				$GLOBALS[base64_decode('SGlkZVN0YWZmTG9ncw==')] = "none";
			}

			if (isset($_REQUEST['currentTab'])) {
				$GLOBALS['CurrentTab'] = (int)$_REQUEST['currentTab'];
			} else {
				$GLOBALS['CurrentTab'] = 0;
			}
		}

		public function HandleToDo($Do)
		{
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('settings');
			if (isc_strtolower($Do) === 'settingsfooterimage') {
				$this->ManageClickSettings();
				return;
			}

			if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Settings)) {
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				return;
			}

			$GLOBALS['BreadcrumEntries'] = array (
				GetLang('Home') => "index.php",
				GetLang('Settings') => "index.php?ToDo=viewSettings",
			);

			switch (isc_strtolower($Do))
			{
				case "saveupdatedaffiliatesettings":
				{
					$GLOBALS['BreadcrumEntries'][GetLang('AffiliateSettings')] = "index.php?ToDo=viewAffiliateSettings";
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->SaveUpdatedAffiliateSettings();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "viewaffiliatesettings":
				{
					$GLOBALS['BreadcrumEntries'][GetLang('AffiliateSettings')] = "index.php?ToDo=viewAffiliateSettings";
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->ManageAffiliateSettings();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "saveupdatedkbsettings":
				{
					$GLOBALS['BreadcrumEntries'][GetLang('KBSettings')] = "index.php?ToDo=viewKBSettings";
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->SaveUpdatedKBSettings();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "viewkbsettings":
				{
					$GLOBALS['BreadcrumEntries'][GetLang('KBSettings')] = "index.php?ToDo=viewKBSettings";
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->ManageKBSettings();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "saveupdatedanalyticssettings":
				{
					$GLOBALS['BreadcrumEntries'][GetLang('AnalyticsSettings')] = "index.php?ToDo=viewAnalyticsSettings";
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->SaveUpdatedAnalyticsSettings();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "viewanalyticssettings":
				{
					$GLOBALS['BreadcrumEntries'][GetLang('AnalyticsSettings')] = "index.php?ToDo=viewAnalyticsSettings";
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->ManageAnalyticsSettings();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "testnotificationmethodsettings":
				{
					$this->TestNotificationMethod();
					break;
				}
				case "saveupdatednotificationsettings":
				{
					$GLOBALS['BreadcrumEntries'][GetLang('NotificationSettings')] = "index.php?ToDo=viewNotificationSettings";
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->SaveUpdatedNotificationSettings();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "viewnotificationsettings":
				{
					$GLOBALS['BreadcrumEntries'][GetLang('NotificationSettings')] = "index.php?ToDo=viewNotificationSettings";
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->ManageNotificationSettings();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "saveupdatedsettings":
				{
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->SaveUpdatedSettings();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "viewcurrencysettings":
				{
					$GLOBALS['BreadcrumEntries'][GetLang('CurrencySettings')] = "index.php?ToDo=viewCurrencySettings";
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->ManageCurrencySettings();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "settingsaddcurrency":
				{
					$GLOBALS['BreadcrumEntries'][GetLang('CurrencySettings')] = "index.php?ToDo=viewCurrencySettings";
					$GLOBALS['BreadcrumEntries'][GetLang('AddCurrency')] = "index.php?ToDo=settingsAddCurrency";
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->AddCurrency();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "settingssavenewcurrency":
				{
					$GLOBALS['BreadcrumEntries'][GetLang('CurrencySettings')] = "index.php?ToDo=viewCurrencySettings";
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->SaveNewCurrency();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "settingsdeletecurrencies":
				{
					$GLOBALS['BreadcrumEntries'][GetLang('CurrencySettings')] = "index.php?ToDo=viewCurrencySettings";
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->DeleteCurrencies();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "settingseditcurrency":
				{
					$GLOBALS['BreadcrumEntries'][GetLang('CurrencySettings')] = "index.php?ToDo=viewCurrencySettings";
					$GLOBALS['BreadcrumEntries'][GetLang('EditCurrency')] = "index.php?ToDo=settingsEditCurrency";
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->EditCurrency();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "settingseditcurrencystatus":
				{
					$GLOBALS['BreadcrumEntries'][GetLang('CurrencySettings')] = "index.php?ToDo=viewCurrencySettings";

					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->UpdateCurrencyStatus();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "settingssaveupdatedcurrency":
				{
					$GLOBALS['BreadcrumEntries'][GetLang('CurrencySettings')] = "index.php?ToDo=viewCurrencySettings";
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->SaveUpdatedCurrency();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "settingssavecurrencysettings":
				{
					$GLOBALS['BreadcrumEntries'][GetLang('CurrencySettings')] = "index.php?ToDo=viewCurrencySettings";
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->SaveUpdatedCurrencySettings();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "settingssetasdefaultcurrency":
				{
					$GLOBALS['BreadcrumEntries'][GetLang('CurrencySettings')] = "index.php?ToDo=viewCurrencySettings";
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->SaveSetAsDefaultCurrency();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "settingsupdateprices":
				{
					$this->UpdateProductPrices();
					break;
				}
				case "saveupdatedreturnssettings":
				{
					if (!gzte11(ISC_LARGEPRINT)) {
						exit;
					}
					$GLOBALS['BreadcrumEntries'][GetLang('ReturnsSettings')] = "index.php?ToDo=viewReturnsSettings";

					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->SaveUpdatedReturnsSettings();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();

					break;
				}
				case "viewreturnssettings":
				{
					if (!gzte11(ISC_LARGEPRINT)) {
						exit;
					}

					$GLOBALS['BreadcrumEntries'][GetLang('ReturnsSettings')] = "index.php?ToDo=viewReturnsSettings";

					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->ManageReturnsSettings();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "saveupdatedgiftcertificatesettings":
				{
					if (!gzte11(ISC_LARGEPRINT)) {
						exit;
					}

					$this->SaveUpdatedGiftCertificateSettings();
					break;
				}
				case "viewgiftcertificatesettings":
				{
					if (!gzte11(ISC_LARGEPRINT)) {
						exit;
					}

					$GLOBALS['BreadcrumEntries'][GetLang('GiftCertificateSettings')] = "index.php?ToDo=viewGiftCertificateSettings";
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->ManageGiftCertificateSettings();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "viewaddonsettings":
				{
					if(GetConfig('DisableAddons') == true) {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					$GLOBALS['BreadcrumEntries'][GetLang('AddonSettings')] = "index.php?ToDo=viewAddonSettings";
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->ManageAddonSettings();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				case "saveupdatedaddonsettings":
				{
					if(GetConfig('DisableAddons') == true) {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
					}

					$GLOBALS['BreadcrumEntries'][GetLang('AddonSettings')] = "index.php?ToDo=viewAddonSettings";
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->SaveUpdatedAddonSettings();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					break;
				}
				default:
				{
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->ManageSettings();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				}
			}
		}

		private function SaveUpdatedGiftCertificateSettings()
		{
			$boolean = array (
				'EnableGiftCertificates',
				'GiftCertificateCustomAmounts',
			);

			foreach ($boolean as $var) {
				if (isset($_POST[$var]) && $_POST[$var] == 1) {
					$GLOBALS['ISC_NEW_CFG'][$var] = 1;
				} else {
					$GLOBALS['ISC_NEW_CFG'][$var] = 0;
				}
			}

			$positive_ints = array (
				'GiftCertificateMinimum',
				'GiftCertificateMaximum',
			);

			foreach ($positive_ints as $var) {
				if (isset($_POST[$var]) && (int) $_POST[$var] > 0) {
					$GLOBALS['ISC_NEW_CFG'][$var] = (int) $_POST[$var];
				} else {
					$GLOBALS['ISC_NEW_CFG'][$var] = 0;
				}
			}

			if (isset($_POST['GiftCertificateExpiry']) && isset($_POST['EnableGiftCertificateExpiry'])) {
				if ($_POST['GiftCertificateExpiryRange'] == "years") {
					$_POST['GiftCertificateExpiry'] *= 365;
				} else if ($_POST['GiftCertificateExpiryRange'] == "months") {
					$_POST['GiftCertificateExpiry'] *= 30;
				} else if ($_POST['GiftCertificateExpiryRange'] == "weeks") {
					$_POST['GiftCertificateExpiry'] *= 7;
				}
				$GLOBALS['ISC_NEW_CFG']['GiftCertificateExpiry'] = $_POST['GiftCertificateExpiry'] * 86400;
			}
			else {
				$GLOBALS['ISC_NEW_CFG']['GiftCertificateExpiry'] = 0;
			}

			$amounts = preg_split("#\s+#", $_POST['GiftCertificateAmounts'], -1, PREG_SPLIT_NO_EMPTY);
			$PredefinedAmounts = array();
			foreach ($amounts as $amount) {
				if (CNumeric($amount) > 0 && trim($amount) != "") {
					$PredefinedAmounts[] = trim(CNumeric($amount));
				}
			}
			// GiftCertificateAmounts is var_exported in CommitSettings so no need to addslashes here
			$GLOBALS['ISC_NEW_CFG']['GiftCertificateAmounts'] = $PredefinedAmounts;

			if ($this->CommitSettings($messages)) {
				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();
				FlashMessage(GetLang('GiftCertificateSettingsSavedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewGiftCertificateSettings');
			} else {
				FlashMessage(sprintf(GetLang('GiftCertificateSettingsNotSaved'), $messages), MSG_ERROR, 'index.php?ToDo=viewGiftCertificateSettings');
			}
		}

		private function ManageGiftCertificateSettings($messages=array())
		{
			$GLOBALS['Message'] = GetFlashMessageBoxes();

			// Get the list of predefined amounts that are enabled and format them
			if (is_array(GetConfig('GiftCertificateAmounts'))) {
				$gift_cert_amounts = GetConfig('GiftCertificateAmounts');
				$gift_cert_amounts = array_map('FormatPrice', $gift_cert_amounts);
				$GLOBALS['GiftCertificateAmountsArea'] = implode("\r\n", $gift_cert_amounts);
			}

			// Are gift certificates enabled?
			if (GetConfig('EnableGiftCertificates') == 1) {
				$GLOBALS['IsEnableGiftCertificates'] = "checked=\"checked\"";
				$GLOBALS['ManageGiftCertificateTemplatesNotice'] =
					GetLang('GiftCertificateManageTemplatesNotice',
						array('manageTemplatesUrl' => 'index.php?ToDo=viewTemplates&forceTab=7'));
			}

			// Can customers enter their own amount for the gift certificates?
			if (GetConfig('GiftCertificateCustomAmounts') == 1) {
				$GLOBALS['IsGiftCertificateCustomAmounts'] = "checked=\"checked\"";
				$GLOBALS['HideSelectAmounts'] = "none";
			}
			else {
				$GLOBALS['IsGiftCertificateSelectAmounts'] = "checked=\"checked\"";
				$GLOBALS['HideCustomAmounts'] = "none";
			}

			$GLOBALS['GiftCertificateMinimum'] = GetConfig('GiftCertificateMinimum');
			$GLOBALS['GiftCertificateMaximum'] = GetConfig('GiftCertificateMaximum');

			// Are gift certificates set to expire after a certain time period?
			if (GetConfig('GiftCertificateExpiry') > 0) {
				$GLOBALS['IsGiftCertificateExpiry'] = "checked=\"checked\"";
				if (GetConfig('GiftCertificateExpiry')) {
					$days = GetConfig('GiftCertificateExpiry')/86400;
					if (($days % 365) == 0) {
						$GLOBALS['ExpiresAfter'] = $days/365;
						$GLOBALS['RangeYearsSelected'] = "selected=\"selected\"";
					}
					else if (($days % 30) == 0) {
						$GLOBALS['ExpiresAfter'] = $days/30;
						$GLOBALS['RangeMonthsSelected'] = "selected=\"selected\"";
					}
					else if (($days % 7) == 0) {
						$GLOBALS['ExpiresAfter'] = $days/7;
						$GLOBALS['RageWeeksSelected'] = "selected=\"selected\"";
					}
					else {
						$GLOBALS['ExpiresAfter'] = $days;
					}
				}
			}

			$this->template->display('settings.giftcertificates.manage.tpl');
		}

		private function SaveUpdatedReturnsSettings()
		{
			// Get the return reasons the user entered and convert them to an array.
			$returnreasons = explode("\n", $_POST['returnreasons']);
			$ReturnReasons = array();
			foreach ($returnreasons as $reason) {
				if (!trim($reason)) {
					continue;
				}

				$ReturnReasons[] = trim($reason);
			}
			// ReturnReasons is var_exported in CommitSettings so no need to addslashes here
			$GLOBALS['ISC_NEW_CFG']['ReturnReasons'] = $ReturnReasons;

			// Get the return actions the user entered and convert them to an array.
			$returnactions = explode("\n", $_POST['returnactions']);
			$ReturnActions = array();
			foreach ($returnactions as $action) {
				if (!trim($action)) {
					continue;
				}

				$ReturnActions[] = trim($action);
			}
			// ReturnActions is var_exported in CommitSettings so no need to addslashes here
			$GLOBALS['ISC_NEW_CFG']['ReturnActions'] = $ReturnActions;

			$boolean = array (
				'enablereturns'			=> 'EnableReturns',
				'returncredits'			=> 'ReturnCredits',
				'returnotifyowner'		=> 'EmailOwnerOnReturn',
				'returnnotifycustomer'	=> 'SendReturnConfirmation',
				'returnnotifystatus'	=> 'NotifyOnReturnStatusChange',
			);

			foreach ($boolean as $post_var => $config_var) {
				if (isset($_POST[$post_var]) && $_POST[$post_var] == 1) {
					$GLOBALS['ISC_NEW_CFG'][$config_var] = 1;
				} else {
					$GLOBALS['ISC_NEW_CFG'][$config_var] = 0;
				}
			}

			// Incoming return instructions?
			if (isset($_POST['returninstructions']) && $_POST['returninstructions'] != '') {
				$GLOBALS['ISC_NEW_CFG']['ReturnInstructions'] = trim($_POST['returninstructions']);
			}
			else {
				$GLOBALS['ISC_NEW_CFG']['ReturnInstructions'] = '';
			}

			$messages = array();

			if ($this->CommitSettings($messages)) {
				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();
				FlashMessage(GetLang('ReturnsSettingsSavedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewReturnsSettings');
			} else {
				FlashMessage(sprintf(GetLang('ReturnsSettingsNotSaved'), $messages), MSG_ERROR, 'index.php?ToDo=viewReturnsSettings');
			}
		}

		private function ManageReturnsSettings($messages=array())
		{

			$GLOBALS['Message'] = GetFlashMessageBoxes();

			foreach ($this->all_vars as $var) {
				if (is_string(GetConfig($var)) || is_numeric(GetConfig($var))) {
					$GLOBALS[$var] = isc_html_escape(GetConfig($var));
				} elseif (is_array(GetConfig($var))) {
					$GLOBALS[$var.'Area'] = isc_html_escape(implode("\r\n", GetConfig($var)));
				}
			}

			// Are returns enabled?
			if (GetConfig('EnableReturns')) {
				$GLOBALS['IsEnableReturns'] = "checked=\"checked\"";
			}

			// Can store credits be issued?
			if (GetConfig('ReturnCredits')) {
				$GLOBALS['IsReturnCredits'] = "checked=\"checked\"";
			}

			if (GetConfig('EmailOwnerOnReturn')) {
				$GLOBALS['IsReturnNotifyOwner'] = "checked=\"checked\"";
			}

			if (GetConfig('SendReturnConfirmation')) {
				$GLOBALS['IsReturnNotifyCustomer'] = "checked=\"checked\"";
			}

			if (GetConfig('NotifyOnReturnStatusChange')) {
				$GLOBALS['IsReturnNotifyStatusChange'] = "checked=\"checked\"";
			}

			$this->template->display('settings.returns.manage.tpl');
		}

		private function SaveUpdatedSettings()
		{
			if($_SERVER['REQUEST_METHOD'] != 'POST') {
				$this->ManageSettings();
				return;
			}

			$boolean = array (
				'UseWYSIWYG',
				'AllowPurchasing',
				'ShowInventory',
				'ShowPreOrderInventory',
				'ShowThumbsInControlPanel',
				'TagCloudsEnabled',
				'ShowAddToCartQtyBox',
				'CaptchaEnabled',
				'ShowCartSuggestions',
				'ShowThumbsInCart',
				'AutoApproveReviews',
				'SearchSuggest',
				'QuickSearch',
				'RSSNewProducts',
				'RSSPopularProducts',
				'RSSFeaturedProducts',
				'RSSCategories',
				'RSSProductSearches',
				'RSSLatestBlogEntries',
				'RSSSyndicationIcons',
				'StoreDSTCorrection',
				'SystemLogging',
				'AdministratorLogging',
				'DebugMode',
				'EnableWishlist',
				'EnableAccountCreation',
				'EnableProductComparisons',
				'ShowProductPrice',
				'ShowPriceGuest',
				'ShowProductSKU',
				'ShowProductWeight',
				'ShowProductBrand',
				'ShowProductShipping',
				'ShowProductRating',
				'HidePHPErrors',
				'HTTPSSLVerifyPeer',
				'ShowAddToCartLink',
				'ShowAddThisLink',
				'BulkDiscountEnabled',
				'EnableProductTabs',
				'ForceControlPanelSSL',
				'ProductImagesTinyThumbnailsEnabled',
				'ProductImagesImageZoomEnabled',
				'DownForMaintenance',
				'EnableCustomersAlsoViewed',
				'FacebookLikeButtonEnabled',
				'FacebookLikeButtonShowFaces',
				'categoryFlyoutDropShadow',
				'UseStoreHours',
				'StoreClosed',
				'CheckoutUseExtraFields',
				'CheckoutExtraFieldActive1',
				'CheckoutExtraFieldActive2',
				'CheckoutExtraFieldActive3',
				'CheckoutExtraFieldActive4',
				'CheckoutExtraFieldActive5',
				'CheckoutExtraFieldRequired1',
				'CheckoutExtraFieldRequired2',
				'CheckoutExtraFieldRequired3',
				'CheckoutExtraFieldRequired4',
				'CheckoutExtraFieldRequired5',
			
				'isIntelisis',
				'syncDropboxActive',
				'syncDropboxOffline',
			
				'showDeliveryDateFromStatus',
			
				'ignoreAddressID0',
					
				'AccountCreationInactiveUsers',
				
				'ForcePasswordChangeNewUsers',
					
				'ShowProductBrandImage',
				'UseStoreOriginForStock',
			);
			
			foreach ($boolean as $var) {
				if (isset($_POST[$var]) && ($_POST[$var] == 1 || $_POST[$var] === "ON")) {
					$GLOBALS['ISC_NEW_CFG'][$var] = 1;
				} else {
					$GLOBALS['ISC_NEW_CFG'][$var] = 0;
				}
			}

			$positive_ints = array (
				'HomeFeaturedProducts',
				'HomeNewProducts',
				// REQ11064 JIB: Agrege la variable de HomePopularProducts
				'HomePopularProducts',
				//?
				'HomeBlogPosts',
				'CategoryProductsPerPage',
				'CategoryListDepth',
				'ProductReviewsPerPage',
				'RSSItemsLimit',
				'RSSCacheTime',
				'EnableSEOUrls',
				'SystemLogMaxLength',
				'AdministratorLogMaxLength',
				'GuestCustomerGroup',
				'CategoryPerRow',
				'CategoryImageWidth',
				'CategoryImageHeight',
				'BrandPerRow',
				'BrandImageWidth',
				'BrandImageHeight',
				'TagCloudMinSize',
				'TagCloudMaxSize',
				'SearchResultsPerPage',
				'ProductImagesStorewideThumbnail_width',
				'ProductImagesStorewideThumbnail_height',
				'ProductImagesProductPageImage_width',
				'ProductImagesProductPageImage_height',
				'ProductImagesGalleryThumbnail_width',
				'ProductImagesGalleryThumbnail_height',
				'ProductImagesZoomImage_width',
				'ProductImagesZoomImage_height',
				'StartingOrderNumber',
				'CustomersAlsoViewedCount',
				'PCIPasswordMinLen',
				'PCIPasswordHistoryCount',
				'PCIPasswordExpiryTimeDay',
				'PCILoginAttemptCount',
				'PCILoginLockoutTimeMin',
				'PCILoginIdleTimeMin',
				'PCILoginInactiveTimeDay',
				'StoreHoursFromHours',
				'StoreHoursFromMinutes',
				'StoreHoursToHours',
				'StoreHoursToMinutes',
					
				//'LicenseTypeControl',
					
				'syncIWSintelisisstocktime',
					
				'DisplayCheckBoxLimit',
			);

			if(isset($_POST['syncIWSintelisisstocktime'])){
				$_POST['syncIWSintelisisstocktime'] = $_POST['syncIWSintelisisstocktime'] * 60;
			}
			
			foreach ($positive_ints as $var) {
				if (isset($_POST[$var]) && (int)$_POST[$var] > 0) {
					$GLOBALS['ISC_NEW_CFG'][$var] = (int)$_POST[$var];
				} else {
					$GLOBALS['ISC_NEW_CFG'][$var] = 0;
				}
			}
			
			if(isset($_POST['DisplayCheckBoxLimit']) && (int)$_POST['DisplayCheckBoxLimit'] >= 1) {
				$GLOBALS['ISC_NEW_CFG']['DisplayCheckBoxLimit'] = (int)$_POST['DisplayCheckBoxLimit'];
			}
			else {
				$GLOBALS['ISC_NEW_CFG']['DisplayCheckBoxLimit'] = 30;
			}
			if($_POST['selectStoreHoursAMPMFrom'] == 'PM') $GLOBALS['ISC_NEW_CFG']['StoreHoursFromHours'] += 12;
			if($_POST['selectStoreHoursAMPMTo'] == 'PM') $GLOBALS['ISC_NEW_CFG']['StoreHoursToHours'] += 12;

			$floats = array(
				'categoryFlyoutMouseOutDelay',
			);

			foreach ($floats as $var) {
				if (!isset($_POST[$var])) {
					$GLOBALS['ISC_NEW_CFG'][$var] = 0;
				}
				$GLOBALS['ISC_NEW_CFG'][$var] = (float)$_POST[$var];
			}

			$_SESSION['RunImageResize'] = 'no';
			if(isset($_POST['AutoResizeImages']) && $_POST['AutoResizeImages'] == 'yes') {
				$_SESSION['RunImageResize'] = 'yes';
			}

			// check the starting order number
			$currentAutoIncrement = (int)GetOrderTableAutoIncrement();
			$newAutoIncrement = (int)$_POST['StartingOrderNumber'];
			if($currentAutoIncrement != $newAutoIncrement) {
				// they've changed the starting order number
				// we need to make sure that it is not lower than any current order's ID tho

				$highestOrderId = GetHighestOrderNumber();
				if($newAutoIncrement <= $highestOrderId) {
					// new starting ID is too low
					$message = GetLang('StartingOrderNumberTooLow', array(
						'currentHighest' => $highestOrderId,
						'lowestPossible' => ($highestOrderId+1),
					));
					FlashMessage($message, MSG_ERROR, 'index.php?ToDo=viewSettings&currentTab='.((int) $_POST['currentTab']));
					die();
				}

				if(!UpdateOrderTableAutoIncrement($newAutoIncrement)) {
					FlashMessage(GetLang('StartingOrderNumberAlterFailed'), MSG_ERROR, 'index.php?ToDo=viewSettings&currentTab='.((int) $_POST['currentTab']));
					die();
				}
			}

			// check image size limits and cap them, check for invalid sizes and set them as defaults
			$imageSizes = array(
				'StorewideThumbnail' => ISC_PRODUCT_DEFAULT_IMAGE_SIZE_THUMBNAIL,
				'ProductPageImage' => ISC_PRODUCT_DEFAULT_IMAGE_SIZE_STANDARD,
				'GalleryThumbnail' => ISC_PRODUCT_DEFAULT_IMAGE_SIZE_TINY,
				'ZoomImage' => ISC_PRODUCT_DEFAULT_IMAGE_SIZE_ZOOM,
			);

			foreach ($imageSizes as $imageSizeKey => $imageSizeDefault) {
				$widthKey = 'ProductImages' . $imageSizeKey . '_width';
				$heightKey = 'ProductImages' . $imageSizeKey . '_height';

				if ($GLOBALS['ISC_NEW_CFG'][$widthKey] > ISC_PRODUCT_IMAGE_MAXLONGEDGE) {
					$GLOBALS['ISC_NEW_CFG'][$widthKey] = ISC_PRODUCT_IMAGE_MAXLONGEDGE;
				} else if ($GLOBALS['ISC_NEW_CFG'][$widthKey] < 1) {
					$GLOBALS['ISC_NEW_CFG'][$widthKey] = $imageSizeDefault;
				}

				if ($GLOBALS['ISC_NEW_CFG'][$heightKey] > ISC_PRODUCT_IMAGE_MAXLONGEDGE) {
					$GLOBALS['ISC_NEW_CFG'][$heightKey] = ISC_PRODUCT_IMAGE_MAXLONGEDGE;
				} else if ($GLOBALS['ISC_NEW_CFG'][$heightKey] < 1) {
					$GLOBALS['ISC_NEW_CFG'][$heightKey] = $imageSizeDefault;
				}

			}

			// Have there been any changes to the image sizes?
			// If there were no changes, don't even touch the images database
			$imageSizes = array(
				ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL => array(
					'ProductImagesStorewideThumbnail_width',
					'ProductImagesStorewideThumbnail_height',
				),
				ISC_PRODUCT_IMAGE_SIZE_STANDARD => array(
					'ProductImagesProductPageImage_width',
					'ProductImagesProductPageImage_height',
				),
				ISC_PRODUCT_IMAGE_SIZE_TINY => array(
					'ProductImagesGalleryThumbnail_width',
					'ProductImagesGalleryThumbnail_height',
				),
				ISC_PRODUCT_IMAGE_SIZE_ZOOM => array(
					'ProductImagesZoomImage_width',
					'ProductImagesZoomImage_height',
				),
			);

			// hacky :/
			$imageTimeChangedKeys = array(
				ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL => 'ProductImagesStorewideThumbnail_timeChanged',
				ISC_PRODUCT_IMAGE_SIZE_STANDARD => 'ProductImagesProductPageImage_timeChanged',
				ISC_PRODUCT_IMAGE_SIZE_TINY => 'ProductImagesGalleryThumbnail_timeChanged',
				ISC_PRODUCT_IMAGE_SIZE_ZOOM => 'ProductImagesZoomImage_timeChanged',
			);

			$changedDimensions = array();
			foreach($imageSizes as $size => $dimensionSettings) {
				foreach($dimensionSettings as $dimension) {
					if($GLOBALS['ISC_NEW_CFG'][$dimension] != $GLOBALS['ISC_CFG'][$dimension]) {
						$changedDimensions[$size] = $size;
						$GLOBALS['ISC_NEW_CFG'][$imageTimeChangedKeys[$size]] = time();
					}
				}
			}

			// product images used to be deleted here if the dimensions changed but this is now inside the image class
			// and is based off the _timeChanged above

			// check if the down for maintenance message is the same as the language pack
			if(Store_DownForMaintenance::getDownForMaintenanceMessage(true) == $_POST['DownForMaintenanceMessage'] || empty($_POST['DownForMaintenanceMessage'])) {
				$GLOBALS['ISC_NEW_CFG']['DownForMaintenanceMessage'] = '';

			} else if ($GLOBALS['ISC_NEW_CFG']['DownForMaintenance'] == 1) {
				$GLOBALS['ISC_NEW_CFG']['DownForMaintenanceMessage'] = $_POST['DownForMaintenanceMessage'];
			}

			// Normalize the shop path based on users redirect to www/no-www setting
			$shopPath = $_POST['ShopPath'];
			GetLib('class.redirects');
			$shopPath = ISC_REDIRECTS::normalizeShopPath($shopPath, (int)$_POST['RedirectWWW']);
			$GLOBALS['ISC_NEW_CFG']['ShopPath'] = $shopPath;

			$strings = array (
				'SharedSSLPath',
				'SubdomainSSLPath',
				'StoreName',
				'StoreAddress',
				'serverStamp',
				'DownloadDirectory',
				'ImageDirectory',
				'HomePagePageTitle',
				'MetaKeywords',
				'MetaDesc',
				'AdminEmail',
				'OrderEmail',
				'DisplayDateFormat',
				'ExportDateFormat',
				'ExtendedDisplayDateFormat',
				'GoogleMapsAPIKey',
				'ForwardInvoiceEmails',
				'HTTPProxyPort',
				'HTTPProxyServer',
				'DimensionsDecimalToken',
				'DimensionsThousandsToken',
				'DimensionsDecimalPlaces',
				'SessionSavePath',
				'DefaultPreOrderMessage',
				'FacebookLikeButtonAdminIds',
				'CategoryListStyle',
				'CheckoutExtraFieldName1',
				'CheckoutExtraFieldName2',
				'CheckoutExtraFieldName3',
				'CheckoutExtraFieldName4',
				'CheckoutExtraFieldName5',
				'CheckoutExtraFieldType1',
				'CheckoutExtraFieldType2',
				'CheckoutExtraFieldType3',
				'CheckoutExtraFieldType4',
				'CheckoutExtraFieldType5',
				'CheckoutExtraFieldValue1',
				'CheckoutExtraFieldValue2',
				'CheckoutExtraFieldValue3',
				'CheckoutExtraFieldValue4',
				'CheckoutExtraFieldValue5',

				'syncDropboxDir',
				'syncDropboxImagesDir',
				'syncFileNameInc',
				'syncFileNameOut',
				'syncPathToType',
				'syncTypeAtributeName',
			
				
			
				'syncIWSurl',
				'syncIWShost',
				'syncIWSport',
				'syncIWSdbname',
				'syncIWSdbuser',
				'syncIWSdbpass',
				'syncIWSintelisisuser',
				'syncIWSintelisispass',
				'syncIWSintelisisempresa',
				'syncIWSintelisissucursal',

				'syncArchiveDir',
					
				'UsersMountForTemplates',
			);

			// ignore this setting if it's posted by the client but should be hidden, otherwise process it
			if (!GetConfig('HideDeletedOrdersActionSetting')) {
				$strings[] = 'DeletedOrdersAction';
			}

			foreach ($strings as $var) {
				if (isset($_POST[$var]) && is_string($_POST[$var])) {
					$GLOBALS['ISC_NEW_CFG'][$var] = $_POST[$var];
				}
			}

			$enums = array (
				'UseSSL' => array(SSL_NONE, SSL_NORMAL, SSL_SHARED, SSL_SUBDOMAIN),
				'WeightMeasurement' => array ('LBS', 'KGS', 'Ounces', 'Grams', 'Tonnes'),
				'LengthMeasurement' => array ('Inches', 'Centimeters'),
				'StoreTimeZone' => array_keys($this->timezones),
				'Language' => $this->GetAvailableLanguagesArray(),
				'TagCartQuantityBoxes' => array ('dropdown', 'textbox'),
				'FastCartAction' => array('popup', 'cart'),
				'ProductImageMode' => array ('popup', 'lightbox'),
				'ProductBreadcrumbs' => array('showall', 'showone', 'shownone'),
				'CategoryListingMode' => array('single', 'emptychildren', 'children'),
				'CategoryDisplayMode' => array('grid', 'list'),
				'ShippingFactoringDimension' => array('depth', 'height', 'width'),
				'SearchDefaultProductSort' => array('relevance', 'alphaasc', 'alphadesc', 'featured', 'newest', 'bestselling', 'avgcustomerreview', 'priceasc', 'pricedesc'),
				'SearchDefaultContentSort' => array('relevance', 'alphaasc', 'alphadesc'),
				'SearchProductDisplayMode' => array('grid', 'list'),
				'SearchOptimisation' => array('fulltext', 'like', 'both'),
				'CharacterSet' => $this->validCharacterSets,
				'AbandonOrderLifetime' => array(1, 7, 14, 21, 30, 60, 90, 120, 150, 180),
				'RedirectWWW' => array(REDIRECT_NO_PREFERENCE, REDIRECT_TO_WWW, REDIRECT_TO_NO_WWW),
				'FacebookLikeButtonStyle' => array('standard', 'countonly'),
				'FacebookLikeButtonPosition' => array('above', 'below'),
				'FacebookLikeButtonVerb' => array('like', 'recommend'),
			);

			foreach ($enums as $var => $possible_vals) {
				if (isset($_POST[$var]) && in_array($_POST[$var], $possible_vals)) {
					$GLOBALS['ISC_NEW_CFG'][$var] = $_POST[$var];
				} else {
					$GLOBALS['ISC_NEW_CFG'][$var] = $possible_vals[0];
				}
			}

			$uploads = array(
				'CategoryDefaultImage',
				'BrandDefaultImage',
			);

			if($_POST['DefaultProductImage'] == 'custom') {
				$uploads[] = 'DefaultProductImageCustom';
			}

			foreach ($uploads as $var) {
				$imageLocation = GetConfig($var);

				if (array_key_exists($var, $_FILES) && file_exists($_FILES[$var]['tmp_name'])) {
					$ext = GetFileExtension($_FILES[$var]['name']);
					$imageLocation = GetConfig('ImageDirectory').'/' . $var . '.' . $ext;
					move_uploaded_file($_FILES[$var]['tmp_name'], ISC_BASE_PATH . '/'.$imageLocation);

					// Attempt to change the permissions on the file
					isc_chmod(ISC_BASE_PATH . '/'.$imageLocation, ISC_WRITEABLE_FILE_PERM);
				}

				if (array_key_exists('Del' . $var, $_REQUEST) && $_REQUEST['Del' . $var]) {
					@unlink(ISC_BASE_PATH . GetConfig($var));
					$imageLocation = '';
				}

				$GLOBALS['ISC_NEW_CFG'][$var] = $imageLocation;
			}

			switch($_POST['DefaultProductImage']) {
				case 'custom':
					if ($GLOBALS['ISC_NEW_CFG']['DefaultProductImageCustom'] != '') {
						$GLOBALS['ISC_NEW_CFG']['DefaultProductImage'] = $GLOBALS['ISC_NEW_CFG']['DefaultProductImageCustom'];
					}
					unset($GLOBALS['ISC_NEW_CFG']['DefaultProductImageCustom']);
					break;
				case 'template':
					$GLOBALS['ISC_NEW_CFG']['DefaultProductImage'] = 'template';
					break;
				default:
					$GLOBALS['ISC_NEW_CFG']['DefaultProductImage'] = '';
			}

			// Backup Settings
			if (gzte11(ISC_MEDIUMPRINT)) {
				$boolean = array (
					'BackupsLocal',
					'BackupsRemoteFTP',
					'BackupsAutomatic',
					'BackupsAutomaticDatabase',
					'BackupsAutomaticImages',
					'BackupsAutomaticDownloads',
				);

				foreach ($boolean as $var) {
					if (isset($_POST[$var]) && ($_POST[$var] == 1 || $_POST[$var] === "ON")) {
						$GLOBALS['ISC_NEW_CFG'][$var] = 1;
					} else {
						$GLOBALS['ISC_NEW_CFG'][$var] = 0;
					}
				}

				$strings = array (
					'BackupsRemoteFTPHost',
					'BackupsRemoteFTPUser',
					'BackupsRemoteFTPPass',
					'BackupsRemoteFTPPath',
				);

				foreach ($strings as $var) {
					if (isset($_POST[$var]) && is_string($_POST[$var])) {
						$GLOBALS['ISC_NEW_CFG'][$var] = $_POST[$var];
					}
				}

				$enums = array (
					'BackupsAutomaticMethod' => array ('ftp', 'local'),
				);

				foreach ($enums as $var => $possible_vals) {
					if (isset($_POST[$var]) && in_array($_POST[$var], $possible_vals)) {
						$GLOBALS['ISC_NEW_CFG'][$var] = $_POST[$var];
					} else {
						$GLOBALS['ISC_NEW_CFG'][$var] = $possible_vals[0];
					}
				}
			}

			// Newsletter Settings
			if (isset($_POST['SystemLogTypes'])) {
				$GLOBALS['ISC_NEW_CFG']['SystemLogTypes'] = implode(",", $_POST['SystemLogTypes']);
			} else {
				$GLOBALS['ISC_NEW_CFG']['SystemLogTypes'] = '';
			}

			if (isset($_POST['SystemLogSeverity'])) {
				$GLOBALS['ISC_NEW_CFG']['SystemLogSeverity'] = implode(",", $_POST['SystemLogSeverity']);
			} else {
				$GLOBALS['ISC_NEW_CFG']['SystemLogSeverity'] = '';
			}

			if(isset($_POST['LowInventoryEmails']) && $_POST['LowInventoryEmails'] == 1) {
				$GLOBALS['ISC_NEW_CFG']['LowInventoryNotificationAddress'] = $_POST['LowInventoryNotificationAddress'];
			}
			else {
				$GLOBALS['ISC_NEW_CFG']['LowInventoryNotificationAddress'] = '';
			}

			if(isset($_POST['ForwardInvoiceEmailsCheck']) && $_POST['ForwardInvoiceEmailsCheck'] == 1) {
				$GLOBALS['ISC_NEW_CFG']['ForwardInvoiceEmails'] = $_POST['ForwardInvoiceEmails'];
			}
			else {
				$GLOBALS['ISC_NEW_CFG']['ForwardInvoiceEmails'] = '';
			}

			// Email Server Settings
			$GLOBALS['ISC_NEW_CFG']['MailUseSMTP'] = 0;
			$GLOBALS['ISC_NEW_CFG']['MailSMTPServer'] = '';
			$GLOBALS['ISC_NEW_CFG']['MailSMTPUsername'] = '';
			$GLOBALS['ISC_NEW_CFG']['MailSMTPPassword'] = '';
			$GLOBALS['ISC_NEW_CFG']['MailSMTPPort'] = '';

			if(isset($_POST['MailUseSMTP']) && $_POST['MailUseSMTP'] == 1) {
				$GLOBALS['ISC_NEW_CFG']['MailUseSMTP'] = 1;

				$GLOBALS['ISC_NEW_CFG']['MailSMTPServer'] = $_POST['MailSMTPServer'];
				if(isset($_POST['MailSMTPUsername'])) {
					$GLOBALS['ISC_NEW_CFG']['MailSMTPUsername'] = $_POST['MailSMTPUsername'];
				}
				if(isset($_POST['MailSMTPPassword'])) {
					$GLOBALS['ISC_NEW_CFG']['MailSMTPPassword'] = $_POST['MailSMTPPassword'];
				}
				if(isset($_POST['MailSMTPPort'])) {
					$GLOBALS['ISC_NEW_CFG']['MailSMTPPort'] = $_POST['MailSMTPPort'];
				}
			}

			if(isset($_POST['VendorPhotoUploading'])) {
				$GLOBALS['ISC_NEW_CFG']['VendorPhotoSize'] = (int)$_POST['VendorPhotoSizeW'].'x'.(int)$_POST['VendorPhotoSizeH'];
			}
			else {
				$GLOBALS['ISC_NEW_CFG']['VendorPhotoSize'] = '';
			}

			if(isset($_POST['VendorLogoUploading'])) {
				$GLOBALS['ISC_NEW_CFG']['VendorLogoSize'] = (int)$_POST['VendorLogoSizeW'].'x'.(int)$_POST['VendorLogoSizeH'];
			}
			else {
				$GLOBALS['ISC_NEW_CFG']['VendorLogoSize'] = '';
			}

			// Remove any settings that have been disabled so they can't be adjusted by the end user
			$disabledFields = array(
				'DisableLicenseKeyField' => array(
					'serverStamp'
				),
				'DisableStoreUrlField' => array(
					'ShopPath'
				),
				'DisablePathFields' => array(
					'DownloadDirectory',
					'ImageDirectory'
				),
				'DisableLoggingSettingsTab' => array(
					'SystemLogging',
					'HidePHPErrors',
					'SystemLogTypes',
					'SystemLogSeverity',
					'SystemLogMaxLength',
					'AdministratorLogging',
					'AdministratorLogMaxLength'
				),
				'DisableProxyFields' => array(
					'HTTPProxyServer',
					'HTTPProxyPort',
					'HTTPSSLVerifyPeer'
				),
				'DisableBackupSettings' => array(
					'BackupsLocal',
					'BackupsRemoteFTP',
					'BackupsRemoteFTPHost',
					'BackupsRemoteFTPUser',
					'BackupsRemoteFTPPass',
					'BackupsRemoteFTPPath',
					'BackupsAutomatic',
					'BackupsAutomaticMethod',
					'BackupsAutomaticDatabase',
					'BackupsAutomaticImages',
					'BackupsAutomaticDownloads'
				),
				'HidePCISettings' => array(
					'PCIPasswordMinLen',
					'PCIPasswordHistoryCount',
					'PCIPasswordExpiryTimeDay',
					'PCILoginAttemptCount',
					'PCILoginLockoutTimeMin',
					'PCILoginIdleTimeMin',
					'PCILoginInactiveTimeDay'
				)
			);

			foreach($disabledFields as $setting => $fields) {
				if(GetConfig($setting) == true) {
					foreach($fields as $field) {
						unset($GLOBALS['ISC_NEW_CFG'][$field]);
					}
				}
			 }

			$messages = array();

			if ($this->CommitSettings($messages)) {
				$redirectUrl = 'index.php?ToDo=viewSettings&currentTab='.(int)$_POST['currentTab'];

				// Mark this step as complete in getting started
				if(GetClass('ISC_ADMIN_ENGINE')->MarkGettingStartedComplete('settings')) {
					$redirectUrl = 'index.php';
				}

				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();
				FlashMessage(GetLang('SettingsSavedSuccessfully'), MSG_SUCCESS, $redirectUrl);
			} else {
				FlashMessage(sprintf(GetLang('SettingsNotSaved'), $messages), MSG_ERROR, 'index.php?ToDo=viewSettings&currentTab='.((int) $_POST['currentTab']));
			}
		}

		public function CommitSettings(&$messages=array())
		{
			// If the shop path has changed normalize it and set the app path too
			if (isset($GLOBALS['ISC_NEW_CFG']['ShopPath']) && $GLOBALS['ISC_NEW_CFG']['ShopPath'] != GetConfig('ShopPath')) {
				$parsedPath = ParseShopPath($GLOBALS['ISC_NEW_CFG']['ShopPath']);
				$GLOBALS['ISC_NEW_CFG']['ShopPath'] = $parsedPath['shopPath'];

				// add an event to resubscribe to ebay notifications since the notification url will now be different
				if (ISC_ADMIN_EBAY::checkEbayConfig()) {
					Interspire_Event::bind('settings_updated', array('ISC_ADMIN_EBAY', 'resubscribeNotifications'));
				}
			}

			// normalize our shared ssl path
			if (isset($GLOBALS['ISC_NEW_CFG']['SharedSSLPath']) && trim($GLOBALS['ISC_NEW_CFG']['SharedSSLPath'])) {
				$ssl_path_parts = parse_url($GLOBALS['ISC_NEW_CFG']['SharedSSLPath']);

				if (!isset($ssl_path_parts['path'])) {
					$ssl_path_parts['path'] = '';
				}
				$ssl_path_parts['path'] = rtrim($ssl_path_parts['path'], '/');

				// Workout the Shop Path
				$GLOBALS['ISC_NEW_CFG']['SharedSSLPath'] = 'https://' . $ssl_path_parts['host'];
				if (isset($ssl_path_parts['port']) && $ssl_path_parts['port'] != '443') {
					$GLOBALS['ISC_NEW_CFG']['SharedSSLPath'] .= ':'.$ssl_path_parts['port'];
				}
				$GLOBALS['ISC_NEW_CFG']['SharedSSLPath'] .= $ssl_path_parts['path'];
			}

			// normalize our subdomain ssl path
			if (isset($GLOBALS['ISC_NEW_CFG']['SubdomainSSLPath']) && trim($GLOBALS['ISC_NEW_CFG']['SubdomainSSLPath'])) {
				$ssl_path_parts = parse_url($GLOBALS['ISC_NEW_CFG']['SubdomainSSLPath']);

				if (!isset($ssl_path_parts['path'])) {
					$ssl_path_parts['path'] = '';
				}
				$ssl_path_parts['path'] = rtrim($ssl_path_parts['path'], '/');

				// Workout the Shop Path
				$GLOBALS['ISC_NEW_CFG']['SubdomainSSLPath'] = 'https://' . $ssl_path_parts['host'];
				if (isset($ssl_path_parts['port']) && $ssl_path_parts['port'] != '443') {
					$GLOBALS['ISC_NEW_CFG']['SubdomainSSLPath'] .= ':'.$ssl_path_parts['port'];
				}
				$GLOBALS['ISC_NEW_CFG']['SubdomainSSLPath'] .= $ssl_path_parts['path'];
			}

			if (!isset($GLOBALS['ISC_NEW_CFG'])) {
				$GLOBALS['ISC_NEW_CFG'] = array();
			}

			$directories = array(
				'ImageDirectory' => 'product_images',
				'DownloadDirectory' => 'product_downloads'
			);
			foreach($directories as $directory => $default) {
				if(isset($GLOBALS['ISC_NEW_CFG'][$directory])) {
					$newDirectory = ISC_BASE_PATH.'/'.$GLOBALS['ISC_NEW_CFG'][$directory];
					if(!$GLOBALS['ISC_NEW_CFG'][$directory] || !is_dir($newDirectory)) {
						$GLOBALS['ISC_NEW_CFG'][$directory] = $default;
					}
				}
			}

			// Wht we're doing here is we copy the current configuration in to another variable and
			// then load the store configuration file again. We do this to prevent any settings
			// that may have temporarily been modified in memory from propagating to the configuration
			// file. We then revert back to the in memory settings
			$memoryConfig = $GLOBALS['ISC_CFG'];
			unset($GLOBALS['ISC_CFG']);
			require ISC_CONFIG_FILE;
			$originalConfig = $GLOBALS['ISC_CFG'];
			$GLOBALS['ISC_CFG'] = $memoryConfig;
			$GLOBALS['ISC_SAVE_CFG'] = array_merge($originalConfig, $GLOBALS['ISC_NEW_CFG']);
			// Save the var_exported vars in the globals array temporarily for saving
			foreach ($this->all_vars as $var) {
				if (!array_key_exists($var, $GLOBALS['ISC_SAVE_CFG'])) {
					if(array_key_exists($var, $memoryConfig)) {
						$GLOBALS[$var] = var_export($memoryConfig[$var], true);
					} else {
						$GLOBALS[$var] = "null";
					}
				} else {
					$GLOBALS[$var] = var_export($GLOBALS['ISC_SAVE_CFG'][$var], true);
				}
			}

			$config_data = $this->template->render('config.file.tpl');

			$setting_string = "<" . "?php\n\n";
			$setting_string .= "\t// Last Updated: ".isc_date("jS M Y @ g:i A") . "\n";
			$setting_string .= $config_data;

			if (!defined("ISC_CONFIG_FILE") || !defined("ISC_CONFIG_BACKUP_FILE")) {
				die("Config sanity check failed");
			}

			// Try to copy the current config file to a backup file
			if (!@copy(ISC_CONFIG_FILE, ISC_CONFIG_BACKUP_FILE)) {
				isc_chmod(ISC_CONFIG_BACKUP_FILE, ISC_WRITEABLE_FILE_PERM);
				$messages = array(GetLang('CouldntBackupConfig') => MSG_INFO);
			}

			// Try to write to the config file
			if (!is_writable(ISC_CONFIG_FILE)) {
				$this->error = GetLang('CouldntSaveConfig');
				return false;
			}

			if (!($fp = @fopen(ISC_CONFIG_FILE, "wb+"))) {
				$this->error = GetLang('CouldntSaveConfig');
				return false;
			}

			if (@fwrite($fp, $setting_string)) {
				fclose($fp);
				$prevCatListDepth = GetConfig('CategoryListDepth');
				// Include the config file again to initialize the new values
				include(ISC_CONFIG_FILE);

				if (isset($GLOBALS['ISC_NEW_CFG']['CategoryListDepth']) && $GLOBALS['ISC_NEW_CFG']['CategoryListDepth'] != $prevCatListDepth) {
					$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateRootCategories();
				}

				// trigger any events that should run after the settings have been saved
				Interspire_Event::trigger('settings_updated');

				return true;
			}

			fclose($fp);
			$this->error = GetLang('CouldntSaveConfig');
			return false;
		}


		private function ManageSettings($messages=array())
		{
			if(!gzte11(ISC_HUGEPRINT)) {
				$GLOBALS['HideVendorOptions'] = 'display: none';
			}

			$GLOBALS['Message'] = GetFlashMessageBoxes();

			// Get the getting started box if we need to
			$GLOBALS['GettingStartedStep'] = '';
			if(empty($GLOBALS['Message']) && (isset($_GET['wizard']) && $_GET['wizard']==1) && !in_array('settings', GetConfig('GettingStartedCompleted')) && !GetConfig('DisableGettingStarted')) {
				$GLOBALS['GettingStartedTitle'] = GetLang('ConfigureStoreSettings');
				$GLOBALS['GettingStartedContent'] = GetLang('ConfigureStoreSettingsDesc');
				$GLOBALS['GettingStartedStep'] = $this->template->render('Snippets/GettingStartedModal.html');
			}

			if (GetConfig('UseWYSIWYG')) {
				$GLOBALS['IsWYSIWYGEnabled'] = 'checked="checked"';
			}

			if (GetConfig('ShowThumbsInControlPanel')) {
				$GLOBALS['IsProductThumbnailsEnabled'] = 'checked="checked"';
			}

			if (GetConfig('DesignMode')) {
				$GLOBALS['IsDesignMode'] = 'checked="checked"';
			}
			
			if (GetConfig('AccountCreationInactiveUsers')) {
				$GLOBALS['AccountCreationInactiveUsersChecked'] = 'checked="checked"';
			}
						
			if (GetConfig('ForcePasswordChangeNewUsers')) {
				$GLOBALS['ForcePasswordChangeNewUsersChecked'] = 'checked="checked"';
			}
						
			if (GetConfig('ShowProductBrandImage')) {
				$GLOBALS['ShowProductBrandImageChecked'] = 'checked="checked"';
			}
						
			
			if (GetConfig('UseStoreOriginForStock')) {
				$GLOBALS['UseStoreOriginForStockChecked'] = 'checked="checked"';
			}

			if (GetConfig('ForceControlPanelSSL')) {
				$GLOBALS['IsControlPanelSSLEnabled'] = 'checked="checked"';
			}

			if (GetConfig('DownForMaintenance')) {
				$GLOBALS['IsDownForMaintenance'] = 'checked="checked"';
			}

			if (GetConfig('UseStoreHours')) {
				$GLOBALS['UseStoreHoursChecked'] = 'checked="checked"';
			}

			if (GetConfig('StoreClosed')) {
				$GLOBALS['StoreClosedChecked'] = 'checked="checked"';
			}
			
			if($GLOBALS['ISC_CFG']['StoreHoursFromHours'] > 12){
				$GLOBALS['ISC_CFG']['StoreHoursFromHours'] -= 12;
				$GLOBALS['PMFromSelected'] = "selected=\"selected\"";
			}
			else $GLOBALS['AMFromSelected'] = "selected=\"selected\"";
			
			if (is_int(GetConfig('DisplayCheckBoxLimit'))) {
				$GLOBALS['DisplayCheckBoxLimit'] = GetConfig('DisplayCheckBoxLimit');
			}
			
			if($GLOBALS['ISC_CFG']['StoreHoursToHours'] > 12){
				$GLOBALS['ISC_CFG']['StoreHoursToHours'] -= 12;
				$GLOBALS['PMToSelected'] = "selected=\"selected\"";
			}
			else $GLOBALS['AMToSelected'] = "selected=\"selected\"";
			
			$GLOBALS['selectHoursFrom'] = $this->CreateSelectNumeric("StoreHoursFromHours", 1, 12, 1, $GLOBALS['ISC_CFG']['StoreHoursFromHours']);
			$GLOBALS['selectMinutesFrom'] = $this->CreateSelectNumeric("StoreHoursFromMinutes", 00, 55, 5, $GLOBALS['ISC_CFG']['StoreHoursFromMinutes']);
			$GLOBALS['selectHoursTo'] = $this->CreateSelectNumeric("StoreHoursToHours", 1, 12, 1, $GLOBALS['ISC_CFG']['StoreHoursToHours']);
			$GLOBALS['selectMinutesTo'] = $this->CreateSelectNumeric("StoreHoursToMinutes", 00, 55, 5, $GLOBALS['ISC_CFG']['StoreHoursToMinutes']);
			
			if (GetConfig('CheckoutUseExtraFields')) {
				$GLOBALS['CheckoutUseExtraFieldsChecked'] = 'checked="checked"';
			}
			
			if (GetConfig('CheckoutExtraFieldActive1')) {
				$GLOBALS['CheckoutExtraFieldActive1Checked'] = 'checked="checked"';
			}
				
			if (GetConfig('CheckoutExtraFieldActive2')) {
				$GLOBALS['CheckoutExtraFieldActive2Checked'] = 'checked="checked"';
			}
				
			if (GetConfig('CheckoutExtraFieldActive3')) {
				$GLOBALS['CheckoutExtraFieldActive3Checked'] = 'checked="checked"';
			}
				
			if (GetConfig('CheckoutExtraFieldActive4')) {
				$GLOBALS['CheckoutExtraFieldActive4Checked'] = 'checked="checked"';
			}
				
			if (GetConfig('CheckoutExtraFieldActive5')) {
				$GLOBALS['CheckoutExtraFieldActive5Checked'] = 'checked="checked"';
			}

			if (GetConfig('CheckoutExtraFieldRequired1')) {
				$GLOBALS['CheckoutExtraFieldRequired1Checked'] = 'checked="checked"';
			}
				
			if (GetConfig('CheckoutExtraFieldRequired2')) {
				$GLOBALS['CheckoutExtraFieldRequired2Checked'] = 'checked="checked"';
			}
				
			if (GetConfig('CheckoutExtraFieldRequired3')) {
				$GLOBALS['CheckoutExtraFieldRequired3Checked'] = 'checked="checked"';
			}
				
			if (GetConfig('CheckoutExtraFieldRequired4')) {
				$GLOBALS['CheckoutExtraFieldRequired4Checked'] = 'checked="checked"';
			}
				
			if (GetConfig('CheckoutExtraFieldRequired5')) {
				$GLOBALS['CheckoutExtraFieldRequired5Checked'] = 'checked="checked"';
			}
			
			if (GetConfig('isIntelisis')) {
				$GLOBALS['isIntelisisChecked'] = 'checked="checked"';
				$GLOBALS['hideIntelisisTab'] = 'inline';
			}
			else {
				$GLOBALS['isIntelisisChecked'] = '';
				$GLOBALS['HideIntelisisTab'] = 'none';
			}
			if (GetConfig('syncDropboxActive')) {
				$GLOBALS['syncDropboxActiveChecked'] = 'checked="checked"';
			}
			if (GetConfig('syncDropboxOffline')) {
				$GLOBALS['syncDropboxOfflineChecked'] = 'checked="checked"';
			}
			
			$GLOBALS["CharacterSet"] = GetConfig('CharacterSet');

			if(in_array(GetConfig('CharacterSet'), $this->validCharacterSets)) {
				$selectedCharset = GetConfig('CharacterSet');
				$selectedCharset = isc_strtolower($selectedCharset);
				$selectedCharset = str_replace(array("-", "_"), "", $selectedCharset);
				$GLOBALS["CharacterSet_Selected_" . $selectedCharset] = 'selected="selected"';
			} else {
				$GLOBALS["CharacterSet_Selected_utf8"] = 'selected="selected"';
			}

			/*
			if (GetConfig('UseSSL')) {
				$GLOBALS['IsSSLEnabled'] = 'checked="checked"';
			}
			*/
			switch (GetConfig('UseSSL')) {
				case SSL_NORMAL:
					$SSLOption = "UseNormalSSL";
					break;
				case SSL_SHARED:
					$SSLOption = "UseSharedSSL";
					break;
				case SSL_SUBDOMAIN:
					$SSLOption = "UseSubdomainSSL";
					break;
				default:
					$SSLOption = "NoSSL";
			}

			$GLOBALS[$SSLOption . 'Checked'] = 'checked="checked"';

			if(GetConfig('AllowPurchasing')) {
				$GLOBALS['IsPurchasingEnabled'] = 'checked="checked"';
			}

			switch(GetConfig('WeightMeasurement')) {
				case 'LBS':
					$GLOBALS['IsPounds'] = 'selected="selected"';
					break;
				case 'Ounces':
					$GLOBALS['IsOunces'] = 'selected="selected"';
					break;
				case 'KGS':
					$GLOBALS['IsKilos'] = 'selected="selected"';
					break;
				case 'Grams':
					$GLOBALS['IsGrams'] = 'selected="selected"';
					break;
				case 'Tonnes':
					$GLOBLAS['IsTonnes'] = 'selected="selected"';
			}

			if (GetConfig('LengthMeasurement') == "Inches") {
				$GLOBALS['IsInches'] = 'selected="selected"';
			} else {
				$GLOBALS['IsCentimeters'] = 'selected="selected"';
			}

			$GLOBALS['ShippingFactoringDimensionDepthSelected'] = '';
			$GLOBALS['ShippingFactoringDimensionHeightSelected'] = '';
			$GLOBALS['ShippingFactoringDimensionWidthSelected'] = '';

			switch (GetConfig('ShippingFactoringDimension')) {
				case 'height':
					$GLOBALS['ShippingFactoringDimensionHeightSelected'] = 'selected="selected"';
					break;
				case 'width':
					$GLOBALS['ShippingFactoringDimensionWidthSelected'] = 'selected="selected"';
					break;
				case 'depth':
				default:
					$GLOBALS['ShippingFactoringDimensionDepthSelected'] = 'selected="selected"';
					break;
			}

			if (GetConfig('TagCartQuantityBoxes') == 'dropdown') {
				$GLOBALS['IsDropdown'] = 'selected="selected"';
			} else {
				$GLOBALS['IsTextbox'] = 'selected="selected"';
			}

			// Product breadcrumbs dropdown
			$GLOBALS['ProductBreadcrumbs'] = GetConfig('ProductBreadcrumbs');
			$GLOBALS['ProductBreadcrumbOptions'] = array(
				"showall" => GetLang('ShowAll'),
				"showone" => GetLang('ShowOneOnly'),
				"shownone" => GetLang('DontShow'),
			);

			if (GetConfig('FastCartAction') == 'popup') {
				$GLOBALS['IsShowPopWindow'] = 'selected="selected"';
			} else {
				$GLOBALS['IsShowCartPage'] = 'selected="selected"';
			}

			if (GetConfig('TagCloudsEnabled')) {
				$GLOBALS['IsTagCloudsEnabled'] = 'checked="checked"';
			}

			if (GetConfig('BulkDiscountEnabled')) {
				$GLOBALS['IsBulkDiscountEnabled'] = 'checked="checked"';
			}

			if (GetConfig('EnableProductTabs')) {
				$GLOBALS['IsProductTabsEnabled'] = 'checked="checked"';
			}

			if (GetConfig('ShowAddToCartQtyBox')) {
				$GLOBALS['IsShownAddToCartQtyBox'] = 'checked="checked"';
			}

			if (GetConfig('CaptchaEnabled')) {
				$GLOBALS['IsCaptchaEnabled'] = 'checked="checked"';
			}

			if(GetConfig('StoreDSTCorrection')) {
				$GLOBALS['IsDSTCorrectionEnabled'] = "checked=\"checked\"";
			}

			if (GetConfig('ShowCartSuggestions')) {
				$GLOBALS['IsShowCartSuggestions'] = 'checked="checked"';
			}

			if (GetConfig('ShowThumbsInCart')) {
				$GLOBALS['IsShowThumbsInCart'] = 'checked="checked"';
			}

			if (GetConfig('TagCloudsEnabled')) {
				$GLOBALS['IsTagCloudsEnabled'] = 'checked="checked"';
			}

			if (GetConfig('ShowAddToCartQtyBox')) {
				$GLOBALS['IsShownAddToCartQtyBox'] = 'checked="checked"';
			}

			if (GetConfig('AutoApproveReviews')) {
				$GLOBALS['IsAutoApproveReviews'] = 'checked="checked"';
			}

			if (GetConfig('SearchSuggest')) {
				$GLOBALS['IsSearchSuggest'] = 'checked="checked"';
			}

			if (GetConfig('QuickSearch')) {
				$GLOBALS['IsQuickSearch'] = 'checked="checked"';
			}

			if (GetConfig('ShowInventory')) {
				$GLOBALS['IsShowInventory'] = 'checked="checked"';
			}

			if (GetConfig('ShowPreOrderInventory')) {
				$GLOBALS['IsShowPreOrderInventory'] = 'checked="checked"';
			}

			// Bulk Discount Settings
			if (GetConfig('BulkDiscountEnabled')) {
				$GLOBALS['IsBulkDiscountEnabled'] = 'checked="checked"';
			}

			if (GetConfig('EnableProductTabs')) {
				$GLOBALS['IsProductTabsEnabled'] = 'checked="checked"';
			}

			// RSS Settings
			if (GetConfig('RSSNewProducts')) {
				$GLOBALS['IsRSSNewProductsEnabled'] = 'checked="checked"';
			}

			if (GetConfig('RSSPopularProducts')) {
				$GLOBALS['IsRSSPopularProductsEnabled'] = 'checked="checked"';
			}

			if (GetConfig('RSSFeaturedProducts')) {
				$GLOBALS['IsRSSFeaturedProductsEnabled'] = 'checked="checked"';
			}

			if (GetConfig('RSSCategories')) {
				$GLOBALS['IsRSSCategoriesEnabled'] = 'checked="checked"';
			}

			if (GetConfig('RSSProductSearches')) {
				$GLOBALS['IsRSSProductSearchesEnabled'] = 'checked="checked"';
			}

			if (GetConfig('RSSLatestBlogEntries')) {
				$GLOBALS['IsRSSLatestBlogEntriesEnabled'] = 'checked="checked"';
			}

			if (GetConfig('RSSSyndicationIcons')) {
				$GLOBALS['IsRSSSyndicationIconsEnabled'] = 'checked="checked"';
			}

			if(GetConfig('EnableCustomersAlsoViewed')) {
				$GLOBALS['IsCustomersAlsoViewedEnabled'] = 'checked="checked"';
			}

			// Product Images
			if (GetConfig('ProductImagesTinyThumbnailsEnabled')) {
				$GLOBALS['IsProductImagesTinyThumbnailsEnabled'] = 'checked="checked"';
			}

			if(GetConfig('ProductImagesImageZoomEnabled')) {
				$GLOBALS['IsProductImagesImageZoomEnabled'] = 'checked="checked"';
			}

			if((int)GetConfig('ProductImagesStorewideThumbnail_width') < 1) {
				$GLOBALS['ISC_CFG']['ProductImagesStorewideThumbnail_width'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_THUMBNAIL;
			}

			if((int)GetConfig('ProductImagesStorewideThumbnail_height') < 1) {
				$GLOBALS['ISC_CFG']['ProductImagesStorewideThumbnail_height'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_THUMBNAIL;
			}

			if((int)GetConfig('ProductImagesProductPageImage_width') < 1) {
				$GLOBALS['ISC_CFG']['ProductImagesProductPageImage_width'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_STANDARD;
			}

			if((int)GetConfig('ProductImagesProductPageImage_height') < 1) {
				$GLOBALS['ISC_CFG']['ProductImagesProductPageImage_height'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_STANDARD;
			}

			if((int)GetConfig('ProductImagesGalleryThumbnail_width') < 1) {
				$GLOBALS['ISC_CFG']['ProductImagesGalleryThumbnail_width'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_TINY;
			}

			if((int)GetConfig('ProductImagesGalleryThumbnail_height') < 1) {
				$GLOBALS['ISC_CFG']['ProductImagesGalleryThumbnail_height'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_TINY;
			}

			// Backup Settings
			if (GetConfig('BackupsLocal')) {
				$GLOBALS['IsBackupsLocalEnabled'] = 'checked="checked"';
			}

			if (GetConfig('BackupsRemoteFTP')) {
				$GLOBALS['IsBackupsRemoteFTPEnabled'] = 'checked="checked"';
			}

			if (GetConfig('BackupsAutomatic')) {
				$GLOBALS['IsBackupsAutomaticEnabled'] = 'checked="checked"';
			}

			if (GetConfig('HTTPSSLVerifyPeer')) {
				$GLOBALS['IsHTTPSSLVerifyPeerEnabled'] = 'checked="checked"';
			}

			if (strpos(strtolower(PHP_OS), 'win') === 0) {
				$binary = 'php.exe';
				$path_to_php = Which($binary);
			} else {
				// Check if there is a separate PHP 5 binary first
				foreach(array('php5', 'php') as $phpBin) {
					$path_to_php = Which($phpBin);
					if($path_to_php !== '') {
						break;
					}
				}
			}

			if ($path_to_php === '' && strpos(strtolower(PHP_OS), 'win') === 0) {
				$path_to_php = 'php.exe';
			} elseif ($path_to_php === '') {
				$path_to_php = 'php';
			}

			$GLOBALS['BackupsAutomaticPath'] = $path_to_php.' -f ' . realpath(ISC_BASE_PATH . "/admin")."/cron-backup.php";

			if (GetConfig('BackupsAutomaticMethod') == "ftp") {
				$GLOBALS['IsBackupsAutomaticMethodFTP'] = 'selected="selected"';
			} else {
				$GLOBALS['IsBackupsAutomaticMethodLocal'] = 'selected="selected"';
			}

			if (GetConfig('BackupsAutomaticDatabase')) {
				$GLOBALS['IsBackupsAutomaticDatabaseEnabled'] = 'checked="checked"';
			}

			if (GetConfig('BackupsAutomaticImages')) {
				$GLOBALS['IsBackupsAutomaticImagesEnabled'] = 'checked="checked"';
			}

			if (GetConfig('BackupsAutomaticDownloads')) {
				$GLOBALS['IsBackupsAutomaticDownloadsEnabled'] = 'checked="checked"';
			}

			$GLOBALS['LanguageOptions'] = $this->GetLanguageOptions(GetConfig('Language'));

			if (!function_exists('ftp_connect')) {
				$GLOBALS['FTPBackupsHide'] = "none";
			}

			$GLOBALS['TimeZoneOptions'] = $this->GetTimeZoneOptions(GetConfig('StoreTimeZone'));

			$query = sprintf("select version() as version");
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			$GLOBALS['dbVersion'] = $row['version'];

			// Logging Settings
			if (GetConfig('SystemLogging')) {
				$GLOBALS['IsSystemLoggingEnabled'] = "checked=\"checked\"";
			}

			if(GetConfig('DebugMode')) {
				$GLOBALS['IsDebugModeEnabled'] = "checked=\"checked\"";
			}

			if (GetConfig('SystemLogTypes')) {
				$types = explode(",", GetConfig('SystemLogTypes'));
				if (in_array('general', $types)) {
					$GLOBALS['IsGeneralLoggingEnabled'] = "selected=\"selected\"";
				}
				if (in_array('payment', $types)) {
					$GLOBALS['IsPaymentLoggingEnabled'] = "selected=\"selected\"";
				}
				if (in_array('shipping', $types)) {
					$GLOBALS['IsShippingLoggingEnabled'] = "selected=\"selected\"";
				}
				if (in_array('notification', $types)) {
					$GLOBALS['IsNotificationLoggingEnabled'] = "selected=\"selected\"";
				}
				if (in_array('sql', $types)) {
					$GLOBALS['IsSQLLoggingEnabled'] = "selected=\"selected\"";
				}
				if (in_array('php', $types)) {
					$GLOBALS['IsPHPLoggingEnabled'] = "selected=\"selected\"";
				}
				if (in_array('accounting', $types)) {
					$GLOBALS['IsAccountingLoggingEnabled'] = "selected=\"selected\"";
				}
				if (in_array('emailintegration', $types)) {
					$GLOBALS['IsEmailIntegrationLoggingEnabled'] = "selected=\"selected\"";
				}
				if (in_array('ebay', $types)) {
					$GLOBALS['IsEbayLoggingEnabled'] = "selected=\"selected\"";
				}
				if (in_array('shoppingcomparison', $types)) {
					$GLOBALS['IsShoppingComparisonLoggingEnabled'] = "selected=\"selected\"";
				}
			}

			if (GetConfig('SystemLogSeverity')) {
				$severities = explode(",", GetConfig('SystemLogSeverity'));
				if (in_array('errors', $severities)) {
					$GLOBALS['IsLoggingSeverityErrors'] = "selected=\"selected\"";
				}
				if (in_array('warnings', $severities)) {
					$GLOBALS['IsLoggingSeverityWarnings'] = "selected=\"selected\"";
				}
				if (in_array('notices', $severities)) {
					$GLOBALS['IsLoggingSeverityNotices'] = "selected=\"selected\"";
				}
				if (in_array('success', $severities)) {
					$GLOBALS['IsLoggingSeveritySuccesses'] = "selected=\"selected\"";
				}
				if (in_array('debug', $severities)) {
					$GLOBALS['IsLoggingSeverityDebug'] = "selected=\"selected\"";
				}
			}


			if (GetConfig('EnableSEOUrls') == 2) {
				$GLOBALS['IsEnableSEOUrlsAuto'] = "selected=\"selected\"";
			}
			else if (GetConfig('EnableSEOUrls') == 1) {
				$GLOBALS['IsEnableSEOUrlsEnabled'] = "selected=\"selected\"";
			}
			else {
				$GLOBALS['IsEnableSEOUrlsDisabled'] = "selected=\"selected\"";
			}

			if (!gzte11(ISC_MEDIUMPRINT)) {
				$GLOBALS['HideBackupSettings'] = "none";
			}

			if (GetConfig('AdministratorLogging')) {
				$GLOBALS['IsAdministratorLoggingEnabled'] = "checked=\"checked\"";
			}

			if(GetConfig('HidePHPErrors')) {
				$GLOBALS['IsHidePHPErrorsEnabled'] = "checked=\"checked\"";
			}

			if(GetConfig('EnableWishlist')) {
				$GLOBALS['IsWishlistEnabled'] = "checked=\"checked\"";
			}

			if(GetConfig('EnableAccountCreation')) {
				$GLOBALS['IsEnableAccountCreation'] = "checked=\"checked\"";
			}

			if (!getProductReviewsEnabled()) {
				 $GLOBALS['HideIfReviewsDisabled'] = 'display: none;';
			}

			if(GetConfig('EnableProductComparisons')) {
				$GLOBALS['IsEnableProductComparisons'] = "checked=\"checked\"";
			}

			// Product display settings
			if(GetConfig('ShowProductPrice')) {
				$GLOBALS['IsProductPriceShown'] = 'CHECKED';
			}
			
			if(GetConfig('ShowPriceGuest')) {
				$GLOBALS['IsPriceGuestShown'] = 'CHECKED';
			}

			if(GetConfig('ShowProductSKU')) {
				$GLOBALS['IsProductSKUShown'] = 'CHECKED';
			}

			if(GetConfig('ShowProductWeight')) {
				$GLOBALS['IsProductWeightShown'] = 'CHECKED';
			}

			if(GetConfig('ShowProductBrand')) {
				$GLOBALS['IsProductBrandShown'] = 'CHECKED';
			}

			if(GetConfig('ShowProductShipping')) {
				$GLOBALS['IsProductShippingShown'] = 'CHECKED';
			}

			if(GetConfig('ShowProductRating')) {
				$GLOBALS['IsProductRatingShown'] = 'CHECKED';
			}

			if(GetConfig('ShowAddToCartLink')) {
				$GLOBALS['IsAddToCartLinkShown'] = 'CHECKED';
			}

			if (GetConfig('ShowAddThisLink')) {
				$GLOBALS['IsAddThisLinkShown'] = 'checked="checked"';
			}

			if(GetConfig('LowInventoryNotificationAddress') != '') {
				$GLOBALS['LowInventoryEmailsEnabledCheck'] = "checked=\"checked\"";
			}
			else {
				$GLOBALS['HideLowInventoryNotification'] = "none";
			}

			if(GetConfig('ForwardInvoiceEmails') != '') {
				$GLOBALS['ForwardInvoiceEmailsCheck'] = "checked=\"checked\"";
			}
			else {
				$GLOBALS['HideForwardInvoiceEmails'] = 'none';
			}

			if(GetConfig('MailUseSMTP')) {
				$GLOBALS['HideMailSMTPSettings'] = '';
				$GLOBALS['MailUseSMTPChecked'] = "checked=\"checked\"";
			}
			else {
				$GLOBALS['HideMailSMTPSettings'] = 'none';
				$GLOBALS['MailUsePHPChecked'] = "checked=\"checked\"";
			}

			if (GetConfig('ProductImageMode') == "lightbox") {
				$GLOBALS['ProductImageModeLightbox'] = 'selected="selected"';
			} else {
				$GLOBALS['ProductImageModePopup'] = 'selected="selected"';
			}

			if (GetConfig('CategoryDisplayMode') == "grid") {
				$GLOBALS['CategoryDisplayModeGrid'] = 'selected="selected"';
			}
			else {
				$GLOBALS['CategoryDisplayModeList'] = 'selected="selected"';
			}

			if (GetConfig('CategoryDefaultImage') !== '') {
				$GLOBALS['CatImageDefaultSettingMessage'] = sprintf(GetLang('CatImageDefaultSettingDesc'), GetConfig('ShopPath') . '/' . GetConfig('CategoryDefaultImage'), GetConfig('CategoryDefaultImage'));
			} else {
				$GLOBALS['CatImageDefaultSettingMessage'] = sprintf(GetLang('BrandImageDefaultSettingNoDeleteDesc'), $GLOBALS['IMG_PATH'].'/CategoryDefault.gif', $GLOBALS['IMG_PATH'].'CategoryDefault.gif');
			}

			if (GetConfig('BrandDefaultImage') !== '') {
				$GLOBALS['BrandImageDefaultSettingMessage'] = sprintf(GetLang('BrandImageDefaultSettingDesc'), GetConfig('ShopPath') . '/' . GetConfig('BrandDefaultImage'), GetConfig('BrandDefaultImage'));
			} else {
				$GLOBALS['BrandImageDefaultSettingMessage'] = sprintf(GetLang('BrandImageDefaultSettingNoDeleteDesc'), $GLOBALS['IMG_PATH'].'/BrandDefault.gif', $GLOBALS['IMG_PATH'].'/BrandDefault.gif');
			}

			$GLOBALS['HideCurrentDefaultProductImage'] = 'display: none';
			switch(GetConfig('DefaultProductImage')) {
				case 'template':
					$GLOBALS['DefaultProductImageTemplateChecked'] = 'checked="checked"';
					break;
				case '':
					$GLOBALS['DefaultProductImageNoneChecked'] = 'checked="checked"';
					break;
				default:
					$GLOBALS['DefaultProductImageCustomChecked'] = 'checked="checked"';
					$GLOBALS['HideCurrentDefaultProductImage'] = '';
					$GLOBALS['DefaultProductImage'] = GetConfig('DefaultProductImage');
			}

			if (GetConfig('CategoryListingMode') == 'children') {
				$GLOBALS['CategoryListModeChildren'] = "checked=\"checked\"";
			}
			else if (GetConfig('CategoryListingMode') == 'emptychildren') {
				$GLOBALS['CategoryListModeEmptyChildren'] = "checked=\"checked\"";
			}
			else {
				$GLOBALS['CategoryListModeSingle'] = "checked=\"checked\"";
			}

			// check if the images need to be resized automatically
			$GLOBALS['RunImageResize'] = '0';
			if(isset($_SESSION['RunImageResize']) && $_SESSION['RunImageResize'] == 'yes') {
				$GLOBALS['RunImageResize'] = '1';
				unset($_SESSION['RunImageResize']);
			}

			// Get a list of the customer groups
			$query = 'SELECT * FROM [|PREFIX|]customer_groups ORDER BY groupname ASC';
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$GLOBALS['CustomerGroupOptions'] = '';
			while($group = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				if(GetConfig('GuestCustomerGroup') == $group['customergroupid']) {
					$sel = 'selected="selected"';
				}
				else {
					$sel = '';
				}
				$GLOBALS['CustomerGroupOptions'] .= "<option value=\"".$group['customergroupid']."\" ".$sel.">".isc_html_escape($group['groupname'])."</option>";
			}

			// Workout the HTTPS URL
			$GLOBALS['CompleteStorePath'] = fix_url($_SERVER['PHP_SELF']);
			$GLOBALS['HTTPSUrl'] = str_replace("http://", "https://", isc_strtolower($GLOBALS['ShopPath']));

			$GLOBALS['HideVendorSettings'] = 'display: none';
			if(gzte11(ISC_HUGEPRINT)) {
				$GLOBALS['HideVendorSettings'] = '';
			}

			if(GetConfig('VendorLogoSize')) {
				$logoDimensions = explode('x', GetConfig('VendorLogoSize'));
				$GLOBALS['VendorLogoSizeW'] = (int)$logoDimensions[0];
				$GLOBALS['VendorLogoSizeH'] = (int)$logoDimensions[1];
				$GLOBALS['HideVendorLogoUploading'] = '';
				$GLOBALS['VendorLogoUploadingChecked'] = 'checked="checked"';
			}
			else {
				$GLOBALS['HideVendorLogoUploading'] = 'display: none';
			}

			if(GetConfig('VendorPhotoSize')) {
				$photoDimensions = explode('x', GetConfig('VendorPhotoSize'));
				$GLOBALS['VendorPhotoSizeW'] = (int)$photoDimensions[0];
				$GLOBALS['VendorPhotoSizeH'] = (int)$photoDimensions[1];
				$GLOBALS['HideVendorPhotoUploading'] = '';
				$GLOBALS['VendorPhotoUploadingChecked'] = 'checked="checked"';
			}
			else {
				$GLOBALS['HideVendorPhotoUploading'] = 'display: none';
			}

			foreach ($this->all_vars as $var) {
				if (is_string(GetConfig($var)) || is_numeric(GetConfig($var))) {
					$GLOBALS[$var] = isc_html_escape(GetConfig($var));
				}
			}
			
			if(isset($GLOBALS['syncIWSintelisisstocktime'])){
				$GLOBALS['syncIWSintelisisstocktime'] = $GLOBALS['syncIWSintelisisstocktime'] / 60;
			}

			// the current value of auto_increment for the orders table
			$GLOBALS['StartingOrderNumber'] = ResetStartingOrderNumber();

			if(GetConfig('DisableDatabaseDetailFields')) {
				$GLOBALS['dbType'] = '';
				$GLOBALS['dbServer'] = '';
				$GLOBALS['dbUser'] = '';
				$GLOBALS['dbPass'] = '';
				$GLOBALS['dbDatabase'] = '';
				$GLOBALS['tablePrefix'] = '';
				$GLOBALS['HideDatabaseDetails'] = 'display: none';
			}

			if(GetConfig('DisableLicenseKeyField')) {
				$GLOBALS['serverStamp'] = 'N/A';
				$GLOBALS['HideLicenseKey'] = 'display: none';
			}

			if(GetConfig('DisablePathFields')) {
				$GLOBALS['HidePathFields'] = 'display: none';
			}

			if(GetConfig('DisableStoreUrlField')) {
				$GLOBALS['HideStoreUrlField'] = 'display: none';
			}

			if(GetConfig('DisableLoggingSettingsTab')) {
				$GLOBALS['HideLoggingSettingsTab'] = 'display: none';
			}

			if(GetConfig('DisableProxyFields')) {
				$GLOBALS['HideProxyFields'] = 'display: none';
			}

			if(GetConfig('DisableBackupSettings')) {
				$GLOBALS['HideBackupSettings'] = 'none';
			}


			// Advance Search settings\
			$GLOBALS['SearchDefaultProductSortOptions'] = getAdvanceSearchSortOptions("product");
			$GLOBALS['SearchDefaultContentSortOptions'] = getAdvanceSearchSortOptions("content");

			$GLOBALS['SearchProductDisplayModeOptions'] = '';

			foreach (array('grid', 'list') as $type) {
				$GLOBALS['SearchProductDisplayModeOptions'] .= '<option value="' . $type . '"';

				if (GetConfig('SearchProductDisplayMode') == $type) {
					$GLOBALS['SearchProductDisplayModeOptions'] .= ' selected';
				}

				$GLOBALS['SearchProductDisplayModeOptions'] .= '>' . GetLang('SearchProductDisplayMode' . ucfirst($type)) . '</option>';
			}

			$GLOBALS['SearchResultsPerPageOptions'] = '';

			foreach (array('5', '10', '20', '50', '100') as $perpage) {
				$GLOBALS['SearchResultsPerPageOptions'] .= '<option value="' . $perpage . '"';

				if (GetConfig('SearchResultsPerPage') == $perpage) {
					$GLOBALS['SearchResultsPerPageOptions'] .= ' selected';
				}

				$GLOBALS['SearchResultsPerPageOptions'] .= '>' . $perpage . '</option>';
			}

			$GLOBALS['SearchOptimisationOptions'] = '';

			foreach (array('fulltext', 'like', 'both') as $mode) {
				$GLOBALS['SearchOptimisationOptions'] .= '<option value="' . $mode . '"';

				if (GetConfig('SearchOptimisation') == $mode) {
					$GLOBALS['SearchOptimisationOptions'] .= ' selected';
				}

				$GLOBALS['SearchOptimisationOptions'] .= '>' . GetLang('SearchOptimisation' . ucfirst(isc_strtolower($mode))) . '</option>';
			}

			$GLOBALS["AbandonOrderLifetimeOptions"] = "";

			foreach (array(1, 7, 14, 21, 30, 60, 90, 120, 150, 180) as $lifetimeType) {
				$GLOBALS["AbandonOrderLifetimeOptions"] .= "<option value=\"" . $lifetimeType . "\"";

				if ((int)GetConfig("AbandonOrderLifetime") == $lifetimeType) {
					$GLOBALS["AbandonOrderLifetimeOptions"] .= " selected=\"selected\"";
				}

				$GLOBALS["AbandonOrderLifetimeOptions"] .= ">" . GetLang("AbandonOrderLifetimeOption" . $lifetimeType . "Days") . "</option>\n";
			}

			$GLOBALS['ShopPath'] = GetConfig('ShopPathNormal');

			// get the maintenance message
			$GLOBALS['DownForMaintenanceMessage'] = Store_DownForMaintenance::getDownForMaintenanceMessage();

			switch (GetConfig('RedirectWWW')) {
				case REDIRECT_TO_WWW:
					$redirectOption = 'RedirectToWWW';
					break;
				case REDIRECT_TO_NO_WWW:
					$redirectOption = 'RedirectToNoWWW';
					break;
				default:
					$redirectOption = 'RedirectNoPreference';
			}

			$GLOBALS[$redirectOption . 'Selected'] = 'selected="selected"';
			$GLOBALS['ShowPCISettings'] = !GetConfig('HidePCISettings');

			$GLOBALS['FacebookLikeButtonEnabled'] = GetConfig('FacebookLikeButtonEnabled');
			$GLOBALS['FacebookLikeButtonStyle' . GetConfig('FacebookLikeButtonStyle')] = 'selected="selected"';
			$GLOBALS['FacebookLikeButtonPosition' . GetConfig('FacebookLikeButtonPosition')] = 'selected="selected"';
			$GLOBALS['FacebookLikeButtonVerb' . GetConfig('FacebookLikeButtonVerb')] = 'selected="selected"';
			$GLOBALS['FacebookLikeButtonShowFacesEnabled'] = GetConfig('FacebookLikeButtonShowFaces');

			if (!isset($GLOBALS['TPL_CFG']['EnableFlyoutMenuSupport']) || !$GLOBALS['TPL_CFG']['EnableFlyoutMenuSupport']) {
				// force selection if template does not support flyout
				$GLOBALS['CategoryListStyle'] = 'static';
			}

			$this->template->display('settings.manage.tpl');
		}

		private function ManageCurrencySettings($messages=array())
		{
			$GLOBALS['Message'] = GetFlashMessageBoxes();

			// Select the first available currency module to be used for auto updating the exchange rate
			if (count($currModules = explode(",", GetConfig("CurrencyMethods")))) {
				$GLOBALS['SelectedCurrencyModuleId'] = $currModules[0];
				$GLOBALS['UpdateExchageRateButton'] = '<input type="button" name="IndexUpdateButton" value="'. GetLang('CurrencyUpdateSelectedExchangeRate') . '" id="IndexUpdateButton" class="SmallButton" style="width:200px;" onclick="ConfirmUpdateSelectedExchangeRate()" />';
			}
			else {
				$GLOBALS['SelectedCurrencyModuleId'] = "0";
				$GLOBALS['UpdateExchageRateButton'] = "";
			}

			// Our default options
			$GLOBALS['DefaultTab'] = 0;
			$GLOBALS['CurrencyTabs'] = '<li><a href="#" id="tab0" onclick="ShowTab(0)">' . GetLang('CurrencyOptions') . '</a></li>';

			// Get our selected currency converts list
			$GLOBALS['ConverterProviders'] = $this->_getCurrencyConvertersAsOptions();

			// What's the path for the exchange rate update cron?
			if (strpos(strtolower(PHP_OS), 'win') === 0) {
				$binary = 'php.exe';
			} else {
				$binary = 'php';
			}

			$path_to_php = Which($binary);
			if ($path_to_php === '' && strpos(strtolower(PHP_OS), 'win') === 0) {
				$path_to_php = 'php.exe';
			} elseif ($path_to_php === '') {
				$path_to_php = 'php';
			}
			$GLOBALS['ExchangeRatePath'] = $path_to_php.' -f ' . realpath(ISC_BASE_PATH.'/admin/') . "/cron-updateexchangerates.php";

			// Get our list of currencies
			$GLOBALS['CurrencyGrid'] = "";
			$GLOBALS['CurrencyIntro'] = GetLang('CurrencyIntro');

			// Apply any special messages that need modifying
			$GLOBALS['CurrencySetAsDefaultMessage'] = sprintf(GetLang('CurrencySetAsDefaultMessage'), GetLang('CurrencySetAsDefaultOptYes'), GetLang('CurrencySetAsDefaultOptYesPrice'));

			// Apply our Popup variables
			$GLOBALS['PopupID'] = "CurrencyPopup";
			$GLOBALS['PopupDisplay'] = "none";
			$GLOBALS['PopupTools'] = "";
			$GLOBALS['PopupImgDisplay'] = "none";
			$GLOBALS['PopupImgSrc'] = "images/1x1.gif";  //IMPORTANT!!! Set any source!
			$GLOBALS['PopupHeader'] = GetLang('CurrencySetAsDefaultTitle');

			$GLOBALS['PopupContent'] = sprintf(GetLang('CurrencySetAsDefaultMessage'), GetLang('CurrencySetAsDefaultOptYes'), GetLang('CurrencySetAsDefaultOptYesPrice')) . '</p><p>';
			$GLOBALS['PopupContent'] .= '<input type="button" value="' . isc_html_escape(GetLang('CurrencySetAsDefaultOptYes')) . '" id="CurrencyPopupButtonYes" class="Field150" />';
			$GLOBALS['PopupContent'] .= '<input type="button" value="' . isc_html_escape(GetLang('CurrencySetAsDefaultOptYesPrice')) . '" id="CurrencyPopupButtonYesPrice" class="Field150" />';
			$GLOBALS['PopupContent'] .= '<input type="button" value="' . isc_html_escape(GetLang('CurrencySetAsDefaultOptNo')) . '" id="CurrencyPopupButtonNo" class="Field150" />';

			// Get our currency list
			$currencyResult = $this->_getCurrencyList();

			if ($GLOBALS['ISC_CLASS_DB']->CountResult($currencyResult) > 0) {
				while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($currencyResult)) {

					$GLOBALS['CurrencyId'] = (int)$row['currencyid'];
					$GLOBALS['CurrencyName'] = isc_html_escape($row['currencyname']);
					$GLOBALS['CurrencyCode'] = isc_html_escape($row['currencycode']);
					$GLOBALS['CurrencyRate'] = FormatPrice($row['currencyexchangerate'], false, true, false, $row, false);

					if ($row['currencyisdefault']) {
						$GLOBALS['ClassName'] = "GridRowSel";
						$GLOBALS['DeleteStatus'] = " disabled='disabled'";
						$GLOBALS['CurrencyName'] .= " <span style='margin-left:10px; font-size:0.8em; font-weight:bold;'>(".GetLang('lowerDefault').")</span>";
						$defaultStyle = " style='color:#666666;'";
					}
					else {
						$GLOBALS['ClassName'] = "GridRow";
						$GLOBALS['DeleteStatus'] = "";
						$defaultStyle = "";
					}

					if ($row['currencyisdefault'] && $row['currencystatus'] == 1) {
						$GLOBALS['Status'] = "<img border='0' src='images/tick.gif' alt='tick'>";
					}
					else if ($row['currencystatus'] == 1) {
						$GLOBALS['Status'] = "<a title='" . GetLang('CurrencyStatusDisable') . "' href='index.php?ToDo=settingsEditCurrencyStatus&amp;currencyId=" . $row['currencyid'] . "&amp;status=0'><img border='0' src='images/tick.gif' alt='tick'></a>";
					}
					else {
						$GLOBALS['Status'] = "<a title='" . GetLang('CurrencyStatusEnable') . "' href='index.php?ToDo=settingsEditCurrencyStatus&amp;currencyId=" . $row['currencyid'] . "&amp;status=1'><img border='0' src='images/cross.gif' alt='cross'></a>";
					}

					$GLOBALS['CurrencyLinks'] = "<a title='" . GetLang('CurrencyEdit') . "' href='index.php?ToDo=settingsEditCurrency&amp;currencyId=" . $row['currencyid'] . "'>" . GetLang('Edit') . "</a>";
					$GLOBALS['CurrencyLinks'] .= "&nbsp;&nbsp;&nbsp;&nbsp;";

					// Default record should not be able to set as default again
					if ($row['currencyisdefault']) {
						$GLOBALS['CurrencyLinks'] .= "<span style='color:#666666;'>" . GetLang('CurrencySetAsDefault') . "</span>";
					}
					else {
						$GLOBALS['CurrencyLinks'] .= "<a href='#' title='" . GetLang('CurrencySetAsDefault') . "' onclick='return ConfirmSetAsDefault(" . $row['currencyid'] . ");'>" . GetLang('CurrencySetAsDefault') . "</a>";
					}

					$GLOBALS['CurrencyGrid'] .= $this->template->render('currency.manage.row.tpl');
				}
			}
			else {
				// There are no currencies in the database
				$GLOBALS['DisableDelete'] = "style='display:none'";
				$GLOBALS['DisplayGrid'] = "none";
				$GLOBALS['CurrencyOptionsMessage'] = MessageBox(GetLang('NoCurrencies'), MSG_INFO);
				$GLOBALS['ShowCurrencyTableHeaders'] = 'none';
			}

			$this->template->display('settings.currency.manage.tpl');
		}

		private function _getCurrencyConvertersAsOptions()
		{
			// Get a list of all available currency converters as <option> tags
			$converters = GetAvailableModules('currency');
			$output = "";

			foreach ($converters as $converter) {
				$sel = '';
				if($converter['enabled']) {
					$sel = 'selected="selected"';
				}
				$output .= sprintf("<option %s value='%s'>%s</option>", $sel, $converter['id'], $converter['name']);
			}

			return $output;
		}

		private function _getCurrencyList()
		{
			$query = "SELECT * FROM [|PREFIX|]currencies ORDER BY currencyisdefault DESC, currencyname ASC";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			return $result;
		}

		private function _getCurrencyConverterAsItems($selectedConverterCode=null, $defaultManualSelect=true)
		{
			if (is_null($selectedConverterCode) && $defaultManualSelect) {
				$selected = 'checked="checked"';
			}
			else {
				$selected = '';
			}

			$converters = GetAvailableModules('currency');
			$convertorList = '<input ' . $selected . ' type="radio" name="currencyconverter" id="currencyconvertermanual" value="" onclick="toggleExchangeConverter(\'manual\');" />'
						   . '<label for="currencyconvertermanual">' . GetLang('CurrencyConverterManual') . '</label><br />';

			foreach ($converters as $converter) {

				if (!$converter['enabled']) {
					continue;
				}

				if ($selectedConverterCode == $converter['id']) {
					$selected = 'checked="checked"';
					$hide = 'inline';
				}
				else {
					$selected = '';
					$hide = 'none';
				}

				$labelValue = sprintf(GetLang('CurrencyConverterAuto'), $converter['object']->geturl(), isc_html_escape($converter['name']));
				$convertorList .= '<input ' . $selected . ' type="radio" name="currencyconverter" id="currencyconverter' . $converter['id'] . '" value="' . $converter['id'] . '" '
								. ' onclick="toggleExchangeConverter(\'' . addslashes($converter['id']) . '\');"/>'
								. '<label for="currencyconverter' . $converter['id'] . '">' . $labelValue . '</label>'
								. '<div style="display:' . $hide . '; margin-left:10px;" id="currencyconverterupdate' . $converter['id'] . '">'
								. '<input type="button" name="currencyconverterupdate" class="FormButton" value="' . GetLang('CurrencyConverterUpdate') . '" '
								. ' onclick="getExchangeRate(\'' . addslashes($converter['id']) . '\');" /></div>'
								. '<br />';
			}

			return $convertorList;
		}

		private function _getCurrencyOriginOptions($countryid=null, $regionid=null)
		{
			$html	= '<optgroup id="currencyorigintype-region" label="' . isc_html_escape(GetLang('CurrencyRegions')) . '">';
			$html	.= GetRegionList($regionid, false, "AllRegions", 0, true);
			$html	.= '</optgroup>';
			$html	.= '<optgroup id="currencyorigintype-country" label="' . isc_html_escape(GetLang('CurrencyCountries')) . '">';
			$html	.= GetCountryList($countryid, false, "AllCountries", 0, true);
			$html	.= '</optgroup>';

			return $html;
		}

		private function AddCurrency()
		{
			$currency = GetDefaultCurrency();

			$GLOBALS['FormAction'] = "SettingsSaveNewCurrency";
			$GLOBALS['CurrencyTitle'] = GetLang('AddCurrency');
			$GLOBALS['CancelMessage'] = GetLang('CancelAddCurrency');
			$GLOBALS['OriginList'] = $this->_getCurrencyOriginOptions();
			$GLOBALS['ConverterList'] = $this->_getCurrencyConverterAsItems();
			$GLOBALS['CurrencyConverterBox'] = sprintf(GetLang('CurrencyConverterBox'), $currency['currencycode']);
			$GLOBALS['CurrencyExchangeRateHelp'] = sprintf(GetLang('CurrencyExchangeRateHelp'), $currency['currencycode'], GetConfig('DefaultCurrencyRate'));

			// Add some default options
			$GLOBALS['CurrencyEnabled'] = ' checked="checked"';
			$GLOBALS['CurrencyString'] = GetLang('InstallDefaultCurrencyString');
			$GLOBALS['CurrencyDecimalString'] = GetLang('InstallDefaultCurrencyDecimalString');
			$GLOBALS['CurrencyThousandString'] = GetLang('InstallDefaultCurrencyThousandString');
			$GLOBALS['CurrencyDecimalPlace'] = GetLang('InstallDefaultCurrencyDecimalPlace');

			$this->template->display('currency.form.tpl');
		}

		private function GetCurrencyDataFromPost()
		{
			$data = array(
				'currencyname' => $_POST['currencyname'],
				'currencycode' => isc_strtoupper($_POST['currencycode']),
				'currencyconvertercode' => $_POST['currencyconverter'],
				'currencyexchangerate' => $_POST['currencyexchangerate'],
				'currencystringposition' => isc_strtoupper($_POST['currencystringposition']),
				'currencystring' => $_POST['currencystring'],
				'currencydecimalstring' => $_POST['currencydecimalstring'],
				'currencythousandstring' => $_POST['currencythousandstring'],
				'currencydecimalplace' => $_POST['currencydecimalplace'],
				'currencylastupdated' => time()
			);

			if (strtolower($_POST['currencyorigintype']) == "country") {
				$data['currencycouregid'] = null;
				$data['currencycountryid'] = $_POST["currencyorigin"];
			} else if (strtolower($_POST['currencyorigintype']) == "region") {
				$data['currencycouregid'] = $_POST["currencyorigin"];
				$data['currencycountryid'] = null;
			}

			if (isset($_POST['currencystatus'])) {
				$data['currencystatus'] = 1;
			}
			else {
				$data['currencystatus'] = 0;
			}

			return $data;
		}

		private function SaveNewCurrency()
		{
			$pass = true;
			$message = "";
			$data = $this->GetCurrencyDataFromPost();

			// Is there already a currency setup for the selected code?
			if (!$this->CurrencyCheck($data, $message)) {
				$this->ManageCurrencySettings(array(sprintf(GetLang('CurrencyNotAdded'), $message) => MSG_ERROR));
				exit;
			}

			// We must be able to start a transaction
			if (!$GLOBALS['ISC_CLASS_DB']->StartTransaction()) {
				$pass = false;
				$message = $GLOBALS['ISC_CLASS_DB']->Error();
			}

			if ($pass) {
				$GLOBALS['ISC_CLASS_DB']->InsertQuery("currencies", $data, true);
			}

			if ($pass && $GLOBALS['ISC_CLASS_DB']->Error() != "") {
				$GLOBALS['ISC_CLASS_DB']->RollbackTransaction();
				$message = $GLOBALS['ISC_CLASS_DB']->Error();
				$pass = false;
			}

			if ($pass) {
				$GLOBALS['ISC_CLASS_DB']->CommitTransaction();

				// Update the cached currency list
				$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateCurrencies();

				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($GLOBALS['ISC_CLASS_DB']->LastId(), $data['currencyname'] . ' ('. $data['currencycode'] . ')');
				$this->ManageCurrencySettings(array(GetLang('CurrencyAddedSuccessfully') => MSG_SUCCESS));
			}
			else {
				$message = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
				$GLOBALS['ISC_CLASS_DB']->RollbackTransaction();
				$this->ManageCurrencySettings(array(sprintf(GetLang('CurrencyNotAdded'), $message) => MSG_ERROR));
			}
		}

		private function CurrencyCheck($data, &$message)
		{
			$isDefault = false;
			if (array_key_exists("currencyid", $_REQUEST) && isId($_REQUEST['currencyid']) && $_REQUEST['currencyid'] == GetConfig("DefaultCurrencyID")) {
				$isDefault = true;
			}

			// General check to see if the required fields were entered
			$requiredFields = array(
				'currencyname'				=> GetLang('EnterCurrencyName'),
				'currencycode'				=> GetLang('EnterCurrencyCode'),
				'currencyexchangerate'		=> GetLang('EnterCurrencyExchangeRate'),
				'currencystringposition'	=> GetLang('EnterCurrencyStringPosition'),
				'currencystring'			=> GetLang('EnterCurrencyString'),
				'currencydecimalstring'		=> GetLang('EnterCurrencyDecimalString'),
				'currencythousandstring'	=> GetLang('EnterCurrencyThousandString'),
				'currencydecimalplace'		=> GetLang('EnterCurrencyDecimalPlace')
			);

			if ($isDefault) {
				unset($requiredFields['currencyexchangerate']);
			}

			foreach ($requiredFields as $key => $err) {
				if (!array_key_exists($key, $data) || strlen($data[$key]) == 0) {
					$message = $err;
					return false;
				}
			}

			if (!isId($data["currencycountryid"]) && !isId($data["currencycouregid"])) {
				$message = GetLang('EnterCurrencyOrigin');
				return false;
			}

			if (!preg_match('/^[a-z]{3}$/i', $data['currencycode'])) {
				$message = GetLang('InvalidCurrencyCode');
				return false;
			}

			if (!$isDefault && !is_numeric($data['currencyexchangerate'])) {
				$message = GetLang('InvalidCurrencyExchangeRate');
				return false;
			}

			$oneChar = array(
				"currencydecimalstring"		=> GetLang('InvalidCurrencyDecimalString'),
				"currencythousandstring"	=> GetLang('InvalidCurrencyThousandString')
			);

			foreach ($oneChar as $key => $err) {
				if (isc_strlen($data[$key]) > 1 || preg_match("/[0-9]+/", $data[$key])) {
					$message = $err;
					return false;
				}
			}

			if ($data['currencydecimalstring'] == $data['currencythousandstring']) {
				$message = GetLang('InvalidCurrencyStringMatch');
				return false;
			}

			// Check to see if we already have this one setup
			$query = "SELECT currencycode FROM [|PREFIX|]currencies WHERE currencycode='".$GLOBALS['ISC_CLASS_DB']->Quote($data['currencycode'])."' AND ";

			if (isId($data['currencycountryid'])) {
				$query .= " currencycountryid='".(int)$data['currencycountryid']."'";
			} else if (isId($data['currencycouregid'])) {
				$query .= " currencycouregid='".(int)$data['currencycouregid']."'";
			}

			if (array_key_exists("currencyid", $_REQUEST) && isId($_REQUEST['currencyid'])) {
				$query .= " AND currencyid != '" . (int)$_REQUEST['currencyid']."'";
			}

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			if($GLOBALS['ISC_CLASS_DB']->FetchOne($result, 'currencycode')) {
				$message = GetLang('CurrencyAlreadySetup');
				return false;
			}

			return true;
		}

		private function SaveUpdatedCurrencySettings()
		{
			// Delete existing module configuration
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('module_vars', "WHERE modulename LIKE 'currency\_%'");

			$converterproviders = '';
			if (isset($_POST['converterproviders'])) {
				$converterproviders = implode(",", $_POST['converterproviders']);
				$enabledStack = $_POST['converterproviders'];
			}
			else {
				$enabledStack = array();
			 }

			// Push everything to globals and save
			$GLOBALS['ISC_NEW_CFG']['CurrencyMethods'] = $converterproviders;

			$messages = array();
			if ($this->CommitSettings($messages)) {
				// Now get all currency variables (they are in an array from $_POST)
				foreach($enabledStack as $module_id) {
					if (!GetModuleById('currency', $module, $module_id)) {
						continue;
					}

					$vars = array();
					if (isset($_POST[$module_id])) {
						$vars = $_POST[$module_id];
					}

					$module->SaveModuleSettings($vars);
				}

				if ($GLOBALS['ISC_CLASS_DB']->Error() == "") {
					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();

					FlashMessage(GetLang('CurrencySettingsSavedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewCurrencySettings');
				} else {
					FlashMessage(GetLang('CurrencySettingsNotSaved'), MSG_ERROR, 'index.php?ToDo=viewCurrencySettings');
				}
			} else {
				FlashMessage(GetLang('CurrencySettingsNotSaved'), MSG_ERROR, 'index.php?ToDo=viewCurrencySettings');
			}
		}

		private function DeleteCurrencies()
		{
			if (isset($_POST['currencies']) && count($currenciesIdx = array_filter($_POST['currencies'], "isId")) > 0) {
				$currenciesIdx = implode(",", $GLOBALS['ISC_CLASS_DB']->Quote($currenciesIdx));

				// Delete the currency
				if(!$GLOBALS['ISC_CLASS_DB']->DeleteQuery('currencies', "WHERE currencyid IN (".$currenciesIdx.") AND currencyisdefault='0'")) {
					$this->ManageCurrencySettings(array($GLOBALS['ISC_CLASS_DB']->GetErrorMsg() => MSG_ERROR));
				} else {
					// Update the cached currency list
					$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateCurrencies();

					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($currenciesIdx));
					$this->ManageCurrencySettings();
				}
			}
			else {
				$this->ManageCurrencySettings();
			}
		}

		private function EditCurrency()
		{
			$currency = GetDefaultCurrency();

			$GLOBALS['FormAction'] = "SettingsSaveUpdatedCurrency";
			$GLOBALS['CurrencyTitle'] = GetLang('EditCurrency');
			$GLOBALS['CancelMessage'] = GetLang('CancelEditCurrency');
			$GLOBALS['CurrencyConverterBox'] = sprintf(GetLang('CurrencyConverterBox'), $currency['currencycode']);
			$GLOBALS['CurrencyExchangeRateHelp'] = sprintf(GetLang('CurrencyExchangeRateHelp'), $currency['currencycode'], GetConfig('DefaultCurrencyRate'));
			$GLOBALS['OriginListSize'] = ' size="2"';

			if (isset($_GET['currencyId'])) {
				$currencyId = (int)$_GET['currencyId'];
				$query = "SELECT * FROM [|PREFIX|]currencies WHERE currencyid='".$currencyId."'";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				$GLOBALS['hiddenFields'] = sprintf("<input type='hidden' name='currencyid' value='%d' />", $currencyId);

				if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$GLOBALS['CurrencyName'] = isc_html_escape($row['currencyname']);
					$GLOBALS['CurrencyCode'] = isc_html_escape($row['currencycode']);
					$GLOBALS['CurrencyString'] = isc_html_escape($row['currencystring']);
					$GLOBALS['CurrencyDecimalString'] = isc_html_escape($row['currencydecimalstring']);
					$GLOBALS['CurrencyThousandString'] = isc_html_escape($row['currencythousandstring']);
					$GLOBALS['CurrencyDecimalPlace'] = isc_html_escape($row['currencydecimalplace']);
					$GLOBALS['CurrencyExchangeRate'] = isc_html_escape((float)$row['currencyexchangerate']);
					$GLOBALS['ConverterList'] = $this->_getCurrencyConverterAsItems($row['currencyconvertercode']);
					$GLOBALS['OriginListSize'] = '';

					if (strtolower($row['currencystringposition']) == "left") {
						$GLOBALS['CurrencyLocationIsLeft'] = 'selected="selected"';
					} else {
						$GLOBALS['CurrencyLocationIsRight'] = 'selected="selected"';
					}

					if (isId($row['currencycountryid'])) {
						$GLOBALS['CurrencyOriginType'] = "country";
					} else if (isId($row['currencycouregid'])) {
						$GLOBALS['CurrencyOriginType'] = "region";
					}

					$GLOBALS['OriginList'] = $this->_getCurrencyOriginOptions($row['currencycountryid'], $row['currencycouregid']);

					if ($row['currencystatus'] == 1) {
						$GLOBALS['CurrencyEnabled'] = 'checked="checked"';
					}

					if ($row['currencyisdefault']) {
						$GLOBALS['HideOnDefault'] = " style='display:none;'";
					}

					$this->template->display('currency.form.tpl');
				}
				else {
					$this->ManageCurrencySettings();
				}
			}
			else {
				$this->ManageCurrencySettings();
			}
		}

		private function UpdateCurrencyStatus()
		{
			if (isset($_GET['currencyId']) && isset($_GET['status'])) {
				$currencyId = (int)$_GET['currencyId'];
				$status = (int)$_GET['status'];

				$updatedCurrency = array(
					"currencystatus" => $status
				);
				if($GLOBALS['ISC_CLASS_DB']->UpdateQuery("currencies", $updatedCurrency, "currencyid='".$GLOBALS['ISC_CLASS_DB']->Quote($currencyId)."'", true)) {
					$query = sprintf("SELECT currencyname FROM [|PREFIX|]currencies WHERE currencyid='%d'", $currencyId);
					$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
					$currName = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

					// Update the cached currency list
					$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateCurrencies();

					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($currencyId, $currName);

					FlashMessage(GetLang('CurrencyStatusSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewCurrencySettings');
				} else {
					$err = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
					FlashMessage(sprintf(GetLang('CurrencyErrStatusNotChanged'), $err), MSG_ERROR, 'index.php?ToDo=viewCurrencySettings');
				}
			}
		}

		private function SaveUpdatedCurrency()
		{
			$pass = true;
			$message = "";
			$data = $this->GetCurrencyDataFromPost();

			// Pop off some fields if this is a default record as we don't want them to modify them
			if ($_POST['currencyid'] == GetConfig('DefaultCurrencyID')) {
				unset($data['currencyconvertercode']);
				unset($data['currencyexchangerate']);
				unset($data['currencystatus']);
			}

			// We must be able to start a transaction
			if (!$GLOBALS['ISC_CLASS_DB']->StartTransaction()) {
				$pass = false;
				$message = $GLOBALS['ISC_CLASS_DB']->Error();
			}

			// Is there already a currency setup for the selected code?
			if (!$this->CurrencyCheck($data, $message)) {
				$pass = false;
			}

			if ($pass) {
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery("currencies", $data, "currencyid='".$GLOBALS['ISC_CLASS_DB']->Quote((int)$_POST['currencyid'])."'", true);
			}

			if ($pass && $GLOBALS['ISC_CLASS_DB']->Error() != "") {
				$message = $GLOBALS['ISC_CLASS_DB']->Error();
				$pass = false;
			}

			// Are we setting this currency as the default?
			if ($pass && array_key_exists("setCurrencyAsDefault", $_POST) && $_POST['setCurrencyAsDefault'] && !$this->setDefaultCurrency($_POST['currencyid'], $message)) {
				$pass = false;
			}

			// If we were editing the default currency then recompile the settings again
			if ($pass && $_POST['currencyid'] == GetConfig('DefaultCurrencyID')) {
				$GLOBALS['ISC_NEW_CFG']['CurrencyToken']	= (string)$data['currencystring'];
				$GLOBALS['ISC_NEW_CFG']['CurrencyLocation']	= strtolower($data['currencystringposition']);
				$GLOBALS['ISC_NEW_CFG']['DecimalToken']		= (string)$data['currencydecimalstring'];
				$GLOBALS['ISC_NEW_CFG']['DecimalPlaces']	= (int)$data['currencydecimalplace'];
				$GLOBALS['ISC_NEW_CFG']['ThousandsToken']	= (string)$data['currencythousandstring'];

				$pass = (bool)$this->CommitSettings($messages);
			}

			if ($pass) {
				$GLOBALS['ISC_CLASS_DB']->CommitTransaction();
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction((int)$_POST['currencyid'], $data['currencyname'] . ' ('. $data['currencycode'] . ')');

				// Update the cached currency list
				$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateCurrencies();

				FlashMessage(GetLang('CurrencyUpdatedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewCurrencySettings');
			}
			else {
				$GLOBALS['ISC_CLASS_DB']->RollbackTransaction();
				FlashMessage(sprintf(GetLang('CurrencyNotUpdated'), $message), MSG_ERROR, 'index.php?ToDo=viewCurrencySettings');
			}
		}

		private function SaveSetAsDefaultCurrency()
		{
			$pass = true;
			$message = "";

			if (!array_key_exists("currencyId", $_REQUEST) || !isId($_REQUEST['currencyId'])) {
				$pass = false;
			}

			if(isset($_REQUEST['updatePrice']) && $_REQUEST['updatePrice'] == 1) {
				$updatePrices = true;
			}
			else {
				$updatePrices = false;
			}

			if ($pass && !$this->setDefaultCurrency($_REQUEST['currencyId'], $message, $updatePrices)) {
				$pass = false;
			}

			if ($pass) {

				// Update the cached currency list
				$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateCurrencies();

				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($_REQUEST['currencyId']);
				$this->ManageCurrencySettings(array(GetLang('CurrencySetAsDefaultSuccessfully') => MSG_SUCCESS));
			}
			else {
				$this->ManageCurrencySettings(array(sprintf(GetLang('CurrencyNotSetAsDefault'), $message) => MSG_ERROR));
			}
		}

		private function setDefaultCurrency($currencyId, &$message, $updatePrices=false)
		{
			$query = "SELECT * FROM [|PREFIX|]currencies WHERE currencyid='".(int)$currencyId."'";
			if (!isId($currencyId) || !($result = $GLOBALS['ISC_CLASS_DB']->Query($query)) || !($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result))) {
				$messages[] = GetLang('CurrencyNotSetToDefault');
				return false;
			}

			$query = "
				UPDATE [|PREFIX|]currencies
				SET currencyexchangerate = IF(currencyid <> ". $currencyId . ", (currencyexchangerate / " . (string)$row['currencyexchangerate'] . "), 1),
				currencyisdefault = IF(currencyid <> ". $currencyId . ", 0, 1), currencystatus = 1, currencylastupdated = UNIX_TIMESTAMP()
			";
			$GLOBALS['ISC_CLASS_DB']->Query($query);
			if ($GLOBALS['ISC_CLASS_DB']->Error() != "") {
				$message = $GLOBALS['ISC_CLASS_DB']->Error();
				return false;
			}

			$GLOBALS['ISC_CLASS_DB']->StartTransaction();

			if($updatePrices == true) {
				// Now the delicate part of updating all the product prices
				$query = "
					UPDATE [|PREFIX|]products
					SET prodprice = (prodprice * " . (string)$row['currencyexchangerate'] . "), prodcostprice = (prodcostprice * " . (string)$row['currencyexchangerate'] . "),
					prodretailprice = (prodretailprice * " . (string)$row['currencyexchangerate'] . "), prodsaleprice = (prodsaleprice * " . (string)$row['currencyexchangerate'] . "),
					prodcalculatedprice = (prodcalculatedprice * " . (string)$row['currencyexchangerate'] . ")
					";
				$GLOBALS['ISC_CLASS_DB']->Query($query);

				if ($GLOBALS['ISC_CLASS_DB']->Error() != "") {
					$message = $GLOBALS['ISC_CLASS_DB']->Error();
					return false;
				}

				// Don't forget our product variations
				$query = "
					UPDATE [|PREFIX|]product_variation_combinations
					SET vcprice = (vcprice * " . (string)$row['currencyexchangerate'] . ")
					";
				$GLOBALS['ISC_CLASS_DB']->Query($query);

				if ($GLOBALS['ISC_CLASS_DB']->Error() != "") {
					$message = $GLOBALS['ISC_CLASS_DB']->Error();
					return false;
				}

				// Also any store credit for all customers
				$query = "
					UPDATE [|PREFIX|]customers
					SET custstorecredit = (custstorecredit * " . (string)$row['currencyexchangerate'] . ")
					";
				$GLOBALS['ISC_CLASS_DB']->Query($query);

				if ($GLOBALS['ISC_CLASS_DB']->Error() != "") {
					$message = $GLOBALS['ISC_CLASS_DB']->Error();
					return false;
				}

				// Plus any of the product discounts
				$query = "
					UPDATE [|PREFIX|]product_discounts
					SET discountamount = (discountamount * " . (string)$row['currencyexchangerate'] . ")
					WHERE discounttype = 'price' OR discounttype = 'fixed'
					";
				$GLOBALS['ISC_CLASS_DB']->Query($query);

				if ($GLOBALS['ISC_CLASS_DB']->Error() != "") {
					$message = $GLOBALS['ISC_CLASS_DB']->Error();
					return false;
				}
			}

			// Save our new currency settings
			$GLOBALS['ISC_NEW_CFG']['DefaultCurrencyID']	= (int)$row['currencyid'];
			$GLOBALS['ISC_NEW_CFG']['CurrencyToken']		= (string)$row['currencystring'];
			$GLOBALS['ISC_NEW_CFG']['CurrencyLocation']		= strtolower($row['currencystringposition']);
			$GLOBALS['ISC_NEW_CFG']['DecimalToken']			= (string)$row['currencydecimalstring'];
			$GLOBALS['ISC_NEW_CFG']['DecimalPlaces']		= (int)$row['currencydecimalplace'];
			$GLOBALS['ISC_NEW_CFG']['ThousandsToken']		= (string)$row['currencythousandstring'];

			if($this->CommitSettings($messages)) {
				$GLOBALS['ISC_CLASS_DB']->CommitTransaction();
				return true;
			}
			else {
				$GLOBALS['ISC_CLASS_DB']->RollbackTransaction();
				return false;
			}
		}
		private function ManageNotificationSettings($messages=array())
		{
			$GLOBALS['Message'] = GetFlashMessageBoxes();

			$GLOBALS['NotificationJavaScript'] = "";
			$GLOBALS['NotificationProviders'] = $this->GetNotificationProvidersAsOptions();

			// Which notification modules are enabled?
			$notifications = GetEnabledNotificationModules();

			$GLOBALS['NotificationTabs'] = "";
			$GLOBALS['NotificationDivs'] = "";
			$count = 2;

			// Setup each notification module with its own tab
			foreach ($notifications as $notification) {
				$GLOBALS['NotificationTabs'] .= sprintf('<li><a href="#" id="tab%d" onclick="ShowTab(%d)">%s</a></li>', $count, $count, $notification['name']);
				$GLOBALS['NotificationDivs'] .= sprintf('<div id="div%d" style="padding-top: 10px;">%s</div>', $count, $notification['object']->getpropertiessheet($count));
				$count++;
			}

			$this->template->display('settings.notifications.manage.tpl');
		}

		private function GetNotificationProvidersAsOptions()
		{
			// Get a list of all available notification providers as <option> tags
			$notifications = GetAvailableModules('notification');
			$output = "";

			foreach ($notifications as $notification) {
				$sel = '';
				if($notification['enabled']) {
					$sel = 'selected="selected"';
				}
				$output .= sprintf("<option %s value='%s'>%s</option>", $sel, $notification['id'], $notification['name']);
			}

			return $output;
		}

		private function SaveUpdatedNotificationSettings()
		{
			// Delete existing module configuration
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('module_vars', "WHERE modulename LIKE 'notification\_%'");

			$enabledStack = array();
			$messages = array();

			if(isset($_POST['notificationproviders'])) {
				// Can the selected payment modules be enabled?
				foreach ($_POST['notificationproviders'] as $provider) {
					GetModuleById('notification', $module, $provider);
					if (is_object($module)) {
					// Is this notification provider supported on this server?
						if($module->IsSupported() == false) {
							$errors = $module->GetErrors();
							foreach($errors as $error) {
								FlashMessage($error, MSG_ERROR);
							}
							continue;
						}

						// Otherwise, this notification provider is fine, so add it to the stack of enabled
						$enabledStack[] = $provider;
					}
				}
			}

			$notificationproviders = implode(",", $enabledStack);

			// Push everything to globals and save
			$GLOBALS['ISC_NEW_CFG']['NotificationMethods'] = $notificationproviders;

			if ($this->CommitSettings($messages)) {
				// Now get all notification variables (they are in an array from $_POST)
				foreach($enabledStack as $module_id) {
					$vars = array();
					if(isset($_POST[$module_id])) {
						$vars = $_POST[$module_id];
					}
					GetModuleById('notification', $module, $module_id);
					$moduleSettings = $module->GetCustomVars();
					if(!empty($vars) || empty($moduleSettings)) {
						$module->SaveModuleSettings($vars);
					}
				}

				// Rebuild the cache of the notification module variables
				$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateNotificationModuleVars();

				if ($GLOBALS['ISC_CLASS_DB']->Error() == "") {
					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();
					FlashMessage(GetLang('NotificationSettingsSavedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewNotificationSettings');
				} else {
					FlashMessage(GetLang('NotificationSettingsNotSaved'), MSG_ERROR, 'index.php?ToDo=viewNotificationSettings');
				}
			} else {
				FlashMessage(GetLang('NotificationSettingsNotSaved'), MSG_ERROR, 'index.php?ToDo=viewNotificationSettings');
			}
		}

		private function TestNotificationMethod()
		{
			$notifier = null;

			if (isset($_GET['module'])) {
				$module = $_GET['module'];

				if (GetModuleById('notification', $notifier, $module)) {
					$this->template->display('module.pageheader.tpl');
					$notifier->TestNotificationForm();
					$this->template->display('module.pagefooter.tpl');
				}
			}
		}

		public function ManageClickSettings()
		{
			ob_end_clean();
			$img = "";
			if (ech0(GetConfig('serverStamp'))) {
				$fp = fopen(dirname(__FILE__) . "/../../images/blank.gif", "rb");
				while (!feof($fp)) {
					$img .= fgets($fp, 1024);
				}
				fclose($fp);
				header("Content-Type:image/gif");
				echo $img;
			}
			else {
				echo time();
			}
			die();
		}

		private function ManageAnalyticsSettings($messages=array())
		{
			$GLOBALS['Message'] = GetFlashMessageBoxes();

			$GLOBALS['AnalyticsJavaScript'] = "";
			$GLOBALS['AnalyticsProviders'] = $this->GetAnalyticsPackagesAsOptions();

			// Which analytics modules are enabled?
			$packages = GetAvailableModules('analytics', true);
			$GLOBALS['AnalyticsTabs'] = "";
			$GLOBALS['AnalyticsDivs'] = "";
			$count = 2;

			// Setup each analytics module with its own tab
			foreach ($packages as $package) {
				$GLOBALS['AnalyticsTabs'] .= sprintf('<li><a href="#" id="tab%d" onclick="ShowTab(%d)">%s</a></li>', $count, $count, $package['name']);
				$GLOBALS['AnalyticsDivs'] .= sprintf('<div id="div%d" style="padding-top: 10px;">%s</div>', $count, $package['object']->getpropertiessheet($count));

				$count++;
			}

			$this->template->display('settings.analytics.manage.tpl');
		}

		private function SaveUpdatedAnalyticsSettings()
		{
			// Delete existing module configuration
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('module_vars', "WHERE modulename LIKE 'analytics\_%'");

			$enabledStack = array();
			$messages = array();

			// Can the selected payment modules be enabled?
			if (isset($_POST['analyticsproviders']) && is_array($_POST['analyticsproviders'])) {
			foreach ($_POST['analyticsproviders'] as $provider) {
				GetModuleById('analytics', $module, $provider);
				if (is_object($module)) {
				// Is this analytics provider supported on this server?
					if($module->IsSupported() == false) {
						$errors = $module->GetErrors();
						foreach($errors as $error) {
							FlashMessage($error, MSG_ERROR);
						}
						continue;
					}

					// Otherwise, this analytics provider is fine, so add it to the stack of enabled
					$enabledStack[] = $provider;
				}
			}
			}

			$analyticsproviders = implode(",", $enabledStack);

			// Push everything to globals and save
			$GLOBALS['ISC_NEW_CFG']['AnalyticsMethods'] = $analyticsproviders;

			if ($this->CommitSettings($messages)) {
				// Now get all analytics variables (they are in an array from $_POST)
				foreach($enabledStack as $module_id) {
					$vars = array();
					if(isset($_POST[$module_id])) {
						$vars = $_POST[$module_id];
					}
					GetModuleById('analytics', $module, $module_id);
					$module->SaveModuleSettings($vars);
				}

				// Rebuild the cache of the analytics module variables
				$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateAnalyticsModuleVars();

				if ($GLOBALS['ISC_CLASS_DB']->Error() == "") {
					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();
					FlashMessage(GetLang('AnalyticsSettingsSavedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewAnalyticsSettings');
				}
				else {
					FlashMessage(GetLang('AnalyticsSettingsNotSaved'), MSG_ERROR, 'index.php?ToDo=viewAnalyticsSettings');
				}
			} else {
				FlashMessage(GetLang('AnalyticsSettingsNotSaved'), MSG_ERROR, 'index.php?ToDo=viewAnalyticsSettings');
			}
		}

		private function GetAnalyticsPackagesAsOptions()
		{

			// Get a list of all available analytics modules as <option> tags
			$analytics = GetAvailableModules('analytics');
			$output = "";

			foreach ($analytics as $package) {
				$sel = '';
				if($package['enabled']) {
					$sel = 'selected="selected"';
				}
				$output .= sprintf("<option %s value='%s'>%s</option>", $sel, $package['id'], $package['name']);
			}

			return $output;
		}

		private function GetAddonPackagesAsOptions()
		{

			// Get a list of all available addon modules as <option> tags
			$addons = GetAvailableAddonModules();
			$output = "";

			foreach ($addons as $package) {
				$sel = '';
				if($package['enabled']) {
					$sel = 'selected="selected"';
				}
				$output .= sprintf("<option %s value='%s'>%s</option>", $sel, $package['id'], $package['name']);
			}

			return $output;
		}

		private function ManageAddonSettings()
		{
			$GLOBALS['Message'] = GetFlashMessageBoxes();

			$numAvailableAddons = count(GetAvailableAddonModules());

			if ($numAvailableAddons == 0) {
				$GLOBALS['ErrorTitle'] = GetLang('NoAddonPackages');
				$GLOBALS['Message'] = MessageBox(GetLang('SeeAddonPackages'), MSG_INFO);
				$this->template->display('error.tpl');
			}
			else {
				$GLOBALS['AddonJavaScript'] = "";
				$GLOBALS['AddonProviders'] = $this->GetAddonPackagesAsOptions();

				$GLOBALS['AddonSelectBoxSize'] = min($numAvailableAddons*4, 12);

				// Which addon modules are enabled?
				$packages = GetEnabledAddonModules();
				$GLOBALS['AddonTabs'] = "";
				$GLOBALS['AddonDivs'] = "";
				$count = 1;

				// Setup each addon module with its own tab
				foreach ($packages as $package) {
					$package['object']->init();
					$GLOBALS['AddonTabs'] .= sprintf('<li><a href="#" id="tab%d" onclick="ShowTab(%d)">%s</a></li>', $count, $count, $package['name']);
					$GLOBALS['AddonDivs'] .= sprintf('<div id="div%d" style="padding-top: 10px;">%s</div>', $count, $package['object']->getpropertiessheet($count));
					$count++;
				}

				if (isset($GLOBALS['TabIdsToHideButtonsFrom'])) {
					$GLOBALS['TabIdsToHideButtonsFrom'] = preg_replace("/,$/", "", $GLOBALS['TabIdsToHideButtonsFrom']);
				}

				$this->template->display('settings.addons.manage.tpl');
			}
		}

		private function SaveUpdatedAddonSettings()
		{
			// Delete existing module configuration
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('module_vars', "WHERE modulename LIKE 'addon\_%'");

			$enabledStack = array();
			$messages = array();

			// Can the selected addons be enabled?
			if (!isset($_POST['addonpackages']) || !is_array($_POST['addonpackages'])) {
				$_POST['addonpackages'] = array();
			}

			foreach ($_POST['addonpackages'] as $package) {
				$id = explode('_', $package, 2);
				GetAddonsModule($module, $id[1]);

				if (is_object($module)) {
				// Is this addon supported on this server?
					if($module->IsSupported() == false) {
						$errors = $module->GetErrors();
						foreach($errors as $error) {
							FlashMessage($error, MSG_ERROR);
						}
						continue;
					}

					// Otherwise, this addon is fine, so add it to the stack of enabled
					$enabledStack[] = 'addon_'.$id[1];
				}
			}

			$addonpackages = implode(",", $enabledStack);

			// Push everything to globals and save
			$GLOBALS['ISC_NEW_CFG']['AddonModules'] = $addonpackages;

			$messages = array();

			if ($this->CommitSettings($messages)) {
				// Now get all addon variables (they are in an array from $_POST)
				foreach($enabledStack as $module_id) {
					$vars = array();
					if(isset($_POST[$module_id])) {
						$vars = $_POST[$module_id];
					}

					GetModuleById('addon', $module, $module_id);
					$module->SaveModuleSettings($vars);
				}

				$tab = 0;
				if(isset($_POST['currentTab'])) {
					$tab = (int)$_POST['currentTab'];
				}

				if ($GLOBALS['ISC_CLASS_DB']->Error() == "") {
					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();

					// Redirect them so that any new modules appear in the menu straight away
					$success = true;
					$message = GetLang('AddonSettingsSavedSuccessfully');
				}
				else {
					$success = false;
					$message = GetLang('AddonSettingsNotSaved');
				}
			}
			else {
					$success = false;
					$message = GetLang('AddonSettingsNotSaved');
			}

			if($success == true) {
				$msgType = MSG_SUCCESS;
			}
			else {
				$msgType = MSG_ERROR;
			}

			// Rebuild the cache of the addon module variables
			$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateAddonModuleVars();

			FlashMessage($message, $msgType, 'index.php?ToDo=viewAddonSettings');
		}

		/**
		* Get a list of html options for use in a timezone list
		*
		* @param string the current timezone
		*
		* @return string The options for the timezones
		*/
		public function GetTimeZoneOptions($current='')
		{
			$option_template = '<option value="%1$s">%2$s</option>';
			$option_selected_template = '<option value="%1$s" selected="selected">%2$s</option>';

			$output = '';

			foreach ($this->timezones as $value => $zone) {
				if ($value != $current) {
					$output .= sprintf($option_template, $value, isc_html_escape(GetLang('TimeZone_'.$zone)));
				} else {
					$output .= sprintf($option_selected_template, $value, isc_html_escape(GetLang('TimeZone_'.$zone)));
				}
			}
			return $output;
		}

		private function ManageKBSettings($messages=array())
		{
			require_once(dirname(__FILE__) . "/class.pages.php");

			$GLOBALS['Message'] = GetFlashMessageBoxes();

			foreach ($this->all_vars as $var) {
				if (is_string(GetConfig($var)) || is_numeric(GetConfig($var))) {
					$GLOBALS[$var] = isc_html_escape(GetConfig($var));
				}
			}

			$GLOBALS['ISC_CLASS_ADMIN_PAGES'] = GetClass('ISC_ADMIN_PAGES');

			// Has ActiveKB been integrated?
			if (GetConfig('AKBIsConfigured')) {
				$GLOBALS['KBPath'] = GetConfig('AKBPath');
				$GLOBALS['CategoryOptions'] = $GLOBALS['ISC_CLASS_ADMIN_PAGES']->GetContactPagesAsOptions(explode(",", GetConfig('ARSPageIds')));

				if(GetConfig('ARSIntegrated') && strlen($GLOBALS['CategoryOptions']) != 0) {
					$GLOBALS['IsARSIntegrated'] = 'checked="checked"';
				}
			}
			else {
				$GLOBALS['KBPath'] = "http://";
				$GLOBALS['CategoryOptions'] = $GLOBALS['ISC_CLASS_ADMIN_PAGES']->GetContactPagesAsOptions();
			}

			$GLOBALS['KBSettingsIntro'] = sprintf(GetLang('KBSettingsIntro'), $GLOBALS['ShopPath'], $GLOBALS['ShopPath']);
			$GLOBALS['HideARSFields'] = "none";

			// If there aren't any contact pages yet we'll tell them they have to create one first
			if($GLOBALS['CategoryOptions'] == "") {
				$GLOBALS['CanIntegrateARS'] = "0";
			}

			$GLOBALS['KBLogo'] = GetConfig('KBLogo');

			$this->template->display('settings.kb.manage.tpl');
		}

		private function SaveUpdatedKBSettings()
		{
			$GLOBALS['ISC_NEW_CFG']['AKBPath'] = "";
			$GLOBALS['ISC_NEW_CFG']['ARSPageIds'] = 0;
			$GLOBALS['ISC_NEW_CFG']['ARSIntegrated'] = 0;

			$messages = array();

			if(isset($_POST['KBPath'])) {
				$kb_path = $_POST['KBPath'];
				$kb_path = str_replace("index.php", "", $kb_path);
				$kb_path = rtrim($kb_path, '/');
				$GLOBALS['ISC_NEW_CFG']['AKBPath'] = $kb_path;

				// Are we integrating it into any contact pages?
				if(isset($_POST['KBContactFormIntegration']) && $_POST['KBContactFormIntegration'] == "ON" && isset($_POST['pageids']) && is_array($_POST['pageids'])) {
					$GLOBALS['ISC_NEW_CFG']['ARSPageIds'] = implode(",", $_POST['pageids']);
					$GLOBALS['ISC_NEW_CFG']['ARSIntegrated'] = true;
				}

				$GLOBALS['ISC_NEW_CFG']['AKBIsConfigured'] = true;

				if($this->CommitSettings()) {
					$messages = array_merge(array(GetLang('AKBSettingsSavedSuccessfully') => MSG_SUCCESS), $messages);
				}
				else {
					$messages = array_merge(array(GetLang('AKBSettingsNotSaved') => MSG_ERROR), $messages);
				}

				$this->ManageKBSettings($messages);
			}
			else {
				$this->ManageKBSettings();
			}
		}

		/**
		 * Get the languages available for use as an array
		 *
		 * @return array An array with the 2 letter character codes as the values
		 **/
		public function GetAvailableLanguagesArray()
		{
			$langdir = ISC_BASE_PATH.'/language';
			$skip = Array (
				'.',
				'..',
				'CVS',
				'.svn',
			);
			$langs = array();

			$dh = opendir($langdir);
			while (($file = readdir($dh)) !== false) {
				if (in_array($file, $skip)) {
					continue;
				}

				if (!is_dir($langdir.'/'.$file)) {
					continue;
				}

				if (!is_file($langdir.'/'.$file.'/settings.ini')) {
					continue;
				}

				if (strlen($file) != 2) {
					continue;
				}

				$langs[] = $file;
			}
			return $langs;
		}

		/**
		* Get the list of options to show on the settings page
		*
		* @param string $selected The currently selected directory
		*
		* @return string The html options
		*/
		public function GetLanguageOptions($selected='en')
		{
			$output = '';
			$option_format = '<option value="%s"%s>%s</option>'."\n";

			// Full list of languages and their native names available at
			// http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes

			$available_langs = $this->GetAvailableLanguagesArray();

			foreach ($available_langs as $lang) {
				$settings = parse_ini_file(ISC_BASE_PATH.'/language/'.$lang.'/settings.ini');

				$native_name = $settings['native_name'];

				if ($lang == $selected) {
					$sel = ' selected';
				} else {
					$sel = '';
				}
				$output .= sprintf($option_format, $lang, $sel, isc_html_escape($native_name));
			}
			return $output;
		}

		/**
		* Allow the user to enter their affiliate tracking code which will be placed on the finishorder.php page
		*
		* @return Void
		*/
		private function ManageAffiliateSettings($messages=array())
		{
			$GLOBALS['Message'] = GetFlashMessageBoxes();

			$GLOBALS['AffiliateConversionTrackingCode'] = GetConfig("AffiliateConversionTrackingCode");
			$this->template->display('settings.affiliates.manage.tpl');
		}

		/**
		* Save the updated affiliate conversion tracking code to the config file
		*
		* @return Void
		*/
		private function SaveUpdatedAffiliateSettings()
		{
			$messages = array();

			if (isset($_POST['AffiliateConversionTrackingCode'])) {
				$GLOBALS['ISC_NEW_CFG']['AffiliateConversionTrackingCode'] = $_POST['AffiliateConversionTrackingCode'];

				if ($this->CommitSettings($messages)) {
					// Log this action
					$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();
					FlashMessage(GetLang('AffiliateSettingsSavedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewAffiliateSettings');
				}
				else {
					FlashMessage(sprintf(GetLang('AffiliateSettingsNotSaved'), $messages), MSG_ERROR, 'index.php?ToDo=viewAffiliateSettings');
				}
			}
			else {
				$this->ManageAffiliateSettings();
			}
		}
		
		private function CreateSelectNumeric($name, $start, $end, $interval, $selected="")
		{
			$select = "<select name=\"".$name."\" id=\"".$name."\">".PHP_EOL;
			
			for($i=$start;$i<=$end;$i+=$interval)
			{
				$select .= "\t<option value=\"".$i."\"";
				if($selected == $i) $select .= " selected=\"selected\"";
				$select .= ">".str_pad($i, 2, "0", STR_PAD_LEFT)."</option>".PHP_EOL;
			}
			
			$select .= "</select>".PHP_EOL;
			
			return $select;
		}
	}
