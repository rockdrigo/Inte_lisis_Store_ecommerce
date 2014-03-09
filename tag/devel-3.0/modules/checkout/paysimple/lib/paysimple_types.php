<?php
// -------- Begin SOAP Types -------

class ArrayOfPaymentTypeDTO
{
	public $PaymentTypeDTO = null;
}

class BaseDTO
{
	public $Id = 0;

	public $Version = 0;
}

class MasterDTO extends BaseDTO
{
	public $MasterData = null;
	public $Status = "Enabled";
	public $ModifiedById = 0;
	public $LastModifiedOn = "2001-01-01T00:00:00";
	public $CreatedOn = "2001-01-01T00:00:00";
}

class PaymentTypeDTO extends MasterDTO
{
 	public $Code = null;
	public $Name = null;
	public $Description = null;
	public $ParentPaymentTypeId = 0;
	public $Disbursement = 0;
	public $Payment = 0;
	public $RecurringPayment = false;
	public $RecurringDisbursement = false;
	public $Sequence = 0;
	public $PrimaryPayment = false;
	public $ReversePaymentTypeId = 0;
}
class MasterData
{
	public $CreatedById = null;

	public $ModifiedById = null;

	public $LastModifiedOn = "2001-01-01T00:00:00";

	public $CreatedOn = 0;

	public $Status = 'Enabled';

}


class ArrayOfCountryDTO
{
	public $CountryDTO = null;

}


class CountryDTO extends BaseDTO
{
	public $Name = null;
	public $Code = null;
}


class ArrayOfStateDTO
{
	public $StateDTO = null;
}


class StateDTO extends BaseDTO
{
	public $CountryId = 0;
	public $Name = null;
	public $Code = null;
}


class Customer
{
	public $Id = 0;
	public $ClientId = 0;
	public $Contact = null;
	public $UserName = null;
	public $StringPassword = null;
	public $Notes = null;
	public $SSN = null;
	public $DrivingLicenseNo = null;
	public $BillingAddress = null;
	public $ShipppingContact = null;
	public $ShippingAddress = null;
	public $BatchId = null;
	public $CreatorTypeId = 0;
	public $CreationMethodTypeId = 0;
	public $CustomData = null;
	public $CustomerAccount = null;
	public $BatchDetailId = 0;
	public $HasPendingTransactions = null;
	public $BillingCountryName = null;
	public $BillingStateName = null;
	public $ShippingCountryName = null;
	public $ShippingStateName = null;
	public $LastLoginOn = null;
	public $InvalidLoginAttempts = null;
	public $ChangePasswordAtNextLogin = false;
	public $IsLocked = false;
	public $LastPasswordChangeDate =  null;
	public $OwnerUserId = 0;
}


class Contact
{
	public $Name = null;
	public $CompanyName = null;
	public $Phone1 = null;
	public $Phone2 = null;
	public $Fax = null;
	public $Mobile = null;
	public $EMail = null;
	public $AlterEMail = null;
	public $WebSite = null;
}


class Name
{
	public $FirstName = null;
	public $MiddleName = null;
	public $LastName = null;
}


class Address
{
	public $AddressLine1 = null;
	public $AddressLine2 = null;
	public $City = null;
	public $StateId = null;
	public $StateCode = null;
	public $CountryId = null;
	public $CountryCode = null;
	public $ZipCode = null;
}


class ArrayOfKeyValuePair
{
	public $KeyValuePair = null;

}


class KeyValuePair
{
	public $Key = null;
	public $Value = null;
}


class ArrayOfCustomer
{
	public $Customer = null;
}


class CustomerAccountDTO extends MasterDTO
{
	public $CustomerId = 0;
	public $IsCreditCard = false;
	public $CreditCardNo = null;
	public $HashedCreditCardNo = null;
	public $CCExpiry = null;
	public $CCType = null;
	public $AccountNo = null;
	public $HashedAccountNo = null;
	public $RoutingNo = null;
	public $IsCheckingAccount = null;
	public $AccountType = null;
	public $AccountTypeValue = null;
	public $BankName = null;
	public $IsDefault = false;
	public $CreditCardName = null;
	public $FormattedCCExpiry = null;

}


class ArrayOfCustomerAccountDTO
{
	public $CustomerAccountDTO = null;

}


class Payment
{
	public $Id = 0;
	public $Amount = 0;
	public $BatchId = null;
	public $ClientUserId = 0;
	public $CustomData = null;
	public $CustomerId = 0;
	public $FromAccountId = null;
	public $InvoiceNo = null;
	public $IsDebit = false;
	public $MasterData = null;
	public $Narration = null;
	public $OrderId = null;
	public $PONO = null;
	public $PaymentData = null;
	public $PaymentDate = null;
	public $PaymentSubTypeCode = null;
	public $PaymentSubTypeDTO = null;
	public $PaymentSubTypeId = 0;
	public $PaymentTypeCode = null;
	public $PaymentTypeCodeDTO = null;
	public $PaymentTypeId = 0;
	public $ProviderAuthCode = null;
	public $RefPaymentId = 0;
	public $SettledDate = null;
	public $Status = 'Pending';
	public $ToAccountId = null;
	public $TraceNumber = null;
	public $VerificationEnabled = false;
	public $VerificationFailed = false;
}


class ArrayOfNotificationInfoDTO
{
	public $NotificationInfoDTO = null;
}


class NotificationInfoDTO extends BaseDTO
{
}


class ArrayOfPaymentHistorySummary
{
	public $PaymentHistorySummary = null;
}


class PaymentHistorySummary extends BaseDTO
{
}


class ResultInfo
{
	public $UsePaging = false;
	public $TotalCount = 0;
	public $RecordperPage = 0;
	public $UseSorting = false;
	public $SortDirectionASC = false;
	public $SortExpression = null;
}


class ArrayOfPayment
{
	public $Payment = null;
}


class RecurringPayment
{
	public $Id = 0;
	public $CustomerId = 0;
	public $MasterData = null;
	public $IsDebit = false;
	public $ScheduleType = 0;
	public $ScheduleTypeName = null;
	public $StartDate = "2001-01-01T00:00:00";
	public $BillingFrequency = 0;
	public $BillingFrequencyParam = null;
	public $PaymentAmount = 0;
	public $FirstPaymentAmount = null;
	public $FirstPaymentDate = null;
	public $EndDate = null;
	public $TotalDueAmount = 0;
	public $TotalNumberOfPayments = 0;
	public $BalanceRemaining = 0;
	public $NumberOfPaymentsRemaining = 0;
	public $PrimaryPaymentTypeId = 0;
	public $PrimaryPaymentTypeDTO = null;
	public $PrimaryPaymentTypeCode = null;
	public $PrimarySubTypeId = 0;
	public $PrimarySubTypeDTO = null;
	public $PrimaryAccountId = 0;
	public $PrimaryAccountDTO = null;
	public $SecondaryPaymentTypeId = null;
	public $SecondaryPaymentTypeDTO = null;
	public $SecondarySubTypeId = null;
	public $SecondarySubTypeDTO = null;
	public $SecondaryAccountId = null;
	public $SecondaryAccountDTO = null;
	public $FromSubTypeId = null;
	public $FromSubTypeDTO = null;
	public $FromAccountId = null;
	public $FromAccountDTO = null;
	public $InvoiceNo = null;
	public $OrderId = null;
	public $PO = null;
	public $Description = null;
	public $ScheduleStatus = 0;
	public $CustomData = null;
	public $ManualConfirmationRequired = false;
	public $CVV2Code = null;
	public $FirstPaymentDone = false;
	public $NumberOfPaymentMade = 0;
	public $TotalAmountPaid = 0;
	public $DateOfLastPaymentMade = null;
	public $PauseUntilDate = null;
	public $CustomerFirstName = null;
	public $CustomerLastName = null;
	public $CustomerCompany = null;
	public $CustomerStateCode = null;
	public $CustomerZip = null;
	public $CustomerCity = null;
	public $BatchDetailsId = 0;
	public $CustomerAccount = null;
	public $NextPaymentDate = "2001-01-01T00:00:00";
}


class ClientAccountDTO extends MasterDTO
{
  	public $ClientId = 1;
  	public $IsCreditCard = false;
  	public $CreditCardNumber = null;
  	public $CCExpiry = "2001-01-01T00:00:00";
  	public $CCType = null;
  	public $AccountNo = null;
  	public $RoutingNo = null;
  	public $HashedAccountNo = null;
  	public $HashedCCNo = null;
  	public $IsCheckingAccount = false;
  	public $BankName = null;
  	public $MerchantKey = null;
  	public $MerchantPassword = null;
  	public $IsBilling = false;
  	public $HashedMerchantPassword = null;
}


class ArrayOfRecurringPayment
{
	public $RecurringPayment = null;
}


class ArrayOfRecurringDisbursementManualConfirmation
{
	public $RecurringDisbursementManualConfirmation = null;
}


class RecurringDisbursementManualConfirmation
{
	public $Id = 0;
	public $ClientId = 0;
	public $CustomerId = 0;
	public $MasterData = null;
	public $ScheduleId = 0;
	public $DueDate = "2001-01-01T00:00:00";
	public $Amount = 0;
	public $CustomerAccountId = 0;
	public $CustomerAccountDTO = null;
	public $BankAccount = null;
	public $PaymentTypeId = 0;
	public $PaymentTypeDTO = null;
	public $PaymentSubTypeId = 0;
	public $PaymentSubTypeDTO = null;
	public $InvoiceNo = null;
	public $OrderId = null;
	public $PO = null;
	public $Narration = null;
	public $PaymentMade = false;
}


class ArrayOfPaperCheck
{
	public $PaperCheck = null;
}


class PaperCheck
{
	public $Id = 0;
	public $RoutingNumber = null;
	public $AccountNo = null;
	public $CheckNumber = null;
	public $PaymentSubType = null;
	public $IsChecking = null;
	public $Currency = null;
	public $Status = 'Pending';
	public $Amount = 0;
	public $CustomerId = 0;
	public $ClientUserId = 0;
}


class Invoice
{
	public $BatchId = 0;
	public $InvoiceAmount = 0;
	public $InvoiceNo = null;
	public $DueDate = null;
	public $OrderId = null;
	public $PONo = null;
	public $MailTo = null;
	public $Cc = null;
	public $Description = null;
	public $IsPaid = false;
	public $Paid = null;
	public $PaymentTransactionID = null;
	public $PaymentDate = null;
	public $LastResentOn = null;
	public $ResentCount = null;
	public $CustomerId = 0;
	public $AccountId = null;
	public $InvoiceScheduleId = null;
	public $InvoiceScheduleName = null;
	public $InvoiceTemplateId = 0;
	public $PaymentButtonId = 0;
	public $CustomData = null;
	public $BatchDetailsId = 0;
	public $CustomerCity = null;
	public $CustomerZip = null;
	public $CustomerStateCode = null;
	public $CustomerCompany = null;
	public $CustomerLastName = null;
	public $CustomerFirstName = null;
	public $CustomerAccount = null;
}


class InvoiceSchedule
{
	public $CustomerId = 0;
	public $BatchId = 0;
	public $AccountId = null;
	public $InvoiceAmount = 0;
	public $InvoiceNo = null;
	public $DueDate = null;
	public $InvoiceDueDate = null;
	public $OrderId = null;
	public $PONo = null;
	public $InvoiceTemplateId = 0;
	public $PaymentButtonId = 0;
	public $MailTo = null;
	public $Cc = null;
	public $Description = null;
	public $ScheduleName = null;
	public $StartDate = "2001-01-01T00:00:00";
	public $EndDate = null;
	public $Frequency = 0;
	public $FrequencyParam = null;
	public $Frequency_Text = null;
	public $CustomData = null;
	public $CustomerFirstName = null;
	public $CustomerLastName = null;
	public $CustomerCompany = null;
	public $CustomerStateCode = null;
	public $CustomerZip = null;
	public $CustomerCity = null;
	public $BatchDetailsId = 0;
	public $CustomerAccount = null;
}


class ArrayOfInvoice
{
	public $Invoice = null;
}


class ArrayOfInvoiceSchedule
{
	public $InvoiceSchedule = null;
}


class ArrayOfPaymentButtonDTO
{
	public $PaymentButtonDTO = null;
}


class PaymentButtonDTO extends MasterDTO
{
  	public $PaymentFormId = null;
  	public $ClientId = 0;
  	public $PaymentFormName = null;
  	public $ButtonType = 0;
  	public $ButtonLabel = null;
  	public $BackgroundColor = null;
  	public $FontFamily = null;
  	public $FontSize = null;
  	public $FontColor = null;
  	public $IsBold = false;
  	public $IsItalic = false;
	public $Description = null;
}


class ClientBillingDTO extends BaseDTO
{
  	public $ClientId = 0;
  	public $BankName = null;
  	public $AccountNo = null;
  	public $RoutingNo = null;
  	public $ClientAccountId = 0;
  	public $BillingFrequency = 0;
  	public $BillingStartDate = null;
  	public $Contact = null;
  	public $Address = null;
  	public $BillingSchedule = 0;
}


class ArrayOfBillingDTO
{
	public $BillingDTO = null;
}


class BillingDTO extends MasterDTO
{
  	public $RangeStartDate = "2001-01-01T00:00:00";
  	public $RangeEndDate = "2001-01-01T00:00:00";
  	public $RunDate = "2001-01-01T00:00:00";
  	public $PaymentDate = "2001-01-01T00:00:00";
  	public $Amount = 0;
  	public $IsPaid = false;
  	public $ClientId = 0;
  	public $ClientName = null;
  	public $PaymentId = null;
  	public $PaymentEntryDate = null;
}


class ArrayOfBillingDetailsDTO
{
	public $BillingDetailsDTO = null;
}


class BillingDetailsDTO extends BaseDTO
{
	public $BillingId = null;
  	public $ProcessorId = 0;
  	public $ProcessorName = null;
  	public $PaymentTypeId = 0;
  	public $PaymentTypeName = null;
  	public $Narration = null;
  	public $Quantity = 0;
  	public $Fees = 0;
  	public $FeeId = null;
  	public $MerchantFee = false;
  	public $SystemFee = false;
}


class FraudACHDTO extends MasterDTO
{
	public $VerificationType = false;
	public $OneTimeVerified = false;
	public $RecurringVerified = false;
	public $OneTimeRejected = false;
	public $RecurringRejected = false;
	public $OneTimeUnknown = false;
	public $RecurringUnknown = false;
	public $ClientId = 0;
}


class FraudCreditCardDTO extends MasterDTO
{
	public $AVSEnabled = false;
	public $AVSYYY = false;
	public $AVSXXX = false;
	public $AVSNYZ = false;
	public $AVSNYW = false;
	public $AVSYNA = false;
	public $AVSNNN = false;
	public $AVSXXE = false;
	public $AVSXXW = false;
	public $AVSXXU = false;
	public $AVSXXR = false;
	public $AVSXXS = false;
	public $AVSXXG = false;
	public $AVSYYG = false;
	public $AVSGGG = false;
	public $AVSYGG = false;
	public $AVSNNC = false;
	public $AVSNA = false;
	public $AVSResponse = false;
	public $CVV2Enabled = false;
	public $CVV2M = false;
	public $CVV2N = false;
	public $CVV2P = false;
	public $CVV2S = false;
	public $CVV2U = false;
	public $CVV2X = false;
	public $CVV2na = false;
	public $MultipleCreditCardEnabled = false;
	public $MultipleCreditCardsTimePeriod = 0;
	public $MultipleCreditCardsNumberofCards = 0;
	public $MultipleCreditCardsBlockbyOrder = false;
	public $MultipleCreditCardsBlockbyIP = false;
	public $ClientId = 0;
	public $AVSJ = false;
	public $AVSK = false;
	public $AVST = false;
	public $AVSC = false;
	public $AVSD = false;
}


class FraudAccountDTO extends MasterDTO
{
	public $BlockbyIPEnabled = false;
	public $BlockedIPAddresses = null;
	public $CountryBlockerEnabled = false;
	public $CountryBlockerAcceptAll = false;
	public $CountryList = null;
	public $DuplicateDetectionEnabled = false;
	public $DuplicateDetectionTimePeriod = false;
	public $DuplicateDetectionInvoice = false;
	public $EmailBlockerEnabled = false;
	public $BlockedEmails = null;
	public $AllowedEmails = null;
	public $ZipCodeVerifierEnabled = false;
	public $BillingState = false;
	public $BillingCity = false;
	public $BillingAreaCode = false;
	public $ShippingState = false;
	public $ShippingCity = false;
	public $ShippingAreaCode = false;
	public $Accepttransactions = false;
	public $ClientId = 0;
}


class Client
{
	public $Id = 0;
	public $Contact = null;
	public $Address = null;
	public $DefaultAccountId = 0;
	public $SSN = null;
	public $DrivingLicenseNumber = null;
	public $Memo = null;
	public $EnableIPRestriction = false;
	public $AlternameEmail = null;
	public $DefaultEmail = null;
	public $DisplayEmailName = null;
	public $Name = null;
	public $Company = null;
	public $City = null;
	public $ZipCode = null;
	public $BatchDetailId = 0;
	public $DefaultBillingAccount = 0;
	public $MasterData = null;
}


class ArrayOfClientAccountDTO
{
	public $ClientAccountDTO = null;
}


class VelocityDTO extends MasterDTO
{
	public $DailyDebitTransactionValue = 0;
	public $DailyCreditTransactionValue = 0;
	public $MonthlyDebitTransactionValue = 0;
	public $MonthlyCreditTransactionValue = 0;
	public $DailyDebitTransactionCount = 0;
	public $DailyCreditTransactionCount = 0;
	public $MonthlyDebitTransactionCount = 0;
	public $MonthlyCreditTransactionCount = 0;
	public $MaxCreditAmountPerTransaction = 0;
	public $MaxDebitAmountPerTransaction = 0;
}


class UserDTO extends MasterDTO
{
	public $Name = null;
	public $ParentId = 0;
	public $UserName = null;
	public $PasswordDTO = null;
	public $Email = null;
	public $PasswordHintQuestion = null;
	public $PasswordHintAnswer = null;
	public $IsAdmin = false;
	public $LastLoginOn = "2001-01-01T00:00:00";
	public $InvalidLoginAttempts = 0;
	public $ChangePasswordAtNextLogin = false;
	public $Notes = null;
	public $IsLocked = false;
	public $UserType = null;
	public $RoleId = 0;
	public $LastPasswordChangeDate = "2001-01-01T00:00:00";
	public $PhoneNo = null;
	public $MailSentStatus = false;
	public $IsAPIUser = false;
}


class ArrayOfUserDTO
{
	public $UserDTO = null;
}


class RoleDTO extends MasterDTO
{
	public $Name = null;
	public $UserType = null;
	public $Notes = null;
	public $ParentId = 0;
}


class ArrayOfRoleDetailsDTO
{
	public $RoleDetailsDTO = null;
}


class RoleDetailsDTO extends BaseDTO
{
	public $AddAccess = false;
	public $ModifyAccess = false;
	public $DeleteAccess = false;
	public $ViewAccess = false;
	public $ExecuteAccess = false;
	public $OperationId = 0;
	public $RoleId = 0;
	public $RoleAccessType = 'Own';
}


class ArrayOfRoleDTO
{
	public $RoleDTO = null;
}


class ArrayOfOperationDTO
{
	public $OperationDTO = null;
}


class OperationDTO extends BaseDTO
{
	public $Name = null;
	public $AppicableTo = 0;
	public $AddAccess = false;
	public $ModifyAccess = false;
	public $DeleteAccess = false;
	public $ViewAccess = false;
	public $ExecuteAccess = false;
	public $SequenceNo = 0;
	public $ParentId = 0;
}


class NotificationsDTO extends MasterDTO
{
	public $TemplateName = null;
	public $Description = null;
	public $CategoryID = 0;
	public $Subject = null;
	public $Body = null;
	public $FromID = null;
	public $ToID = null;
	public $CCID = null;
	public $BCCID = null;
	public $UserType = null;
	public $ParentId = 0;
	public $Category = null;
	public $DefaultTemplate = false;
	public $SystemDefault = false;
	public $IsApplicableToPaySimple = false;
	public $AllowMultipleTemplates = false;
}


class ArrayOfNotificationsDTO
{
	public $NotificationsDTO = null;
}


class NotificationCategoryDTO extends MasterDTO
{
	public $Category = null;
	public $Description = null;
	public $UserType = null;
	public $IsApplicableToPaySimple = false;
	public $PSDisplayName = null;
	public $RequiredPaymentTypes = null;
	public $CustomModuleId = null;
}


class ArrayOfNotificationCategoryDTO
{
	public $NotificationCategoryDTO = null;
}


class ArrayOfFieldMappingDTO
{
	public $FieldMappingDTO = null;
}


class FieldMappingDTO extends MasterDTO
{
	public $CategoryId = 0;
	public $FieldName = null;
	public $DisplayName = null;
	public $TableName = null;
	public $DefaultValue = null;
	public $RequiredPaymentTypes = null;
	public $ModuleName = null;
	public $IsApplicableToPaySimple = 0;
}


class CustomField
{
	public $Id = 0;
	public $ModuleId = 0;
	public $ModuleName = null;
	public $ModuleDescription = null;
	public $ClientId = 0;
	public $Name = null;
	public $DisplayName = null;
	public $ColumnName = null;
	public $Type = 'Text';
	public $DefaultValue = null;
	public $IdPlusModuleId = null;
	public $Params = null;
	public $TypeParam = null;
}


class ArrayOfKeyValuePairOfStringString
{
	public $KeyValuePairOfStringString = null;
}


class KeyValuePairOfStringString
{
}


class ArrayOfCustomField
{
	public $CustomField = null;
}


class ArrayOfCustomFieldModuleDTO
{
	public $CustomFieldModuleDTO = null;
}


class CustomFieldModuleDTO
{
	public $Id = 0;
	public $Name = null;
	public $Description = null;
	public $Code = null;
}

// ------------------------------------------
