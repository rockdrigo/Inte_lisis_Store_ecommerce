<?php
/**
 * YouTube API interaction class for Interspire Shopping Cart
 *
 * This class interacts with the YouTube API by: encoding, sending, receiving and decoding requests.
 *
 * It currently supports searching videos by keywords.
 */
class ISC_YOUTUBE
{
	/**
	 * @var integer The number of videos to request from YouTube per request.
	 */
	private $resultsPerRequest = 10;

	/**
	 * @var integer The offset to start the search results from, inclusive.
	 */
	private $startOffset = 1;

	/**
	 * @var string The field to order the YouTube results by. Can be: relevance, published, viewCount or rating.
	 */
	private $orderResultsBy = 'relevance';

	/**
	 * @var string The search string to send to YouTube when looking for videos
	 */
	private $searchQuery = '';

	/**
	 * @var string The YouTube video ID to be used when requesting data from YouTube
	 */
	private $videoId = '';

	/**
	* @var integer The time out in seconds for when making a cURL request to YouTube
	*/
	private $curlTimeOut = 5;

	/**
	* Handy for debugging purposes, this is the raw XML returned from youtube from the last request
	* @var string
	*/
	public $rawXML = '';

	/**
	* @var integer The error code number for the last error that has occurred that corresponds with one of the ERROR_ class constants.
	*/
	private $errorCode = 0;

	/**
	* @var string A helpful error message that accompanies the error code.
	*/
	private $errorMessage = '';

	/**
	* This is used after making a request to YouTube. The resulting XML is put into an SimpleXMLElement and stored in this variable.
	* @var SimpleXMLElement
	*/
	public  $requestResult;

	/**
	* Error constants
	*/
	const ERROR_NO_SEARCH_TERMS  = 1;
	const ERROR_RETURNED_BLANK   = 2;
	const ERROR_XML_EXCEPTION    = 3;
	const ERROR_CURL_INIT        = 4;
	const ERROR_CURL_EXEC        = 5;
	const ERROR_FOPEN_FAIL       = 6;


	/**
	* Perform a search for videos on YouTube using a set of keywords
	*
	* @param mixed $keywords
	*/
	public function search($keywords, $pageNumber=1)
	{
		if($pageNumber > 1) {
			$this->startOffset = ($this->resultsPerRequest * ($pageNumber-1)) + 1;
		}
		return $this->sendRequest('search', $keywords);
	}

	/**
	* This function takes the ID for a video and performs a YouTube search for it
	*
	* @param string $videoId The YouTube video ID, this is not a number, it should be a string like 3dAax1YyBQQ
	*/
	public function loadVideoById($videoId)
	{
		$this->videoId = $videoId;
		return $this->sendRequest('video');
	}

	/**
	* This function builds up and sends a request to YouTube for some videos.
	*
	* @param string $searchTerms The search query to send to YouTube
	*/
	private function sendRequest($method, $searchTerms='')
	{
		if(!empty($searchTerms)) {
			$this->searchQuery = $searchTerms;
		}

		if($method == 'search') {
			$this->searchQuery = trim($this->searchQuery);

			if(empty($this->searchQuery)) {
				// no search terms
				$this->setError(self::ERROR_NO_SEARCH_TERMS);
				return false;
			}
		}

		$requestUri = $this->getRequestUri($method);

		$resultXML = trim($this->sendGetRequest($requestUri));

		$this->rawXML = $resultXML;

		if($resultXML === false || $this->errorCode > 0) {
			return false;
		}

		if(empty($resultXML)) {
			// youtube returned blank
			$this->setError(self::ERROR_RETURNED_BLANK);
			return false;
		}

		try {
			$this->requestResult = new SimpleXMLElement($resultXML);
		} catch(Exception $exception) {
			$this->setError(self::ERROR_XML_EXCEPTION, $exception->getMessage());
			return false;
		}

		return true;
	}

	/**
	* This function takes a URI, makes a request to YouTube and returns the result.
	*
	* @param string $uri The URI to request
	*
	* @return string The response from YouTube
	*/
	private function sendGetRequest($uri)
	{
		if(function_exists('curl_init')) {
			return $this->requestUsingCurl($uri);
		}

		return $this->requestUsingFopen($uri);
	}


	/**
	 * This is the function that uses cURL to open a remote location.
	 *
	 * @param string $url The url to read the data from
	 *
	 * @return mixed It returns a string if the connection was successful and data was retrieved, false otherwise
	 */
	private function requestUsingCurl($url)
	{
		$connection = @curl_init();

		if(!$connection) {
			$this->setError(self::ERROR_CURL_INIT);
			return false;
		}

		curl_setopt($connection, CURLOPT_URL, $url);
		curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($connection, CURLOPT_FAILONERROR, true);
		curl_setopt($connection, CURLOPT_CONNECTTIMEOUT, $this->curlTimeOut);
		curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);

		$data = curl_exec($connection);

		if ($data === false) {
			$this->setError(self::ERROR_CURL_EXEC, 'Error ' . curl_errno($connection) . ': ' .curl_error($connection));
			return false;
		}

		curl_close($connection);

		return $data;
	}


	/**
	 * This is the function that uses fOpen to open a remote location.
	 *
	 * @param string $url The url to read the data from
	 *
	 * @return mixed It returns a string if the connection was successful and data was retrieved, false otherwise
	 */
	private function requestUsingFopen($url)
	{
		$data = '';
		$connection = @fopen($url, 'rb');

		if (!$connection) {
			$this->setError(self::ERROR_FOPEN_FAIL);
			return false;
		}

		while (!@feof($connection)) {
			$data .= @fgets($connection, 4096);
		}

		@fclose($connection);

		return $data;
	}

	/**
	* This function builds the URI that can be used to request videos from youtube.
	*
	* @return string Returns a URI that can be used to make a request from YouTube
	*/
	private function getRequestUri($type='search')
	{
		switch($type) {
			case 'search':
				return 'http://gdata.youtube.com/feeds/api/videos?q=' . urlencode($this->searchQuery) . '&orderby=' . $this->orderResultsBy . '&start-index='. $this->startOffset . '&max-results=' . $this->resultsPerRequest . '&v=2';
			case 'video':
				return 'http://gdata.youtube.com/feeds/api/videos/' . $this->videoId;
		}
		return;
	}

	/**
	* This function sets the member variables errorCode and errorMessage based on the error code number passed in.
	*
	* @param integer $errorCode The error code number. It must correspond with a defined constant within this class.
	* @param string $errorMessage An optional error message, some error codes have predefined error messages. Some that rely on other objects, like SimpleXMl, will pass in their own messages.
	*
	* @return void Doesn't return anything
	*/
	private function setError($errorCode, $errorMessage='')
	{
		switch($errorCode) {
			case self::ERROR_NO_SEARCH_TERMS:
				$this->errorCode = $errorCode;
				$this->errorMessage = 'No search terms entered.';
				break;
			case self::ERROR_RETURNED_BLANK:
				$this->errorCode = $errorCode;
				$this->errorMessage = 'YouTube returned a blank response.';
				break;
			case self::ERROR_CURL_INIT:
				$this->errorCode = $errorCode;
				$this->errorMessage = 'Unable to initialise cURL.';
				break;
			case self::ERROR_FOPEN_FAIL:
				$this->errorCode = $errorCode;
				$this->errorMessage = 'Unable to open the remote URL using fopen.';
				break;
			case self::ERROR_XML_EXCEPTION:
			case self::ERROR_CURL_EXEC:
				$this->errorCode = $errorCode;
				$this->errorMessage = $errorMessage;
				break;
		}
	}

	/**
	* Returns the current error code number
	*
	* @return integer An error code that corresponds with a defined constant within this class.
	*/
	public function getErrorCode()
	{
		return $this->errorCode;
	}

	/**
	* Returns a message describing the error that has occurred.
	*
	* @return string
	*/

	public function getErrorMessage()
	{
		return $this->errorMessage;
	}

}