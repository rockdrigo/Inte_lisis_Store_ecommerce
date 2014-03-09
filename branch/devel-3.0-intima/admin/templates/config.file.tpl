
	$GLOBALS['ISC_CFG']["isSetup"] = true;
	$GLOBALS['ISC_CFG']["Language"] = {{ Language|safe }};
	$GLOBALS['ISC_CFG']["AllowPurchasing"] = {{ AllowPurchasing|safe }};
	$GLOBALS['ISC_CFG']["serverStamp"] = {{ serverStamp|safe }};
	$GLOBALS['ISC_CFG']["HostingProvider"] = {{ HostingProvider|safe }};
	$GLOBALS['ISC_CFG']["UseWYSIWYG"] = {{ UseWYSIWYG|safe }};
	$GLOBALS['ISC_CFG']["dbType"] = {{ dbType|safe }};
	$GLOBALS['ISC_CFG']["dbEncoding"] = {{ dbEncoding|safe }};
	$GLOBALS['ISC_CFG']["dbServer"] = {{ dbServer|safe }};
	$GLOBALS['ISC_CFG']["dbUser"] = {{ dbUser|safe }};
	$GLOBALS['ISC_CFG']["dbPass"] = {{ dbPass|safe }};
	$GLOBALS['ISC_CFG']["dbDatabase"] = {{ dbDatabase|safe }};
	$GLOBALS['ISC_CFG']["tablePrefix"] = {{ tablePrefix|safe }};
	$GLOBALS['ISC_CFG']["StoreName"] = {{ StoreName|safe }};
	$GLOBALS['ISC_CFG']["StoreAddress"] = {{ StoreAddress|safe }};
	$GLOBALS['ISC_CFG']["LogoType"] = {{ LogoType|safe }};
	$GLOBALS['ISC_CFG']["StoreLogo"] = {{ StoreLogo|safe }};
	$GLOBALS['ISC_CFG']["ShopPath"] = {{ ShopPath|safe }};
	$GLOBALS['ISC_CFG']["CharacterSet"] = {{ CharacterSet|safe }};
	$GLOBALS['ISC_CFG']["HomePagePageTitle"] = {{ HomePagePageTitle|safe }};
	$GLOBALS['ISC_CFG']["MetaKeywords"] = {{ MetaKeywords|safe }};
	$GLOBALS['ISC_CFG']["MetaDesc"] = {{ MetaDesc|safe }};
	$GLOBALS['ISC_CFG']["DownloadDirectory"] = {{ DownloadDirectory|safe }};
	$GLOBALS['ISC_CFG']["ImageDirectory"] = {{ ImageDirectory|safe }};
	$GLOBALS['ISC_CFG']["template"] = {{ template|safe }};
	$GLOBALS['ISC_CFG']["SiteColor"] = {{ SiteColor|safe }};
	$GLOBALS['ISC_CFG']["CurrencyToken"] = {{ CurrencyToken|safe }};
	$GLOBALS['ISC_CFG']["CurrencyLocation"] = {{ CurrencyLocation|safe }};
	$GLOBALS['ISC_CFG']["DecimalToken"] = {{ DecimalToken|safe }};
	$GLOBALS['ISC_CFG']["DecimalPlaces"] = {{ DecimalPlaces|safe }};
	$GLOBALS['ISC_CFG']["ThousandsToken"] = {{ ThousandsToken|safe }};
	$GLOBALS['ISC_CFG']["InstallDate"] = {{ InstallDate|safe }};

	// SSL Settings
	$GLOBALS['ISC_CFG']["UseSSL"] = {{ UseSSL|safe }};
	$GLOBALS['ISC_CFG']["SharedSSLPath"] = {{ SharedSSLPath|safe }};
	$GLOBALS['ISC_CFG']["SubdomainSSLPath"] = {{ SubdomainSSLPath|safe }};
	$GLOBALS['ISC_CFG']["ForceControlPanelSSL"] = {{ ForceControlPanelSSL|safe }};

	// Physical Dimensions Settings
	$GLOBALS['ISC_CFG']["WeightMeasurement"] = {{ WeightMeasurement|safe }};
	$GLOBALS['ISC_CFG']["LengthMeasurement"] = {{ LengthMeasurement|safe }};
	$GLOBALS['ISC_CFG']["DimensionsDecimalToken"] = {{ DimensionsDecimalToken|safe }};
	$GLOBALS['ISC_CFG']["DimensionsDecimalPlaces"] = {{ DimensionsDecimalPlaces|safe }};
	$GLOBALS['ISC_CFG']["DimensionsThousandsToken"] = {{ DimensionsThousandsToken|safe }};

	$GLOBALS['ISC_CFG']["DisplayDateFormat"] = {{ DisplayDateFormat|safe }};
	$GLOBALS['ISC_CFG']["ExportDateFormat"] = {{ ExportDateFormat|safe }};
	$GLOBALS['ISC_CFG']["ExtendedDisplayDateFormat"] = {{ ExtendedDisplayDateFormat|safe }};
	$GLOBALS['ISC_CFG']["HomeFeaturedProducts"] = {{ HomeFeaturedProducts|safe }};
	$GLOBALS['ISC_CFG']["HomeNewProducts"] = {{ HomeNewProducts|safe }};
	
	// REQ11064 JIB: Agrege la variable de HomePopularProducts en las variables globales
	$GLOBALS['ISC_CFG']["HomePopularProducts"] = {{ HomePopularProducts|safe }};
	//?
	$GLOBALS['ISC_CFG']["HomeBlogPosts"] = {{ HomeBlogPosts|safe }};
	$GLOBALS['ISC_CFG']["CategoryProductsPerPage"] = {{ CategoryProductsPerPage|safe }};
	$GLOBALS['ISC_CFG']["CategoryListDepth"] = {{ CategoryListDepth|safe }};
	$GLOBALS['ISC_CFG']["ProductReviewsPerPage"] = {{ ProductReviewsPerPage|safe }};
	$GLOBALS['ISC_CFG']["TagCloudsEnabled"] = {{ TagCloudsEnabled|safe }};
	$GLOBALS['ISC_CFG']["ShowAddToCartQtyBox"] = {{ ShowAddToCartQtyBox|safe }};
	$GLOBALS['ISC_CFG']["CaptchaEnabled"] = {{ CaptchaEnabled|safe }};
	$GLOBALS['ISC_CFG']["ShowCartSuggestions"] = {{ ShowCartSuggestions|safe }};
	$GLOBALS['ISC_CFG']["AdminEmail"] = {{ AdminEmail|safe }};
	$GLOBALS['ISC_CFG']["OrderEmail"] = {{ OrderEmail|safe }};
	$GLOBALS['ISC_CFG']['LowInventoryNotificationAddress'] = {{ LowInventoryNotificationAddress|safe }};
	$GLOBALS['ISC_CFG']["ShowThumbsInCart"] = {{ ShowThumbsInCart|safe }};
	$GLOBALS['ISC_CFG']["AutoApproveReviews"] = {{ AutoApproveReviews|safe }};
	$GLOBALS['ISC_CFG']["SearchSuggest"] = {{ SearchSuggest|safe }};
	$GLOBALS['ISC_CFG']["QuickSearch"] = {{ QuickSearch|safe }};

	// Shipping Settings
	$GLOBALS['ISC_CFG']["CompanyName"] = {{ CompanyName|safe }};
	$GLOBALS['ISC_CFG']["CompanyAddress"] = {{ CompanyAddress|safe }};
	$GLOBALS['ISC_CFG']["CompanyCity"] = {{ CompanyCity|safe }};
	$GLOBALS['ISC_CFG']["CompanyCountry"] = {{ CompanyCountry|safe }};
	$GLOBALS['ISC_CFG']["CompanyState"] = {{ CompanyState|safe }};
	$GLOBALS['ISC_CFG']["CompanyZip"] = {{ CompanyZip|safe }};

	// Checkout Settings
	$GLOBALS['ISC_CFG']["CheckoutMethods"] = {{ CheckoutMethods|safe }};
	$GLOBALS['ISC_CFG']['CheckoutType'] = {{ CheckoutType|safe }};
	$GLOBALS['ISC_CFG']['GuestCheckoutEnabled'] = {{ GuestCheckoutEnabled|safe }};
	$GLOBALS['ISC_CFG']['GuestCheckoutCreateAccounts'] = {{ GuestCheckoutCreateAccounts|safe }};

	$GLOBALS['ISC_CFG']["EmailIntegrationMethods"] = {{ EmailIntegrationMethods|safe }};
	$GLOBALS['ISC_CFG']["EmailIntegrationNewsletterDoubleOptin"] = {{ EmailIntegrationNewsletterDoubleOptin|safe }};
	$GLOBALS['ISC_CFG']["EmailIntegrationNewsletterSendWelcome"] = {{ EmailIntegrationNewsletterSendWelcome|safe }};
	$GLOBALS['ISC_CFG']["EmailIntegrationOrderDoubleOptin"] = {{ EmailIntegrationOrderDoubleOptin|safe }};
	$GLOBALS['ISC_CFG']["EmailIntegrationOrderSendWelcome"] = {{ EmailIntegrationOrderSendWelcome|safe }};

	$GLOBALS['ISC_CFG']["ShowThumbsInControlPanel"] = {{ ShowThumbsInControlPanel|safe }};
	$GLOBALS['ISC_CFG']["EnableSEOUrls"] = {{ EnableSEOUrls|safe }};
	$GLOBALS['ISC_CFG']['ShowInventory'] = {{ ShowInventory|safe }};
	$GLOBALS['ISC_CFG']['ShowPreOrderInventory'] = {{ ShowPreOrderInventory|safe }};
	$GLOBALS['ISC_CFG']['DefaultPreOrderMessage'] = {{ DefaultPreOrderMessage|safe }};
	$GLOBALS['ISC_CFG']['StoreTimeZone'] = {{ StoreTimeZone|safe }};
	$GLOBALS['ISC_CFG']['StoreDSTCorrection'] = {{ StoreDSTCorrection|safe }};
	$GLOBALS['ISC_CFG']['ShowDownloadTemplates'] = {{ ShowDownloadTemplates|safe }};
	$GLOBALS['ISC_CFG']['TagCartQuantityBoxes'] = {{ TagCartQuantityBoxes|safe }};
	$GLOBALS['ISC_CFG']['ProductBreadcrumbs'] = {{ ProductBreadcrumbs|safe }};
	$GLOBALS['ISC_CFG']['FastCartAction'] = {{ FastCartAction|safe }};

	$GLOBALS['ISC_CFG']["RSSNewProducts"] = {{ RSSNewProducts|safe }};
	$GLOBALS['ISC_CFG']["RSSPopularProducts"] = {{ RSSPopularProducts|safe }};
	$GLOBALS['ISC_CFG']["RSSFeaturedProducts"] = {{ RSSFeaturedProducts|safe }};
	$GLOBALS['ISC_CFG']["RSSCategories"] = {{ RSSCategories|safe }};
	$GLOBALS['ISC_CFG']["RSSProductSearches"] = {{ RSSProductSearches|safe }};
	$GLOBALS['ISC_CFG']["RSSLatestBlogEntries"] = {{ RSSLatestBlogEntries|safe }};
	$GLOBALS['ISC_CFG']["RSSItemsLimit"] = {{ RSSItemsLimit|safe }};
	$GLOBALS['ISC_CFG']["RSSCacheTime"] = {{ RSSCacheTime|safe }};
	$GLOBALS['ISC_CFG']["RSSSyndicationIcons"] = {{ RSSSyndicationIcons|safe }};

	$GLOBALS['ISC_CFG']['BackupsLocal'] = {{ BackupsLocal|safe }};
	$GLOBALS['ISC_CFG']['BackupsRemoteFTP'] = {{ BackupsRemoteFTP|safe }};
	$GLOBALS['ISC_CFG']['BackupsRemoteFTPHost'] = {{ BackupsRemoteFTPHost|safe }};
	$GLOBALS['ISC_CFG']['BackupsRemoteFTPUser'] = {{ BackupsRemoteFTPUser|safe }};
	$GLOBALS['ISC_CFG']['BackupsRemoteFTPPass'] = {{ BackupsRemoteFTPPass|safe }};
	$GLOBALS['ISC_CFG']['BackupsRemoteFTPPath'] = {{ BackupsRemoteFTPPath|safe }};
	$GLOBALS['ISC_CFG']['BackupsAutomatic'] = {{ BackupsAutomatic|safe }};
	$GLOBALS['ISC_CFG']['BackupsAutomaticMethod'] = {{ BackupsAutomaticMethod|safe }};
	$GLOBALS['ISC_CFG']['BackupsAutomaticDatabase'] = {{ BackupsAutomaticDatabase|safe }};
	$GLOBALS['ISC_CFG']['BackupsAutomaticImages'] = {{ BackupsAutomaticImages|safe }};
	$GLOBALS['ISC_CFG']['BackupsAutomaticDownloads'] = {{ BackupsAutomaticDownloads|safe }};

	$GLOBALS['ISC_CFG']["GoogleMapsAPIKey"] = {{ GoogleMapsAPIKey|safe }};
	$GLOBALS['ISC_CFG']["NotificationMethods"] = {{ NotificationMethods|safe }};
	$GLOBALS['ISC_CFG']["CurrencyMethods"] = {{ CurrencyMethods|safe }};
	$GLOBALS['ISC_CFG']["DefaultCurrencyID"] = {{ DefaultCurrencyID|safe }};
	$GLOBALS['ISC_CFG']["DefaultCurrencyRate"] = {{ DefaultCurrencyRate|safe }};

	$GLOBALS['ISC_CFG']["MailAutomaticallyTickNewsletterBox"] = {{ MailAutomaticallyTickNewsletterBox|safe }};
	$GLOBALS['ISC_CFG']["MailAutomaticallyTickOrderBox"] = {{ MailAutomaticallyTickOrderBox|safe }};
	$GLOBALS['ISC_CFG']['ShowMailingListInvite'] = {{ ShowMailingListInvite|safe }};

	$GLOBALS['ISC_CFG']["AnalyticsMethods"] = {{ AnalyticsMethods|safe }};

	$GLOBALS['ISC_CFG']['SystemLogging'] = {{ SystemLogging|safe }};
	$GLOBALS['ISC_CFG']['HidePHPErrors'] = {{ HidePHPErrors|safe }};
	$GLOBALS['ISC_CFG']['SystemLogTypes'] = {{ SystemLogTypes|safe }};
	$GLOBALS['ISC_CFG']['SystemLogSeverity'] = {{ SystemLogSeverity|safe }};
	$GLOBALS['ISC_CFG']['SystemLogMaxLength'] = {{ SystemLogMaxLength|safe }};
	$GLOBALS['ISC_CFG']['AdministratorLogging'] = {{ AdministratorLogging|safe }};
	$GLOBALS['ISC_CFG']['AdministratorLogMaxLength'] = {{ AdministratorLogMaxLength|safe }};
	$GLOBALS['ISC_CFG']['DebugMode'] = {{ DebugMode|safe }};

	$GLOBALS['ISC_CFG']['EnableReturns'] = {{ EnableReturns|safe }};
	$GLOBALS['ISC_CFG']['ReturnReasons'] = {{ ReturnReasons|safe }};
	$GLOBALS['ISC_CFG']['ReturnActions'] = {{ ReturnActions|safe }};
	$GLOBALS['ISC_CFG']['ReturnCredits'] = {{ ReturnCredits|safe }};
	$GLOBALS['ISC_CFG']['ReturnInstructions'] = {{ ReturnInstructions|safe }};
	$GLOBALS['ISC_CFG']['EmailOwnerOnReturn'] = {{ EmailOwnerOnReturn|safe }};
	$GLOBALS['ISC_CFG']['SendReturnConfirmation'] = {{ SendReturnConfirmation|safe }};
	$GLOBALS['ISC_CFG']['NotifyOnReturnStatusChange'] = {{ NotifyOnReturnStatusChange|safe }};

	$GLOBALS['ISC_CFG']['EnableGiftCertificates'] = {{ EnableGiftCertificates|safe }};
	$GLOBALS['ISC_CFG']['GiftCertificateAmounts'] = {{ GiftCertificateAmounts|safe }};
	$GLOBALS['ISC_CFG']['GiftCertificateCustomAmounts'] = {{ GiftCertificateCustomAmounts|safe }};
	$GLOBALS['ISC_CFG']['GiftCertificateMinimum'] = {{ GiftCertificateMinimum|safe }};
	$GLOBALS['ISC_CFG']['GiftCertificateMaximum'] = {{ GiftCertificateMaximum|safe }};
	$GLOBALS['ISC_CFG']['GiftCertificateExpiry'] = {{ GiftCertificateExpiry|safe }};
	$GLOBALS['ISC_CFG']['GiftCertificateThemes'] = {{ GiftCertificateThemes|safe }};
	$GLOBALS['ISC_CFG']['GiftCertificateCustomDirectory'] = {{ GiftCertificateCustomDirectory|safe }};
	$GLOBALS['ISC_CFG']['GiftCertificateMasterDirectory'] = {{ GiftCertificateMasterDirectory|safe }};

	$GLOBALS['ISC_CFG']['UpdateInventoryLevels'] = {{ UpdateInventoryLevels|safe }};
	$GLOBALS['ISC_CFG']['UpdateInventoryOnOrderEdit'] = {{ UpdateInventoryOnOrderEdit|safe }};
	$GLOBALS['ISC_CFG']['UpdateInventoryOnOrderDelete'] = {{ UpdateInventoryOnOrderDelete|safe }};
	$GLOBALS['ISC_CFG']['OrderStatusNotifications'] = {{ OrderStatusNotifications|safe }};

	$GLOBALS['ISC_CFG']['AddonModules'] = {{ AddonModules|safe }};

	$GLOBALS['ISC_CFG']['AKBIsConfigured'] = {{ AKBIsConfigured|safe }};
	$GLOBALS['ISC_CFG']['AKBPath'] = {{ AKBPath|safe }};
	$GLOBALS['ISC_CFG']['ARSPageIds'] = {{ ARSPageIds|safe }};
	$GLOBALS['ISC_CFG']['ARSIntegrated'] = {{ ARSIntegrated|safe }};

	$GLOBALS['ISC_CFG']['ShowProductPrice'] = {{ ShowProductPrice|safe }};
	$GLOBALS['ISC_CFG']['ShowPriceGuest'] = {{ ShowPriceGuest|safe }};
	$GLOBALS['ISC_CFG']['ShowProductSKU'] = {{ ShowProductSKU|safe }};
	$GLOBALS['ISC_CFG']['ShowProductWeight'] = {{ ShowProductWeight|safe }};
	$GLOBALS['ISC_CFG']['ShowProductBrand'] = {{ ShowProductBrand|safe }};
	$GLOBALS['ISC_CFG']['ShowProductShipping'] = {{ ShowProductShipping|safe }};
	$GLOBALS['ISC_CFG']['ShowProductRating'] = {{ ShowProductRating|safe }};
	$GLOBALS['ISC_CFG']['ProductImageMode'] = {{ ProductImageMode|safe }};

	$GLOBALS['ISC_CFG']['ShowAddThisLink'] = {{ ShowAddThisLink|safe }};

	// DO NOT CHANGE THIS VARIABLE OR YOU WILL BREAK ORDERS
	$GLOBALS['ISC_CFG']["EncryptionToken"] = {{ EncryptionToken|safe }};

	$GLOBALS['ISC_CFG']["EnableWishlist"] = {{ EnableWishlist|safe }};
	$GLOBALS['ISC_CFG']["EnableAccountCreation"] = {{ EnableAccountCreation|safe }};
	$GLOBALS['ISC_CFG']['EnableProductComparisons'] = {{ EnableProductComparisons|safe }};
	$GLOBALS['ISC_CFG']["EnableOrderComments"] = {{ EnableOrderComments|safe }};
	$GLOBALS['ISC_CFG']["EnableOrderTermsAndConditions"] = {{ EnableOrderTermsAndConditions|safe }};
	$GLOBALS['ISC_CFG']["OrderTermsAndConditionsType"] = {{ OrderTermsAndConditionsType|safe }};
	$GLOBALS['ISC_CFG']["OrderTermsAndConditionsLink"] = {{ OrderTermsAndConditionsLink|safe }};
	$GLOBALS['ISC_CFG']["OrderTermsAndConditions"] = {{ OrderTermsAndConditions|safe }};

	// Logo Settings
	$GLOBALS['ISC_CFG']["LogoFields"] = {{ LogoFields|safe }};
	$GLOBALS['ISC_CFG']["ForceWebsiteTitleText"] = {{ ForceWebsiteTitleText|safe }};
	$GLOBALS['ISC_CFG']['UseAlternateTitle'] = {{ UseAlternateTitle|safe }};
	$GLOBALS['ISC_CFG']['AlternateTitle'] = {{ AlternateTitle|safe }};
	$GLOBALS['ISC_CFG']['UsingLogoEditor'] = {{ UsingLogoEditor|safe }};
	$GLOBALS['ISC_CFG']['UsingTemplateLogo'] = {{ UsingTemplateLogo|safe }};

	$GLOBALS['ISC_CFG']['AffiliateConversionTrackingCode'] = {{ AffiliateConversionTrackingCode|safe }};

	$GLOBALS['ISC_CFG']['GuestCustomerGroup'] = {{ GuestCustomerGroup|safe }};
	$GLOBALS['ISC_CFG']['ForwardInvoiceEmails'] = {{ ForwardInvoiceEmails|safe }};

	// Mail Settings
	$GLOBALS['ISC_CFG']['MailUseSMTP'] = {{ MailUseSMTP|safe }};
	$GLOBALS['ISC_CFG']['MailSMTPServer'] = {{ MailSMTPServer|safe }};
	$GLOBALS['ISC_CFG']['MailSMTPUsername'] = {{ MailSMTPUsername|safe }};
	$GLOBALS['ISC_CFG']['MailSMTPPassword'] = {{ MailSMTPPassword|safe }};
	$GLOBALS['ISC_CFG']['MailSMTPPort'] = {{ MailSMTPPort|safe }};

	// Curl Proxy Settings
	$GLOBALS['ISC_CFG']['HTTPProxyServer'] = {{ HTTPProxyServer|safe }};
	$GLOBALS['ISC_CFG']['HTTPProxyPort'] = {{ HTTPProxyPort|safe }};
	$GLOBALS['ISC_CFG']['HTTPSSLVerifyPeer'] = {{ HTTPSSLVerifyPeer|safe }};

	// Digital Download Settings
	$GLOBALS['ISC_CFG']['DigitalOrderHandlingFee'] = {{ DigitalOrderHandlingFee|safe }};

	// Accounting Settings
	$GLOBALS['ISC_CFG']['AccountingMethods'] = {{ AccountingMethods|safe }};

	// Live Chat Modules
	$GLOBALS['ISC_CFG']['LiveChatModules'] = {{ LiveChatModules|safe }};

	//Category and Brand image dimensions
	$GLOBALS['ISC_CFG']['CategoryPerRow'] = {{ CategoryPerRow|safe }};
	$GLOBALS['ISC_CFG']['CategoryImageWidth'] = {{ CategoryImageWidth|safe }};
	$GLOBALS['ISC_CFG']['CategoryImageHeight'] = {{ CategoryImageHeight|safe }};
	$GLOBALS['ISC_CFG']['CategoryDefaultImage'] = {{ CategoryDefaultImage|safe }};
	$GLOBALS['ISC_CFG']['BrandPerRow'] = {{ BrandPerRow|safe }};
	$GLOBALS['ISC_CFG']['BrandImageWidth'] = {{ BrandImageWidth|safe }};
	$GLOBALS['ISC_CFG']['BrandImageHeight'] = {{ BrandImageHeight|safe }};
	$GLOBALS['ISC_CFG']['BrandDefaultImage'] = {{ BrandDefaultImage|safe }};

	// Product Images
	$GLOBALS['ISC_CFG']['DefaultProductImage'] = {{ DefaultProductImage|safe }};

	//Display the 'Add to Cart' link on all the product panels
	$GLOBALS['ISC_CFG']['ShowAddToCartLink'] = {{ ShowAddToCartLink|safe }};

	$GLOBALS['ISC_CFG']['CategoryListingMode'] = {{ CategoryListingMode|safe }};
	$GLOBALS['ISC_CFG']['CategoryDisplayMode'] = {{ CategoryDisplayMode|safe }};
	$GLOBALS['ISC_CFG']['TagCloudMinSize'] = {{ TagCloudMinSize|safe }};
	$GLOBALS['ISC_CFG']['TagCloudMaxSize'] = {{ TagCloudMaxSize|safe }};

	// Bulk Discounts
	$GLOBALS['ISC_CFG']['BulkDiscountEnabled'] = {{ BulkDiscountEnabled|safe }};

	$GLOBALS['ISC_CFG']['EnableProductTabs'] = {{ EnableProductTabs|safe }};

	$GLOBALS['ISC_CFG']['MultipleShippingAddresses'] = {{ MultipleShippingAddresses|safe }};

	// Vendor Edition Settings
	$GLOBALS['ISC_CFG']['VendorLogoSize'] = {{ VendorLogoSize|safe }};
	$GLOBALS['ISC_CFG']['VendorPhotoSize'] = {{ VendorPhotoSize|safe }};

	// The factoring dimension for a shipping quote (depth, height or width with default of depth)
	$GLOBALS['ISC_CFG']['ShippingFactoringDimension'] = {{ ShippingFactoringDimension|safe }};

	// Array of the getting started steps that have been completed
	$GLOBALS['ISC_CFG']['GettingStartedCompleted'] = {{ GettingStartedCompleted|safe }};

	// The favicon file
	$GLOBALS['ISC_CFG']['Favicon'] = {{ Favicon|safe }};

	// Session settings
	$GLOBALS['ISC_CFG']['SessionSavePath'] = {{ SessionSavePath|safe }};

	// Optimizer Settings
	$GLOBALS['ISC_CFG']['OptimizerMethods'] = {{ OptimizerMethods|safe }};

	// Advance Search format (search all)
	$GLOBALS['ISC_CFG']['SearchDefaultProductSort'] = {{ SearchDefaultProductSort|safe }};
	$GLOBALS['ISC_CFG']['SearchDefaultContentSort'] = {{ SearchDefaultContentSort|safe }};
	$GLOBALS['ISC_CFG']['SearchProductDisplayMode'] = {{ SearchProductDisplayMode|safe }};
	$GLOBALS['ISC_CFG']['SearchResultsPerPage'] = {{ SearchResultsPerPage|safe }};
	$GLOBALS['ISC_CFG']['SearchOptimisation'] = {{ SearchOptimisation|safe }};

	// Abandon Orders
	$GLOBALS['ISC_CFG']['AbandonOrderLifetime'] = {{ AbandonOrderLifetime|safe }};

	// Product Image Settings
	$GLOBALS['ISC_CFG']['ProductImagesStorewideThumbnail_width'] = {{ ProductImagesStorewideThumbnail_width|safe }};
	$GLOBALS['ISC_CFG']['ProductImagesStorewideThumbnail_height'] = {{ ProductImagesStorewideThumbnail_height|safe }};
	$GLOBALS['ISC_CFG']['ProductImagesStorewideThumbnail_timeChanged'] = {{ ProductImagesStorewideThumbnail_timeChanged|safe }};
	$GLOBALS['ISC_CFG']['ProductImagesProductPageImage_width'] = {{ ProductImagesProductPageImage_width|safe }};
	$GLOBALS['ISC_CFG']['ProductImagesProductPageImage_height'] = {{ ProductImagesProductPageImage_height|safe }};
	$GLOBALS['ISC_CFG']['ProductImagesProductPageImage_timeChanged'] = {{ ProductImagesProductPageImage_timeChanged|safe }};
	$GLOBALS['ISC_CFG']['ProductImagesGalleryThumbnail_width'] = {{ ProductImagesGalleryThumbnail_width|safe }};
	$GLOBALS['ISC_CFG']['ProductImagesGalleryThumbnail_height'] = {{ ProductImagesGalleryThumbnail_height|safe }};
	$GLOBALS['ISC_CFG']['ProductImagesGalleryThumbnail_timeChanged'] = {{ ProductImagesGalleryThumbnail_timeChanged|safe }};
	$GLOBALS['ISC_CFG']['ProductImagesZoomImage_width'] = {{ ProductImagesZoomImage_width|safe }};
	$GLOBALS['ISC_CFG']['ProductImagesZoomImage_height'] = {{ ProductImagesZoomImage_height|safe }};
	$GLOBALS['ISC_CFG']['ProductImagesZoomImage_timeChanged'] = {{ ProductImagesZoomImage_timeChanged|safe }};
	$GLOBALS['ISC_CFG']['ProductImagesTinyThumbnailsEnabled'] = {{ ProductImagesTinyThumbnailsEnabled|safe }};
	$GLOBALS['ISC_CFG']['ProductImagesImageZoomEnabled'] = {{ ProductImagesImageZoomEnabled|safe }};

	// Variable used to force browsers to re-download already cached
	// stylesheets/Javascript. Set to a random value during the upgrade.
	$GLOBALS['ISC_CFG']['JSCacheToken'] = {{ JSCacheToken|safe }};

	// Shopping Comparison
	$GLOBALS['ISC_CFG']['ShoppingComparisonModules'] = {{ ShoppingComparisonModules|safe }};

	// Maintenance
	$GLOBALS['ISC_CFG']["DownForMaintenance"] = {{ DownForMaintenance|safe }};
	$GLOBALS['ISC_CFG']["DownForMaintenanceMessage"] = {{ DownForMaintenanceMessage|safe }};

	// Starting Order Number
	$GLOBALS['ISC_CFG']["StartingOrderNumber"] = {{ StartingOrderNumber|safe }};

	// Shipping Manager Settings
	$GLOBALS['ISC_CFG']['ShippingManagerModules'] = {{ ShippingManagerModules|safe }};

	// 'Customers who viewed this product also viewed' Settings
	$GLOBALS['ISC_CFG']['EnableCustomersAlsoViewed'] = {{ EnableCustomersAlsoViewed|safe }};
	$GLOBALS['ISC_CFG']['CustomersAlsoViewedCount'] = {{ CustomersAlsoViewedCount|safe }};

	// Ebay Settings
	$GLOBALS['ISC_CFG']['EbayDevId'] = {{ EbayDevId|safe }};
	$GLOBALS['ISC_CFG']['EbayAppId'] = {{ EbayAppId|safe }};
	$GLOBALS['ISC_CFG']['EbayCertId'] = {{ EbayCertId|safe }};
	$GLOBALS['ISC_CFG']['EbayUserToken'] = {{ EbayUserToken|safe }};
	$GLOBALS['ISC_CFG']['EbayDefaultSite'] = {{ EbayDefaultSite|safe }};
	$GLOBALS['ISC_CFG']['EbayStore'] = {{ EbayStore|safe }};
	$GLOBALS['ISC_CFG']['EbayTestMode'] = {{ EbayTestMode|safe }};
	$GLOBALS['ISC_CFG']['EbaySettingsValid'] = {{ EbaySettingsValid|safe }};

	// Comment System Settings
	$GLOBALS['ISC_CFG']['CommentSystemModule'] = {{ CommentSystemModule|safe }};

	// Redirect to www or no www
	$GLOBALS['ISC_CFG']['RedirectWWW'] = {{ RedirectWWW|safe }};

	// Tax Settings
	$GLOBALS['ISC_CFG']['taxLabel'] = {{ taxLabel|safe }};
	$GLOBALS['ISC_CFG']['taxEnteredWithPrices'] = {{ taxEnteredWithPrices|safe }};
	$GLOBALS['ISC_CFG']['taxCalculationBasedOn'] = {{ taxCalculationBasedOn|safe }};
	$GLOBALS['ISC_CFG']['taxDefaultTaxDisplayCatalog'] = {{ taxDefaultTaxDisplayCatalog|safe }};
	$GLOBALS['ISC_CFG']['taxDefaultTaxDisplayProducts'] = {{ taxDefaultTaxDisplayProducts|safe }};
	$GLOBALS['ISC_CFG']['taxDefaultTaxDisplayCart'] = {{ taxDefaultTaxDisplayCart|safe }};
	$GLOBALS['ISC_CFG']['taxDefaultTaxDisplayOrders'] = {{ taxDefaultTaxDisplayOrders|safe }};
	$GLOBALS['ISC_CFG']['taxChargesOnOrdersBreakdown'] = {{ taxChargesOnOrdersBreakdown|safe }};
	$GLOBALS['ISC_CFG']['taxChargesInCartBreakdown'] = {{ taxChargesInCartBreakdown|safe }};
	$GLOBALS['ISC_CFG']['taxDefaultCountry'] = {{ taxDefaultCountry|safe }};
	$GLOBALS['ISC_CFG']['taxDefaultState'] = {{ taxDefaultState|safe }};
	$GLOBALS['ISC_CFG']['taxDefaultZipCode'] = {{ taxDefaultZipCode|safe }};
	$GLOBALS['ISC_CFG']['taxPendingChanges'] = {{ taxPendingChanges|safe }};
	$GLOBALS['ISC_CFG']['taxShippingTaxClass'] = {{ taxShippingTaxClass|safe }};
	$GLOBALS['ISC_CFG']['taxGiftWrappingTaxClass'] = {{ taxGiftWrappingTaxClass|safe }};
	$GLOBALS['ISC_CFG']['taxPendingChanges'] = {{ taxPendingChanges|safe }};

	// PCI config
	$GLOBALS['ISC_CFG']['PCIPasswordMinLen'] = {{ PCIPasswordMinLen|safe }};
	$GLOBALS['ISC_CFG']['PCIPasswordHistoryCount'] = {{ PCIPasswordHistoryCount|safe }};
	$GLOBALS['ISC_CFG']['PCIPasswordExpiryTimeDay'] = {{ PCIPasswordExpiryTimeDay|safe }};
	$GLOBALS['ISC_CFG']['PCILoginAttemptCount'] = {{ PCILoginAttemptCount|safe }};
	$GLOBALS['ISC_CFG']['PCILoginLockoutTimeMin'] = {{ PCILoginLockoutTimeMin|safe }};
	$GLOBALS['ISC_CFG']['PCILoginIdleTimeMin'] = {{ PCILoginIdleTimeMin|safe }};
	$GLOBALS['ISC_CFG']['PCILoginInactiveTimeDay'] = {{ PCILoginInactiveTimeDay|safe }};

	// Mobile/Portable Template
	$GLOBALS['ISC_CFG']['enableMobileTemplate'] = {{ enableMobileTemplate|safe }};
	$GLOBALS['ISC_CFG']['enableMobileTemplateDevices'] = {{ enableMobileTemplateDevices|safe }};
	$GLOBALS['ISC_CFG']['mobileTemplateLogo'] = {{ mobileTemplateLogo|safe }};

	// Facebook Like Button
	$GLOBALS['ISC_CFG']['FacebookLikeButtonEnabled'] = {{ FacebookLikeButtonEnabled|safe }};
	$GLOBALS['ISC_CFG']['FacebookLikeButtonStyle'] = {{ FacebookLikeButtonStyle|safe }};
	$GLOBALS['ISC_CFG']['FacebookLikeButtonPosition'] = {{ FacebookLikeButtonPosition|safe }};
	$GLOBALS['ISC_CFG']['FacebookLikeButtonVerb'] = {{ FacebookLikeButtonVerb|safe }};
	$GLOBALS['ISC_CFG']['FacebookLikeButtonShowFaces'] = {{ FacebookLikeButtonShowFaces|safe }};
	$GLOBALS['ISC_CFG']['FacebookLikeButtonAdminIds'] = {{ FacebookLikeButtonAdminIds|safe }};

	// Deleted orders handling
	$GLOBALS['ISC_CFG']['DeletedOrdersAction'] = {{ DeletedOrdersAction|safe }};

	// Category flyout menu configuration
	$GLOBALS['ISC_CFG']['CategoryListStyle'] = {{ CategoryListStyle|safe }};
	$GLOBALS['ISC_CFG']['categoryFlyoutMouseOutDelay'] = {{ categoryFlyoutMouseOutDelay|safe }};
	$GLOBALS['ISC_CFG']['categoryFlyoutDropShadow'] = {{ categoryFlyoutDropShadow|safe }};

	// Added by Nissim, Store Hours
	$GLOBALS['ISC_CFG']['UseStoreHours'] = {{ UseStoreHours|safe }};
	$GLOBALS['ISC_CFG']['StoreHoursFromHours'] = {{ StoreHoursFromHours|safe }};
	$GLOBALS['ISC_CFG']['StoreHoursFromMinutes'] = {{ StoreHoursFromMinutes|safe }};
	$GLOBALS['ISC_CFG']['StoreHoursToHours'] = {{ StoreHoursToHours|safe }};
	$GLOBALS['ISC_CFG']['StoreHoursToMinutes'] = {{ StoreHoursToMinutes|safe }};
	$GLOBALS['ISC_CFG']['StoreClosed'] = {{ StoreClosed|safe }};

	// Extra fields on checkout for new orders
	$GLOBALS['ISC_CFG']['CheckoutUseExtraFields'] = {{ CheckoutUseExtraFields|safe }};
	
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldActive1'] = {{ CheckoutExtraFieldActive1|safe }};
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldName1'] = {{ CheckoutExtraFieldName1|safe }};
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldType1'] = {{ CheckoutExtraFieldType1|safe }};
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldValue1'] = {{ CheckoutExtraFieldValue1|safe }};
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldRequired1'] = {{ CheckoutExtraFieldRequired1|safe }};
	
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldActive2'] = {{ CheckoutExtraFieldActive2|safe }}; 
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldName2'] = {{ CheckoutExtraFieldName2|safe }}; 
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldType2'] = {{ CheckoutExtraFieldType2|safe }}; 
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldValue2'] = {{ CheckoutExtraFieldValue2|safe }};
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldRequired2'] = {{ CheckoutExtraFieldRequired2|safe }};
	
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldActive3'] = {{ CheckoutExtraFieldActive3|safe }}; 
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldName3'] = {{ CheckoutExtraFieldName3|safe }}; 
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldType3'] = {{ CheckoutExtraFieldType3|safe }};
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldValue3'] = {{ CheckoutExtraFieldValue3|safe }};
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldRequired3'] = {{ CheckoutExtraFieldRequired3|safe }};
	
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldActive4'] = {{ CheckoutExtraFieldActive4|safe }}; 
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldName4'] = {{ CheckoutExtraFieldName4|safe }}; 
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldType4'] = {{ CheckoutExtraFieldType4|safe }};
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldValue4'] = {{ CheckoutExtraFieldValue4|safe }}; 
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldRequired4'] = {{ CheckoutExtraFieldRequired4|safe }};
	
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldActive5'] = {{ CheckoutExtraFieldActive5|safe }}; 
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldName5'] = {{ CheckoutExtraFieldName5|safe }}; 
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldType5'] = {{ CheckoutExtraFieldType5|safe }}; 
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldValue5'] = {{ CheckoutExtraFieldValue5|safe }};
	$GLOBALS['ISC_CFG']['CheckoutExtraFieldRequired5'] = {{ CheckoutExtraFieldRequired5|safe }};

	$GLOBALS['ISC_CFG']['syncDropboxActive'] = {{ syncDropboxActive|safe }};
	$GLOBALS['ISC_CFG']['syncDropboxOffline'] = {{ syncDropboxOffline|safe }};
	$GLOBALS['ISC_CFG']['syncDropboxDir'] = {{ syncDropboxDir|safe }};
	$GLOBALS['ISC_CFG']['syncDropboxImagesDir'] = {{ syncDropboxImagesDir|safe }};
	$GLOBALS['ISC_CFG']['syncFileNameInc'] = {{ syncFileNameInc|safe }};
	$GLOBALS['ISC_CFG']['syncFileNameOut'] = {{ syncFileNameOut|safe }};
	$GLOBALS['ISC_CFG']['syncPathToType'] = {{ syncPathToType|safe }};
	$GLOBALS['ISC_CFG']['syncTypeAtributeName'] = {{ syncTypeAtributeName|safe }};

	$GLOBALS['ISC_CFG']['LicenseTypeControl'] = {{ LicenseTypeControl|safe }};
	
	$GLOBALS['ISC_CFG']['isIntelisis'] = {{ isIntelisis|safe }};
	
	$GLOBALS['ISC_CFG']['syncIWSurl'] = {{ syncIWSurl|safe }};
	$GLOBALS['ISC_CFG']['syncIWShost'] = {{ syncIWShost|safe }};
	$GLOBALS['ISC_CFG']['syncIWSport'] = {{ syncIWSport|safe }};
	$GLOBALS['ISC_CFG']['syncIWSdbname'] = {{ syncIWSdbname|safe }};
	$GLOBALS['ISC_CFG']['syncIWSdbuser'] = {{ syncIWSdbuser|safe }};
	$GLOBALS['ISC_CFG']['syncIWSdbpass'] = {{ syncIWSdbpass|safe }};
	$GLOBALS['ISC_CFG']['syncIWSintelisisuser'] = {{ syncIWSintelisisuser|safe }};
	$GLOBALS['ISC_CFG']['syncIWSintelisispass'] = {{ syncIWSintelisispass|safe }};
	$GLOBALS['ISC_CFG']['syncIWSintelisisempresa'] = {{ syncIWSintelisisempresa|safe }};
	$GLOBALS['ISC_CFG']['syncIWSintelisissucursal'] = {{ syncIWSintelisissucursal|safe }};
	$GLOBALS['ISC_CFG']['syncIWSintelisisstocktime'] = {{ syncIWSintelisisstocktime|safe }};
	
	$GLOBALS['ISC_CFG']['showDeliveryDateFromStatus'] = {{ showDeliveryDateFromStatus|safe }};
	$GLOBALS['ISC_CFG']['ignoreAddressID0'] = {{ ignoreAddressID0|safe }};
	
	$GLOBALS['ISC_CFG']['syncArchiveDir'] = {{ syncArchiveDir|safe }};
	
	$GLOBALS['ISC_CFG']['AccountCreationInactiveUsers'] = {{ AccountCreationInactiveUsers|safe }};
	
	$GLOBALS['ISC_CFG']['DisplayCheckBoxLimit'] = {{ DisplayCheckBoxLimit|safe }};
	
	$GLOBALS['ISC_CFG']['ForcePasswordChangeNewUsers'] = {{ ForcePasswordChangeNewUsers|safe }};
	
	$GLOBALS['ISC_CFG']['ShowProductBrandImage'] = {{ ShowProductBrandImage|safe }}; 

	$GLOBALS['ISC_CFG']['UseStoreOriginForStock'] = {{ UseStoreOriginForStock|safe }};
	
		// Separar usuarios con comas ya que usamos explode() para obtenerlos
	$GLOBALS['ISC_CFG']['UsersMountForTemplates'] = {{ UsersMountForTemplates|safe }};