<?php

require_once('apibase.class.php');
require_once('paysimple_types.php');
require_once('dynamicKey.php');

class Gateway extends APIBase
{
	public function ToArray($returnVal)
	{
		if(is_array($returnVal))
			return $returnVal;
		else
		{
			$returnArray = array($returnVal);
			return $returnArray;
		}
	}

	public function GetDynamicKey( $merchantKey , $validUpTo )
	{

		$args = array();
		$args['merchantKey'] = $merchantKey;
		$args['validUpTo'] = $validUpTo;
		$error = $this->Call('GetDynamicKey', $args, $data);

		return $data->GetDynamicKeyResult;

	}

	public function GetSupportedPaymentTypes( )
	{

		$data = $this->Call('GetSupportedPaymentTypes');

		return $this->ToArray($data->GetSupportedPaymentTypesResult->PaymentTypeDTO);

	}

	public function GetPrimaryPaymentSubTypes( $paymentType )
	{

		$args = array();
		$args['paymentType'] = $paymentType;
		$data = $this->Call('GetPrimaryPaymentSubTypes', $args);

		return $this->ToArray($data->GetPrimaryPaymentSubTypesResult->PaymentTypeDTO);

	}

	public function GetSecondaryPaymentSubTypes( $paymentType )
	{

		$args = array();
		$args['paymentType'] = $paymentType;
		$data = $this->Call('GetSecondaryPaymentSubTypes', $args);

		return $this->ToArray($data->GetSecondaryPaymentSubTypesResult->PaymentTypeDTO);

	}

	public function GetAllCountries( $merchantKey )
	{

		$args = array();
		$args['merchantKey'] = $merchantKey;
		$data = $this->Call('GetAllCountries', $args);

		return $this->ToArray($data->GetAllCountriesResult->CountryDTO);

	}

	public function GetStatesByCountryId( $merchantKey , $countryId )
	{

		$args = array();
		$args['merchantKey'] = $merchantKey;
		$args['countryId'] = $countryId;
		$data = $this->Call('GetStatesByCountryId', $args);

		return $this->ToArray($data->GetStatesByCountryIdResult->StateDTO);

	}

	public function GetStateById( $merchantKey , $stateId )
	{

		$args = array();
		$args['merchantKey'] = $merchantKey;
		$args['stateId'] = $stateId;
		$data = $this->Call('GetStateById', $args);

		return $data->GetStateByIdResult;

	}

	public function GetCountryById( $merchantKey , $countryId )
	{

		$args = array();
		$args['merchantKey'] = $merchantKey;
		$args['countryId'] = $countryId;
		$data = $this->Call('GetCountryById', $args);

		return $data->GetCountryByIdResult;

	}

	public function AddCustomer( $merchantKey , $customer )
	{

		$args = array();
		$args['merchantKey'] = $merchantKey;
		$args['customer'] = $customer;
		$error = $this->Call('AddCustomer', $args, $data);

		if (!$error) {
			return $data->AddCustomerResult;
		}
		else {
			return $data;
		}

	}

	public function DeleteCustomer( $merchantKey , $customerId )
	{

		$args = array();
		$args['merchantKey'] = $merchantKey;
		$args['customerId'] = $customerId;
		$data = $this->Call('DeleteCustomer', $args);

		return true;
	}

	public function ModifyCustomer( $merchantKey , $customer )
	{

		$args = array();
		$args['merchantKey'] = $merchantKey;
		$args['customer'] = $customer;
		$data = $this->Call('ModifyCustomer', $args);

		return $data->ModifyCustomerResult;

	}

	public function GetCustomerById( $merchantKey , $customerId )
	{

		$args = array();
		$args['merchantKey'] = $merchantKey;
		$args['customerId'] = $customerId;
		$data = $this->Call('GetCustomerById', $args);

		return $data->GetCustomerByIdResult;

	}

	public function GetAllCustomers( $merchantKey )
	{

		$args = array();
		$args['merchantKey'] = $merchantKey;
		$data = $this->Call('GetAllCustomers', $args);

		return $this->ToArray($data->GetAllCustomersResult->Customer);

	}

	public function AddAccount( $merchantKey , $customerAccount )
	{

		$args = array();
		$args['merchantKey'] = $merchantKey;
		$args['customerAccount'] = $customerAccount;
		$error = $this->Call('AddAccount', $args, $data);

		if (!$error) {
			return $data->AddAccountResult;
		}
		else {
			return $data;
		}

	}

	public function DeleteAccount( $merchantKey , $customerAccountId )
	{

		$args = array();
		$args['merchantKey'] = $merchantKey;
		$args['customerAccountId'] = $customerAccountId;
		$data = $this->Call('DeleteAccount', $args);

		return true;

	}

	public function ModifyAccount( $merchantKey , $customerAccount )
	{

		$args = array();
		$args['merchantKey'] = $merchantKey;
		$args['customerAccount'] = $customerAccount;
		$data = $this->Call('ModifyAccount', $args);

		return $data->ModifyAccountResult;

	}

	public function GetAllAccountsForCustomer( $merchantKey , $customerId )
	{

		$args = array();
		$args['merchantKey'] = $merchantKey;
		$args['customerId'] = $customerId;
		$data = $this->Call('GetAllAccountsForCustomer', $args);

		return $this->ToArray($data->GetAllAccountsForCustomerResult->CustomerAccountDTO);
	}

	public function GetCustomerAccountById( $merchantKey , $customerAccountId )
	{

		$args = array();
		$args['merchantKey'] = $merchantKey;
		$args['customerAccountId'] = $customerAccountId;
		$data = $this->Call('GetCustomerAccountById', $args);

		return $data->GetCustomerAccountByIdResult;

	}

	public function GetDefaultAccountForCustomer( $merchantKey , $customerId , $isCreditCard )
	{

		$args = array();
		$args['merchantKey'] = $merchantKey;
		$args['customerId'] = $customerId;
		$args['isCreditCard'] = $isCreditCard;
		$data = $this->Call('GetDefaultAccountForCustomer', $args);

		return $data->GetDefaultAccountForCustomerResult;

	}

	public function SetDefaultAccountForCustomer( $merchantKey , $customerId , $customerAccountId , $isCreditCard )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['customerId'] = $customerId;
	$args['customerAccountId'] = $customerAccountId;
	$args['isCreditCard'] = $isCreditCard;
	 $data = $this->Call('SetDefaultAccountForCustomer', $args);

	 return true;

	}

	public function MakePayment( $merchantKey , $payment , $notificationInfoDTO )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['payment'] = $payment;
	$args['notificationInfoDTO'] = $notificationInfoDTO;
	 $error = $this->Call('MakePayment', $args, $data);

	if (!$error)
	 return $data->MakePaymentResult;
	else
	 return $data;
	}

	public function CancelPayment( $merchantKey , $paymentId , $reason )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['paymentId'] = $paymentId;
	$args['reason'] = $reason;
	 $data = $this->Call('CancelPayment', $args);

	 return true;

	}

	public function ReversePayment( $merchantKey , $paymentId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['paymentId'] = $paymentId;
	 $data = $this->Call('ReversePayment', $args);

	 return $data->ReversePaymentResult;

	}

	public function GetPaymentById( $merchantKey , $paymentId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['paymentId'] = $paymentId;
	 $data = $this->Call('GetPaymentById', $args);

	 return $data->GetPaymentByIdResult;

	}

	public function SearchHistory( $transactionFromDate , $transactionToDate , $customerId )
	{

	 $args = array();
	$args['transactionFromDate'] = $transactionFromDate;
	$args['transactionToDate'] = $transactionToDate;
	$args['customerId'] = $customerId;
	 $data = $this->Call('SearchHistory', $args);

	 return $this->ToArray($data->SearchHistoryResult->PaymentHistorySummary);

	}

	public function SearchPayments( $merchantKey , $transactionFromDate , $transactionToDate , $customerId , $resultInfo )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['transactionFromDate'] = $transactionFromDate;
	$args['transactionToDate'] = $transactionToDate;
	$args['customerId'] = $customerId;
	$args['resultInfo'] = $resultInfo;
	 $data = $this->Call('SearchPayments', $args);

	 return $this->ToArray($data->SearchPaymentsResult->Payment);

	}

	public function AddPaymentSchedule( $merchantKey , $paymentSchedule , $notificationInfoDTO )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['paymentSchedule'] = $paymentSchedule;
	$args['notificationInfoDTO'] = $notificationInfoDTO;
	 $data = $this->Call('AddPaymentSchedule', $args);

	 return $data->AddPaymentScheduleResult;

	}

	public function DeletePaymentSchedule( $merchantKey , $scheduleId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['scheduleId'] = $scheduleId;
	 $data = $this->Call('DeletePaymentSchedule', $args);

	 return true;

	}

	public function ModifyPaymentSchedule( $merchantKey , $paymentSchedule , $notificationInfoDTO )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['paymentSchedule'] = $paymentSchedule;
	$args['notificationInfoDTO'] = $notificationInfoDTO;
	 $data = $this->Call('ModifyPaymentSchedule', $args);

	 return $data->ModifyPaymentScheduleResult;

	}

	public function GetPaymentScheduleById( $merchantKey , $scheduleId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['scheduleId'] = $scheduleId;
	 $data = $this->Call('GetPaymentScheduleById', $args);

	 return $data->GetPaymentScheduleByIdResult;

	}

	public function GetAllPaymentSchedulesForCustomer( $merchantKey , $customerId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['customerId'] = $customerId;
	 $data = $this->Call('GetAllPaymentSchedulesForCustomer', $args);

	 return $this->ToArray($data->GetAllPaymentSchedulesForCustomerResult->RecurringPayment);

	}

	public function SendMailforPayment( $merchantKey , $template , $to , $cc , $paymentInfoDTO )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['template'] = $template;
	$args['to'] = $to;
	$args['cc'] = $cc;
	$args['paymentInfoDTO'] = $paymentInfoDTO;
	 $data = $this->Call('SendMailforPayment', $args);

	 return true;

	}

	public function GetRecurringDisbursementManualConfirmations( $merchantKey , $clientId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['clientId'] = $clientId;
	 $data = $this->Call('GetRecurringDisbursementManualConfirmations', $args);

	 return $this->ToArray($data->GetRecurringDisbursementManualConfirmationsResult->RecurringDisbursementManualConfirmation);

	}

	public function GetRecurringDisbursementManualConfirmationById( $merchantKey , $confirmationId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['confirmationId'] = $confirmationId;
	 $data = $this->Call('GetRecurringDisbursementManualConfirmationById', $args);

	 return $data->GetRecurringDisbursementManualConfirmationByIdResult;

	}

	public function DeleteManualConfirmation( $merchantKey , $confirmationId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['confirmationId'] = $confirmationId;
	 $data = $this->Call('DeleteManualConfirmation', $args);

	 return true;

	}

	public function UpdateManualConfirmation( $merchantKey , $manualConfirmation )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['manualConfirmation'] = $manualConfirmation;
	 $data = $this->Call('UpdateManualConfirmation', $args);

	 return $data->UpdateManualConfirmationResult;

	}

	public function MakePaperCheckPayment( $merchantKey , $paperCheckList )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['paperCheckList'] = $paperCheckList;
	 $data = $this->Call('MakePaperCheckPayment', $args);

	 return $this->ToArray($data->MakePaperCheckPaymentResult->PaperCheck);

	}

	public function SetInvoiceDetails( $merchantKey , $invoice )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['invoice'] = $invoice;
	 $data = $this->Call('SetInvoiceDetails', $args);

	 return $data->SetInvoiceDetailsResult;

	}

	public function SetInvoiceScheduleDetails( $merchantKey , $invoiceSchedule )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['invoiceSchedule'] = $invoiceSchedule;
	 $data = $this->Call('SetInvoiceScheduleDetails', $args);

	 return $data->SetInvoiceScheduleDetailsResult;

	}

	public function GetInvoiceDetailsById( $merchantKey , $invoiceId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['invoiceId'] = $invoiceId;
	 $data = $this->Call('GetInvoiceDetailsById', $args);

	 return $data->GetInvoiceDetailsByIdResult;

	}

	public function GetInvoiceScheduleDetailsById( $merchantKey , $invoiceScheduleId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['invoiceScheduleId'] = $invoiceScheduleId;
	 $data = $this->Call('GetInvoiceScheduleDetailsById', $args);

	 return $data->GetInvoiceScheduleDetailsByIdResult;

	}

	public function GetInvoicesByDate( $merchantKey , $fromDate , $toDate , $customerId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['fromDate'] = $fromDate;
	$args['toDate'] = $toDate;
	$args['customerId'] = $customerId;
	 $data = $this->Call('GetInvoicesByDate', $args);

	 return $data->GetInvoicesByDateResult;

	}

	public function GetActiveScheduleByCustomerId( $merchantKey , $customerId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['customerId'] = $customerId;
	 $data = $this->Call('GetActiveScheduleByCustomerId', $args);

	 return $this->ToArray($data->GetActiveScheduleByCustomerIdResult->InvoiceSchedule);

	}

	public function GetCountActiveScheduleByCustomerId( $merchantKey , $customerId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['customerId'] = $customerId;
	 $data = $this->Call('GetCountActiveScheduleByCustomerId', $args);

	 return $data->GetCountActiveScheduleByCustomerIdResult;

	}

	public function DeleteInvoiceSchedule( $merchantKey , $scheduleId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['scheduleId'] = $scheduleId;
	 $data = $this->Call('DeleteInvoiceSchedule', $args);

	 return true;

	}

	public function ToggleInvoiceScheduleOnOff( $merchantKey , $scheduleId , $isDisable )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['scheduleId'] = $scheduleId;
	$args['isDisable'] = $isDisable;
	 $data = $this->Call('ToggleInvoiceScheduleOnOff', $args);

	 return true;

	}

	public function ReSendInvoice( $merchantKey , $invoiceId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['invoiceId'] = $invoiceId;
	 $data = $this->Call('ReSendInvoice', $args);

	 return true;

	}

	public function GetAllPaymentButtons( $merchantKey )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetAllPaymentButtons', $args);

	 return $this->ToArray($data->GetAllPaymentButtonsResult->PaymentButtonDTO);

	}

	public function SetBillingInfo( $merchantKey , $clientBillingDTO )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['clientBillingDTO'] = $clientBillingDTO;
	 $data = $this->Call('SetBillingInfo', $args);

	 return true;

	}

	public function GetBillingInfo( $merchantKey )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetBillingInfo', $args);

	 return $data->GetBillingInfoResult;

	}

	public function GetDefaultClientBillingAccount( $merchantKey )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetDefaultClientBillingAccount', $args);

	 return $data->GetDefaultClientBillingAccountResult;

	}

	public function SetDefaultClientBillingAccount( $merchantKey , $clientAccountDTO )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['clientAccountDTO'] = $clientAccountDTO;
	 $data = $this->Call('SetDefaultClientBillingAccount', $args);

	 return true;

	}

	public function GetBillingInfoBetweenDates( $merchantKey , $startdate , $enddate )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['startdate'] = $startdate;
	$args['enddate'] = $enddate;
	 $data = $this->Call('GetBillingInfoBetweenDates', $args);

	 return $this->ToArray($data->GetBillingInfoBetweenDatesResult->BillingDTO);

	}

	public function GetBillingDetail( $merchantKey , $billingId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['billingId'] = $billingId;
	 $data = $this->Call('GetBillingDetail', $args);

	 return $this->ToArray($data->GetBillingDetailResult->BillingDetailsDTO);

	}

	public function GetBillById( $merchantKey , $billId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['billId'] = $billId;
	 $data = $this->Call('GetBillById', $args);

	 return $data->GetBillByIdResult;

	}

	public function GetUnbilledUsageDetails( $merchantKey )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetUnbilledUsageDetails', $args);

	 return $this->ToArray($data->GetUnbilledUsageDetailsResult->BillingDetailsDTO);

	}

	public function GetNextBillingDate( $merchantKey )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetNextBillingDate', $args);

	 return $data->GetNextBillingDateResult;

	}

	public function GetLastBillingInfo( $merchantKey )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetLastBillingInfo', $args);

	 return $this->ToArray($data->GetLastBillingInfoResult->BillingDTO);

	}

	public function SetACHFraudSettings( $merchantKey , $fraudach )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['fraudach'] = $fraudach;
	 $data = $this->Call('SetACHFraudSettings', $args);

	 return $data->SetACHFraudSettingsResult;

	}

	public function SetCreditCardFraudSettings( $merchantKey , $fraudCreditCard )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['fraudCreditCard'] = $fraudCreditCard;
	 $data = $this->Call('SetCreditCardFraudSettings', $args);

	 return $data->SetCreditCardFraudSettingsResult;

	}

	public function SetAccountFraudSettings( $merchantKey , $fraudAccount )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['fraudAccount'] = $fraudAccount;
	 $data = $this->Call('SetAccountFraudSettings', $args);

	 return $data->SetAccountFraudSettingsResult;

	}

	public function GetACHFraudSettings( $merchantKey )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetACHFraudSettings', $args);

	 return $data->GetACHFraudSettingsResult;

	}

	public function GetCreditCardFraudSettings( $merchantKey )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetCreditCardFraudSettings', $args);

	 return $data->GetCreditCardFraudSettingsResult;

	}

	public function GetAccountFraudSettings( $merchantKey )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetAccountFraudSettings', $args);

	 return $data->GetAccountFraudSettingsResult;

	}

	public function ModifyClient( $merchantKey , $client )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['client'] = $client;
	 $data = $this->Call('ModifyClient', $args);

	 return $data->ModifyClientResult;

	}

	public function GetClientInfo( $merchantKey )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetClientInfo', $args);

	 return $data->GetClientInfoResult;

	}

	public function GetAllAccounts( $merchantKey )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetAllAccounts', $args);

	 return $this->ToArray($data->GetAllAccountsResult->ClientAccountDTO);

	}

	public function SetDefaultAccount( $merchantKey , $accountId , $isFirstAccount )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['accountId'] = $accountId;
	$args['isFirstAccount'] = $isFirstAccount;
	 $data = $this->Call('SetDefaultAccount', $args);

	 return true;

	}

	public function GetDefaultAccount( $merchantKey )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetDefaultAccount', $args);

	 return $data->GetDefaultAccountResult;

	}

	public function GetVelocitiesForClientUser( $merchantKey , $paymentType )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['paymentType'] = $paymentType;
	 $data = $this->Call('GetVelocitiesForClientUser', $args);

	 return $data->GetVelocitiesForClientUserResult;

	}

	public function SetVelocitiesForClientUser( $merchantKey , $paymentType , $velocityDTO )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['paymentType'] = $paymentType;
	$args['velocityDTO'] = $velocityDTO;
	 $data = $this->Call('SetVelocitiesForClientUser', $args);

	 return true;

	}

	public function GetAllowedPaymentTypes( $merchantKey )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetAllowedPaymentTypes', $args);

	 return $this->ToArray($data->GetAllowedPaymentTypesResult->PaymentTypeDTO);

	}

	public function AddUser( $merchantKey , $user )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['user'] = $user;
	 $data = $this->Call('AddUser', $args);

	 return $data->AddUserResult;

	}

	public function DeleteUser( $merchantKey , $userId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['userId'] = $userId;
	 $data = $this->Call('DeleteUser', $args);

	 return true;

	}

	public function SetUserEnabledStatus( $merchantKey , $userId , $enabled )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['userId'] = $userId;
	$args['enabled'] = $enabled;
	 $data = $this->Call('SetUserEnabledStatus', $args);

	 return true;

	}

	public function ModifyUser( $merchantKey , $user )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['user'] = $user;
	 $data = $this->Call('ModifyUser', $args);

	 return $data->ModifyUserResult;

	}

	public function GetUserById( $merchantKey , $userId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['userId'] = $userId;
	 $data = $this->Call('GetUserById', $args);

	 return $data->GetUserByIdResult;

	}

	public function GetUserByLoginName( $merchantKey , $loginName )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['loginName'] = $loginName;
	 $data = $this->Call('GetUserByLoginName', $args);

	 return $data->GetUserByLoginNameResult;

	}

	public function GetAllUsers( $merchantKey )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetAllUsers', $args);

	 return $this->ToArray($data->GetAllUsersResult->UserDTO);

	}

	public function GetAllAccountExecutives( $merchantKey )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetAllAccountExecutives', $args);

	 return $this->ToArray($data->GetAllAccountExecutivesResult->UserDTO);

	}

	public function GetVelocitiesForUser( $merchantKey , $userId , $paymentType )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['userId'] = $userId;
	$args['paymentType'] = $paymentType;
	 $data = $this->Call('GetVelocitiesForUser', $args);

	 return $data->GetVelocitiesForUserResult;

	}

	public function SetVelocitiesForUser( $merchantKey , $userId , $paymentType , $velocityDTO )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['userId'] = $userId;
	$args['paymentType'] = $paymentType;
	$args['velocityDTO'] = $velocityDTO;
	 $data = $this->Call('SetVelocitiesForUser', $args);

	 return true;

	}

	public function AddRole( $merchantKey , $role )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['role'] = $role;
	 $data = $this->Call('AddRole', $args);

	 return $data->AddRoleResult;

	}

	public function DeleteRole( $merchantKey , $roleId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['roleId'] = $roleId;
	 $data = $this->Call('DeleteRole', $args);

	 return true;

	}

	public function ModifyRole( $merchantKey , $roleDTO )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['roleDTO'] = $roleDTO;
	 $data = $this->Call('ModifyRole', $args);

	 return $data->ModifyRoleResult;

	}

	public function GetRoleDetails( $merchantKey , $roleId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['roleId'] = $roleId;
	 $data = $this->Call('GetRoleDetails', $args);

	 return $this->ToArray($data->GetRoleDetailsResult->RoleDetailsDTO);

	}

	public function SetRoleDetails( $merchantKey , $roleId , $roleDetails )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['roleId'] = $roleId;
	$args['roleDetails'] = $roleDetails;
	 $data = $this->Call('SetRoleDetails', $args);

	 return true;

	}

	public function GetRoleById( $merchantKey , $roleId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['roleId'] = $roleId;
	 $data = $this->Call('GetRoleById', $args);

	 return $data->GetRoleByIdResult;

	}

	public function GetAllRoles( $merchantKey )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetAllRoles', $args);

	 return $this->ToArray($data->GetAllRolesResult->RoleDTO);

	}

	public function GetVelocitiesForRole( $merchantKey , $roleId , $paymentType )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['roleId'] = $roleId;
	$args['paymentType'] = $paymentType;
	 $data = $this->Call('GetVelocitiesForRole', $args);

	 return $data->GetVelocitiesForRoleResult;

	}

	public function SetVelocitiesForRole( $merchantKey , $roleId , $paymentType , $velocityDTO )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['roleId'] = $roleId;
	$args['paymentType'] = $paymentType;
	$args['velocityDTO'] = $velocityDTO;
	 $data = $this->Call('SetVelocitiesForRole', $args);

	 return true;

	}

	public function GetAllOperations( $merchantKey )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetAllOperations', $args);

	 return $this->ToArray($data->GetAllOperationsResult->OperationDTO);

	}

	public function GetIdByName( $merchantKey , $operationName )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['operationName'] = $operationName;
	 $data = $this->Call('GetIdByName', $args);

	 return $data->GetIdByNameResult;

	}

	public function GetOperationById( $merchantKey , $id )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['id'] = $id;
	 $data = $this->Call('GetOperationById', $args);

	 return $data->GetOperationByIdResult;

	}

	public function AddNotificationTemplate( $merchantKey , $notificationTemplate )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['notificationTemplate'] = $notificationTemplate;
	 $data = $this->Call('AddNotificationTemplate', $args);

	 return $data->AddNotificationTemplateResult;

	}

	public function ModifyNotificationTemplate( $merchantKey , $notificationTemplate )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['notificationTemplate'] = $notificationTemplate;
	 $data = $this->Call('ModifyNotificationTemplate', $args);

	 return $data->ModifyNotificationTemplateResult;

	}

	public function DeleteNotificationTemplate( $merchantKey , $templateID )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['templateID'] = $templateID;
	 $data = $this->Call('DeleteNotificationTemplate', $args);

	 return true;

	}

	public function GetNotificationTemplateById( $merchantKey , $templateId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['templateId'] = $templateId;
	 $data = $this->Call('GetNotificationTemplateById', $args);

	 return $data->GetNotificationTemplateByIdResult;

	}

	public function GetAllNotificationTemplates( $merchantKey )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetAllNotificationTemplates', $args);

	 return $this->ToArray($data->GetAllNotificationTemplatesResult->NotificationsDTO);

	}

	public function GetCategoryById( $merchantKey , $notificationId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['notificationId'] = $notificationId;
	 $data = $this->Call('GetCategoryById', $args);

	 return $data->GetCategoryByIdResult;

	}

	public function GetAllNotificationCategories( $merchantKey )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetAllNotificationCategories', $args);

	 return $this->ToArray($data->GetAllNotificationCategoriesResult->NotificationCategoryDTO);

	}

	public function GetAllFields( $merchantKey , $categoryId )
	{

	 $args = array();
	$args['merchantKey'] = $merchantKey;
	$args['categoryId'] = $categoryId;
	 $data = $this->Call('GetAllFields', $args);

	 return $this->ToArray($data->GetAllFieldsResult->FieldMappingDTO);

	}

	public function GetAllNotificationTemplatesForTemplateType( $merchantKey , $categoryId )
	{

	 $args = array();
	 $args['merchantKey'] = $merchantKey;
	 $args['categoryId'] = $categoryId;
	 $data = $this->Call('GetAllNotificationTemplatesForTemplateType', $args);

	 return $this->ToArray($data->GetAllNotificationTemplatesForTemplateTypeResult->NotificationsDTO);

	}

	public function AddCustomField( $merchantKey , $customFiled )
	{

	 $args = array();
	 $args['merchantKey'] = $merchantKey;
	 $args['customFiled'] = $customFiled;
	 $data = $this->Call('AddCustomField', $args);

	 return $data->AddCustomFieldResult;

	}

	public function DeleteCustomField( $merchantKey , $customFieldId )
	{

	 $args = array();
	 $args['merchantKey'] = $merchantKey;
	 $args['customFieldId'] = $customFieldId;
	 $data = $this->Call('DeleteCustomField', $args);

	 return true;

	}

	public function ModifyCustomField( $merchantKey , $customField )
	{

	 $args = array();
	 $args['merchantKey'] = $merchantKey;
	 $args['customField'] = $customField;
	 $data = $this->Call('ModifyCustomField', $args);

	 return $data->ModifyCustomFieldResult;

	}

	public function GetCustomFieldById( $merchantKey , $id )
	{

	 $args = array();
	 $args['merchantKey'] = $merchantKey;
	 $args['id'] = $id;
	 $data = $this->Call('GetCustomFieldById', $args);

	 return $data->GetCustomFieldByIdResult;

	}

	public function GetAllCustomFields( $merchantKey )
	{

	 $args = array();
	 $args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetAllCustomFields', $args);

	 return $this->ToArray($data->GetAllCustomFieldsResult->CustomField);

	}

	public function GetCustomFieldsForModule( $merchantKey , $moduleId )
	{

	 $args = array();
	 $args['merchantKey'] = $merchantKey;
	 $args['moduleId'] = $moduleId;
	 $data = $this->Call('GetCustomFieldsForModule', $args);

	 return $this->ToArray($data->GetCustomFieldsForModuleResult->CustomField);

	}

	public function GetCustomFieldByName( $merchantKey , $name , $moduleId )
	{

	 $args = array();
	 $args['merchantKey'] = $merchantKey;
	 $args['name'] = $name;
	 $args['moduleId'] = $moduleId;
	 $data = $this->Call('GetCustomFieldByName', $args);

	 return $data->GetCustomFieldByNameResult;

	}

	public function GetCustomFieldModuleList( $merchantKey )
	{

	 $args = array();
	 $args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetCustomFieldModuleList', $args);

	 return $this->ToArray($data->GetCustomFieldModuleListResult->CustomFieldModuleDTO);

	}

	public function GetMerchantKeyExpirationDate( $merchantKey )
	{

	 $args = array();
	 $args['merchantKey'] = $merchantKey;
	 $data = $this->Call('GetMerchantKeyExpirationDate', $args);

	 return $data->GetMerchantKeyExpirationDateResult;

	}
	public function AddAndPay( $merchantKey, $customer, $customerAccount, $payment, $notificationDTO)
	{

	 $args = array();
	 $args['merchantKey'] = $merchantKey;
	 $args['customer'] = $customer;
	 $args['customerAccount'] = $customerAccount;
	 $args['payment'] = $payment;
 	 $args['notificationDTO'] = $notificationDTO;

	 $data = $this->Call('AddAndPay', $args);

	 return $data->AddAndPayResult;

	}
}