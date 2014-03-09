<?php

	class ISC_SUBSCRIBE
	{

		public function HandlePage()
		{
			$action = "";
			if(isset($_REQUEST['action'])) {
				$action = isc_strtolower($_REQUEST['action']);
			}

			switch($action) {
				case "subscribe": {
					$this->Subscribe();
					break;
				}
				default: {
					ob_end_clean();
					header(sprintf("Location:%s", $GLOBALS['ShopPath']));
					die();
				}
			}
		}

		/*
		*	Add the visitor to newsletter mailing list(s)
		*/
		public function Subscribe()
		{
			if(!isset($_POST['check'])) {
				$GLOBALS['SubscriptionHeading'] = GetLang('Oops');
				$GLOBALS['Class'] = "ErrorMessage";
				$GLOBALS['SubscriptionMessage'] = GetLang('NewsletterSpammerVerification');
			}
			else if(isset($_POST['nl_first_name']) && isset($_POST['nl_email'])) {

				$first_name = $_POST['nl_first_name'];
				$email = $_POST['nl_email'];

				if (!is_email_address($email)) {
					$GLOBALS['SubscriptionHeading'] = GetLang('NewsletterSubscription');
					$GLOBALS['Class'] = "ErrorMessage";
					$GLOBALS['SubscriptionMessage'] = GetLang('NewsletterEnterValidEmail');
				} else {
					$subscription = new Interspire_EmailIntegration_Subscription_Newsletter($email, $first_name);
					$results = $subscription->routeSubscription();

					$success = false;
					$existed = false;

					foreach ($results as /** @var Interspire_EmailIntegration_SubscriberActionResult */$result) {
						// message sent to visitor is 'ok' if even one subscription worked; other failures will be logged internally & emailed to store owner
						// this is a little counter-intuitive when multiple modules are enabled but it's the best compromise I think short of sending info about every module back to the visitor, who shouldn't be concered with such detail
						if ($result->pending) {
							$success = true;
						} else {
							if ($result->success) {
								$success = true;
							}
							if ($result->existed) {
								$existed = true;
							}
						}
					}

					if ($success) {
						if ($existed) {
							// most APIs will simply update existing details, rather than error - but this mimmicks the existing behaviour of ISC if the API can let us know the subscriber existed
							$GLOBALS['SubscriptionHeading'] = GetLang('Oops');
							$GLOBALS['Class'] = "ErrorMessage";
							$GLOBALS['SubscriptionMessage'] = sprintf(GetLang('NewsletterAlreadySubscribed'), $email); // legacy sprintf
						} else {
							$GLOBALS['SubscriptionHeading'] = GetLang('NewsletterThanksForSubscribing');
							$GLOBALS['Class'] = "";
							$GLOBALS['SubscriptionMessage'] = GetLang('NewsletterSubscribedSuccessfully') . sprintf(" <a href='%s'>%s.</a>", $GLOBALS['ShopPath'], GetLang('Continue'));
						}
					} else {
						$GLOBALS['SubscriptionHeading'] = GetLang('Oops');
						$GLOBALS['Class'] = "ErrorMessage";
						$GLOBALS['SubscriptionMessage'] = GetLang('NewsletterSubscribeError');
					}
				}
			}
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(sprintf("%s - %s", GetConfig('StoreName'), GetLang('NewsletterSubscription')));
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("newsletter_subscribe");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}
	}
