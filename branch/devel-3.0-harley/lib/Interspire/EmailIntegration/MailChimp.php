<?php

/**
 * This is an Interspire-specific extension of the MailChimp MCAPI allowing us to, if necessary, seamlessly override functionality of the MCAPI or add new features
 *
 */
class Interspire_EmailIntegration_MailChimp extends Interspire_EmailIntegration_MailChimp_MCAPI
{
	/**
	* Message receieved from the MailChimp API's ping() method when everything is... chimpy
	*
	* @var string
	*/
	const PING_OK = "Everything's Chimpy!";

	/**
	* Number of seconds to wait, by default, for a response from MailChimp's API
	*
	* @var int
	*/
	const MCAPI_TIMEOUT = 60;

	/**
	* Number of seconds to wait for requests that are expected to take longer, such as batch operations.
	*
	* @var int
	*/
	const MCAPI_TIMEOUT_LONG = 100;

	/**
	* Actually connect to the server and call the requested methods, parsing the result
	* You should never have to call this public function manually
	*
	* This is an override of the MCAPI callServer method to use ISC's network code. Since it's now using ISC, the error codes produced may not match MCAPI documents.
	*
	* @param string $method
	* @param array $params
	* @param int $timeout
	* @return string|bool resulting response body on success, or false on failure
	*/
	public function callServer($method, $params, $timeout = self::MCAPI_TIMEOUT)
	{
		$dc = "us1";
		if (strstr($this->api_key,"-")){
			list($key, $dc) = explode("-",$this->api_key,2);
			if (!$dc) $dc = "us1";
		}
		$host = $dc.".".$this->apiUrl["host"];
		$params["apikey"] = $this->api_key;

		$this->errorMessage = "";
		$this->errorCode = "";

		$url = 'http';
		if ($this->secure) {
			$url .= 's';
		}
		$url .= '://' . $host . $this->apiUrl['path'] . '?' . $this->apiUrl['query'] . '&method=' . $method;

		$requestOptions = new Interspire_Http_RequestOptions;
		$requestOptions->userAgent = 'MCAPI/' . $this->version . ' (BigCommerce)';

		$response = PostToRemoteFileAndGetResponse($url, http_build_query($params), $timeout, $errno, $requestOptions);

		if (!$response) {
			$this->errorMessage = "Could not connect (" . $errno . ": " . GetLang('ISC_REMOTEFILE_ERROR_' . $errno) . ")";
			$this->errorCode = $errno;
			Interspire_Event::trigger('Interspire_EmailIntegration_MailChimp/error', array(
				'method' => $method,
				'params' => $params,
				'api' => $this,
			));
			return false;
		}

		if (ini_get("magic_quotes_runtime")) {
			$response = stripslashes($response);
		}

		$serial = unserialize($response);
		if($response && $serial === false) {
			$response = array("error" => "Bad Response.  Got This: " . $response, "code" => "-99");
		} else {
			$response = $serial;
		}
		if(is_array($response) && isset($response["error"])) {
			$this->errorMessage = $response["error"];
			$this->errorCode = $response["code"];
			Interspire_Event::trigger('Interspire_EmailIntegration_MailChimp/error', array(
				'method' => $method,
				'params' => $params,
				'api' => $this,
			));
			return false;
		}

		return $response;
	}

	/**
	 * Subscribe a batch of email addresses to a list at once. If you are using a serialized version of the API, we strongly suggest that you
	 * only run this method as a POST request, and <em>not</em> a GET request. This is an Interspire override of the MCAPI method of the same
	 * name which extends our implementation's timeout for this call.
	 *
	 * @section List Related
	 *
	 * @example mcapi_listBatchSubscribe.php
	 * @example xml-rpc_listBatchSubscribe.php
	 *
	 * @param string $id the list id to connect to. Get by calling lists()
	 * @param array $batch an array of structs for each address to import with two special keys: "EMAIL" for the email address, and "EMAIL_TYPE" for the email type option (html, text, or mobile)
	 * @param boolean $double_optin flag to control whether to send an opt-in confirmation email - defaults to true
	 * @param boolean $update_existing flag to control whether to update members that are already subscribed to the list or to return an error, defaults to false (return error)
	 * @param boolean $replace_interests flag to determine whether we replace the interest groups with the updated groups provided, or we add the provided groups to the member's interest groups (optional, defaults to true)
	 * @return struct Array of result counts and any errors that occurred
	 * @returnf integer success_count Number of email addresses that were succesfully added/updated
	 * @returnf integer error_count Number of email addresses that failed during addition/updating
	 * @returnf array errors Array of error structs. Each error struct will contain "code", "message", and the full struct that failed
	 */
	public function listBatchSubscribe($id, $batch, $double_optin=true, $update_existing=false, $replace_interests=true)
	{
		$params = array();
		$params["id"] = $id;
		$params["batch"] = $batch;
		$params["double_optin"] = $double_optin;
		$params["update_existing"] = $update_existing;
		$params["replace_interests"] = $replace_interests;
		return $this->callServer("listBatchSubscribe", $params, self::MCAPI_TIMEOUT_LONG);
	}

	/**
	 * Unsubscribe a batch of email addresses to a list
	 *
	 * @section List Related
	 * @example mcapi_listBatchUnsubscribe.php
	 *
	 * @param string $id the list id to connect to. Get by calling lists()
	 * @param array $emails array of email addresses to unsubscribe
	 * @param boolean $delete_member flag to completely delete the member from your list instead of just unsubscribing, default to false
	 * @param boolean $send_goodbye flag to send the goodbye email to the email addresses, defaults to true
	 * @param boolean $send_notify flag to send the unsubscribe notification email to the address defined in the list email notification settings, defaults to false
	 * @return struct Array of result counts and any errors that occurred
	 * @returnf integer success_count Number of email addresses that were succesfully added/updated
	 * @returnf integer error_count Number of email addresses that failed during addition/updating
	 * @returnf array errors Array of error structs. Each error struct will contain "code", "message", and "email"
	 */
	public function listBatchUnsubscribe($id, $emails, $delete_member=false, $send_goodbye=true, $send_notify=false)
	{
		$params = array();
		$params["id"] = $id;
		$params["emails"] = $emails;
		$params["delete_member"] = $delete_member;
		$params["send_goodbye"] = $send_goodbye;
		$params["send_notify"] = $send_notify;
		return $this->callServer("listBatchUnsubscribe", $params, self::MCAPI_TIMEOUT_LONG);
	}
}
