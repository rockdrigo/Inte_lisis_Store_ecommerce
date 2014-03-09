<?php

require_once('FPS/FPSSignatureHelper.class.php');
require_once('Crypt/HMAC.php');

class AmazonTokenCreator {

	public static $amazonfpsURL = 'https://fps.amazonaws.com/';
	public static $amazonfpsURLSandbox = 'https://fps.sandbox.amazonaws.com/';

	/**
	* Whether the account to use is a live account or sandbox account.
	*
	* @var bool
	*/
	public $live;

	public function __construct ($live = true)
	{
		$this->live = $live;
	}

	public function process($type, $AWSAccessKeyID, $AWSSecretAccessKey, &$successMsg, &$failMsg)
	{
		$uniqueId = $type.'-ISC-'.microtime(true);

		// prepare the REST request array map
		$request = array(
				'Action' => 'InstallPaymentInstruction',
				'PaymentInstruction' => "MyRole == '".$type."' orSay 'Roles do not match';",
				'CallerReference' => $uniqueId,
				'TokenType' => 'Unrestricted',
		);

		$timestamp = gmdate("Y-m-d\TH:i:s\Z");
		$SERVICE_VERSION = "2007-01-08";
		$SIGNATURE_VERSION = "1";

		$array1 = array();
		$array1["Timestamp"] = $timestamp;
		$array1["Version"] = $SERVICE_VERSION;
		$array1["SignatureVersion"] = $SIGNATURE_VERSION;
		$array1["AWSAccessKeyId"] = $AWSAccessKeyID;

		$array = $request + $array1;

		$signiture = FPSSignatureHelper::generateSignature($AWSSecretAccessKey, $array);

		$sortedUrl = FPSSignatureHelper::sortedParams($array, true);

		if ($this->live) {
			$url = AmazonTokenCreator::$amazonfpsURL;
		} else {
			$url = AmazonTokenCreator::$amazonfpsURLSandbox;
		}

		$url .= "?".$sortedUrl."&Signature=".urlencode($signiture);

		$xmlresponse = false;

		if(function_exists("curl_exec")) {
			// Use CURL if it's available
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			// Setup the proxy settings if there are any

			$response = curl_exec($ch);
			if ($response) {
				$xmlresponse = new SimpleXMLElement($response);
			} else {
				$failMsg = "Fatal Error:<br/>No response from Amazon, or Internet connection issue. Error information: " . curl_error($ch);
			}
		} else {
			$failMsg = "Fatal Error: PHP CURL not available on server.";
		}


		if (!empty($xmlresponse)) {
			//handle HTTP response. Fatal error if did not pass this step
			if($xmlresponse->Status == 'Success') {
				$successMsg .= $type . ' ID : '.$xmlresponse->TokenId . "<br>";
			}
			else {
			  	//handle response (basic error handling
			  	$failMsg .=  "Fatal Error: <br> ";
				$failMsg .= "Response: ". $xmlresponse->Errors->Error->Message ."<br>";
			}
		}
	}

}
$failMsg = '';
$successMsg = '';
if (isset($_POST['accessKey']) && isset($_POST['accessSecret']) && @$_POST['accountType']) {
	$live = $_POST['accountType'] == 'live';
	$aws = new AmazonTokenCreator($live);
	$aws->process('Caller',$_POST['accessKey'],$_POST['accessSecret'], $successMsg, $failMsg);
	$aws->process('Recipient',$_POST['accessKey'],$_POST['accessSecret'], $successMsg,$failMsg);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11">
<html>
<head>
	<title>Amazon Flexible Payment System</title>
	<meta name="robots" content="noindex, nofollow" />
	<style type="text/css">
		@import url("../../../../admin/Styles/styles.css");
	</style>
</head>

<body>
<div style="border:2px solid #CACACA; height:300px; padding:10px;">
<h2>
	Amazon Flexible Payment System
</h2>

<div id="MessageBox">
	<?php if($successMsg != '') { ?>
	<div class="MessageBox MessageBoxSuccess">
		<?php echo $successMsg;?>
	</div>
	<?php } ?>
	<?php if($failMsg != '') { ?>
	<div class="MessageBox MessageBoxError">
		<?php echo $failMsg;?>
	</div>
	<?php } ?>
</div>

	<form method="POST">
	<table class="Panel" width="100%">
		<tr><td class="FieldLabel">Access ID :</td><td><input class="Field400" id="accessKey" name="accessKey" value="<?php echo htmlspecialchars(@$_POST['accessKey']) ?>" /></td></tr>
		<tr><td class="FieldLabel">Access Secret Key :</td><td><input class="Field400" id="accessSecret" name="accessSecret" value="<?php echo htmlspecialchars(@$_POST['accessSecret']) ?>" /></td></tr>
		<tr><td class="FieldLabel">Account Type:</td><td><select name="accountType">
			<option value="">--- Please Select---</option>
			<option value=""></option>
			<option value="live" <?php if (@$_POST['accountType'] == 'live') { echo 'selected="selected"'; } ?>>Live AWS Account</option>
			<option value="sandbox" <?php if (@$_POST['accountType'] == 'sandbox') { echo 'selected="selected"'; } ?>>Sandbox / Test Account</option>
		</select></td></tr>
		<tr><td></td><td style="padding-top:5px;"><input type="submit" value="Generate Tokens" /></td></tr>
	</table>
	</form>
	</div>

</body>
</html>