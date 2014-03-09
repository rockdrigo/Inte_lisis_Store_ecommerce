<?php
/**
 * Captcha Class
 *
 * This class generates, manages and outputs the Captcha images in order to prevent
 * automated form submittion which causes SPAM.
 *
 * It detects if the server has GD running, if so it uses that, otherwise it
 * uses static images to generate the Captcha Code.
 *
 * @version 	$Id: class.captcha.php 11528 2010-08-10 01:31:24Z chris.boulton $
 * @author  	Jordie Bodlay <jordie@interspire.com>
 * @copyright 	Copyright (c) 2004-2006 Interspire Pty. Ltd.
 * @package 	ArticleLive NX
 *
 */

class ISC_CAPTCHA
{
	/**
	 * Holds the secret captcha code.
	 *
	 * @var string
	 */
	private $__secret;

	/**
	 * Determines the length of the code. Default is 6.
	 *
	 * @var integer
	 */
	public $length;

	/**
	 * Contains the path to the TTF font file to be used for GD.
	 *
	 * @var string
	 */
	public $font;

	/**
	 * Contains the path to the list of letter images to be used
	 * when GD isn't installed on the server.
	 *
	 * @var string
	 */
	public $imgDir;

	/**
	 * Determines the size of the font when using GD
	 *
	 * @var integer
	 */
	public $fontSize;

	/**
	 * Determines the color of the font when using GD
	 *
	 * @var string
	 */
	public $textColor;

	/**
	 * Determines one of the gradient values for the image background when
	 * using GD
	 *
	 * @var string
	 */
	public $bgCol1;

	/**
	 * Determines one of the gradient values for the image background when
	 * using GD
	 *
	 * @var string
	 */
	public $bgCol2;

	/**
	 * List of fonts available to ArticleLive
	 *
	 * @var array
	 */
	public $fontlist = array();


	/**
	 * Determines the shape of the gradient fill in the image background when
	 * using GD
	 *
	 * @var string
	 */
	public $bgFillStyle;

	/**
	 * Constructor
	 *
	 * Sets variables needed by the class
	 */
	public function __construct()
	{
		// Detect if the server has GD installed or not
		// Set variables for later use
		if (GDEnabledPNG()) {
			$this->type = 'dynamic';
		} else {
			$this->type = 'static';
		}

		// all variables
		$this->length = 6;

		// img captcha variables
		$this->imgDir = CleanPath(ISC_BASE_PATH . "/lib/captcha/letters/");

		// gd type captcha variables
		$this->LoadAvailableFonts();

		$this->fontSize		= '20';
		$this->textColor	= 'FFFFFF';
		$this->bgCol1		= '#448BB4';
		$this->bgCol2		= '#90D95B';
		$this->bgFillStyle	= 'vertical';
	}

	/**
	 * AddFont
	 *
	 * Adds a font to the list of fonts
	 *
	 * @return void
	 */
	public function AddFont($file,$path=null)
	{
		if($path == null) {
			$path = CleanPath(ISC_BASE_PATH . "/lib/captcha/fonts/");
		}

		$this->fontlist[] = str_replace("//","/",$path .'/'. $file);
	}

	/**
	* Make all the fonts in the lib/captcha/fonts directory with a ttf extension
	* available for use in the captcha
	*
	* @return void
	*/
	public function LoadAvailableFonts()
	{
		$dir = ISC_BASE_PATH."/lib/captcha/fonts/";
		$dir = str_replace("\\","/",$dir);

		$fonts = scandir($dir);
		foreach($fonts as $key => $font) {
			if($font == "." || $font == ".." || strrpos($font, ".ttf") === false) {
				unset($fonts[$key]);
			}
			else {
				$fonts[$key] = $dir.$font;
			}
		}
		$this->fontlist = array_merge($fonts, $this->fontlist);
	}

	/**
	 * LoadFont
	 *
	 * Gets a random font from the list
	 *
	 * @return string;
	 */

	public function LoadFont()
	{
		$random = array_rand($this->fontlist);
		return $this->fontlist[$random];
	}

	/**
	 * CreateSecret
	 *
	 * Generates a new random secret captcha code
	 *
	 * @return true
	 */

	public function CreateSecret()
	{
		// get random characters, set the secret variable to it
		$this->__secret = $this->GetRandom($this->length);

		//set the session variable
		$this->SetSecret();

		return true;
	}

	/**
	 * GetSecret
	 *
	 * Detects if there is already a secret saved, if so the function returns the secret
	 * otherwise it generates a new one.
	 *
	 * @return string
	 */
	public function GetSecret()
	{

		if(!isset($this->__secret) OR $this->__secret == '') {
			// if the secret is not already set, create it
			return $this->LoadSecret();
		} else {
			// otherwise return it
			return $this->__secret;
		}
	}

	/**
	 * LoadSecret
	 *
	 * If the secret is stored in the Session, retrieve and decode it
	 * Otherwise create a new secret.
	 *
	 * @return secret
	 */
	public function LoadSecret()
	{
		// if the secret stored in the session, retreive it
		// otherwise create a new secret
		if(isset($_SESSION['captchaCode'])) {
			$this->__secret = base64_decode($_SESSION['captchaCode']);
		} else {
			$this->CreateSecret();
		}
		return $this->__secret;
	}

	/**
	 * SetSecret
	 *
	 * Sets the session variable to the current secret code
	 *
	 * @return unknown
	 */
	public function SetSecret()
	{
		// delete current secret
		unset($_SESSION['captchaCode']);

		// set new secret to the session
		if($_SESSION['captchaCode'] = base64_encode($this->GetSecret())) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * GetRandom
	 *
	 * Generates a string of random alphanumeric characters of a length
	 * determined by $length
	 *
	 * @param integer $length
	 * @return string
	 */
	public function GetRandom($length=5)
	{
		// init
		$returnRandom = '';

		// make sure its an integer
		$length = (int)$length;

		$chars = array('a','b','c','d','e','f','g','h','j','k','n','p','q','r','s','t','u','v','x','y','z','2','3','4','5','6','7','8','9');
		for ($i=0; $i<$length; $i++) {
			$key = array_rand($chars);
			$returnRandom .= $chars[$key];
		}

		return $returnRandom;
	}

	/**
	 * EncodeLetter
	 *
	 * Takes in 1 letter and outputs it in a jumbled string
	 *
	 * @param char $letter
	 * @return string
	 */
	public function EncodeLetter($letter)
	{

		// make sure we have 1 letter
		$letter = substr($letter,0,1);

		// we are going to hide the single letter in
		// a string of 15 characters, 10 before, 4 after.

		// get the first random 10
		$random = $this->GetRandom(10);

		// get the last random 4
		$random2 = $this->GetRandom(4);

		// put it all together
		$together = $random.$letter.$random2;

		// encode it for the session storage
		$together = base64_encode($together);

		return $together;
	}

	/**
	 * DecodeLetter
	 *
	 * Takes in a string and extracts the hidden character inside
	 *
	 * @param string $string
	 * @return char
	 */
	public function DecodeLetter($string)
	{

		// decode the session value
		$string = base64_decode($string);

		// pic out letter out of the random string of characters
		$letter = substr($string,10,1);

		return $letter;
	}

	/**
	 * LoadImage
	 *
	 * Outputs an image determined by $this->type
	 *
	 * @param char $letter
	 * @return binary
	 */
	public function LoadImage($letter='')
	{
		// if dynamic, use GD, otherwise open imagefile
		if($this->type == 'dynamic') {
			$this->font = $this->LoadFont();
			// buffer everything so its all returned together
			ob_start();

			// are we using a preset background image
			//	if (!is_file($this->bg)) {
			$img_handle = imageCreate(110,40);
			//	} else {
			//		$img_handle = imageCreateFromPNG($this->bg);
			//	}

			// grab text color
			$col = hex2rgb($this->textColor);

			// set background
			self::gd_gradient_fill($img_handle, $this->bgFillStyle, $this->bgCol1, $this->bgCol2);

			// use the image value we set, if its not valid, default to black
			if ($col) {
				$text_color = ImageColorAllocate($img_handle, $col['r'], $col['g'], $col['b']);
			} else {
				$text_color = ImageColorAllocate($img_handle, 0, 0, 0);
			}

			$x = 0;

			// if the font-file exists then use it, otherwise, use the GD default text
			if (file_exists($this->font) && function_exists("imagettftext")) {
				$length = strlen($this->__secret);
				for($i=0; $i<$length; $i++) {

					$x = $x + (12+($i));
					imagettftext($img_handle, $this->fontSize, rand(-4, 3), $x, 30+rand(-1, 1), $text_color, $this->font, $this->__secret{$i});
				}
			} else {
				ImageString($img_handle, 5, 20, 13, $this->__secret, $text_color);
			}
			// create the image
			ImagePng($img_handle);
			ImageDestroy($img_handle);
			$content = ob_get_contents();
			ob_end_clean();

			return trim($content);
		}
		else {

			$filename = $this->imgDir.'/'.isc_strtolower($letter).'.png';

			// check to see if the settings are correct
			if (!is_dir($this->imgDir)) {
				return false;
			}

			if (!is_file($filename)) {
				return false;
			}

			ob_start();
			// output image file
			readfile($filename);
			$content = ob_get_contents();
			ob_end_clean();

			return $content;
		}
	}

	/**
	 * OutputImage
	 *
	 * Outputs the image header, then the content of the image
	 *
	 */
	public function OutputImage()
	{
		// check to see what action we need to take
		if($this->type == 'dynamic') {

			$this->LoadSecret();

			// send several headers to make sure the image is not cached

			// a date in the past
			header("Expires: Mon, 23 Jul 1993 05:00:00 GMT");

			// always modified
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

			// HTTP/1.1
			header("Cache-Control: no-store, no-cache, must-revalidate");

			header("Cache-Control: post-check=0, pre-check=0, max-age=0", false);

			header('Content-type: image/png');

			echo $this->LoadImage();
		} else {
			// find the encoded string
			$crypted = $_SERVER['QUERY_STRING'];
			// get the single letter
			$letter = $this->DecodeLetter($crypted);
			// output image
			header('Content-type: image/png');
			echo $this->LoadImage($letter);
		}
		die();
	}

	/**
	 * ShowCaptcha
	 *
	 * Returns the html img tags for the captcha image(s)
	 *
	 * @return string
	 */
	public function ShowCaptcha()
	{
		// determine what we need to show for the captcha
		if($this->type == 'dynamic') {
			// single GD generated image
			$return = "<img src='" . $GLOBALS['ShopPath'] . "/captcha.php?" . rand(500,8000) . "' alt='img' />";
			return $return;

		}
		else {
			// multiple static images
			$return = '';
			for ($i=0; $i<strlen($this->__secret); $i++) {
				$return .= "<img src='" . $GLOBALS['ShopPath'] . "/captcha.php?" . $this->EncodeLetter(substr($this->__secret,$i,1)) . "' />";
			}
			return $return;
		}
	}

	/**
	* the main function that draws the gradient. This used to be in general.php but the CAPTCHA class seems to be the only reference to it so, since it's fairly large, it's been moved here.
	*
	* @param mixed $im
	* @param mixed $direction
	* @param mixed $start
	* @param mixed $end
	*/
	public static function gd_gradient_fill($im,$direction,$start,$end)
	{
		switch ($direction) {
			case 'horizontal':
				$line_numbers = imagesx($im);
				$line_width = imagesy($im);
				list($r1,$g1,$b1) = hex2rgb($start);
				list($r2,$g2,$b2) = hex2rgb($end);
				break;
			case 'vertical':
				$line_numbers = imagesy($im);
				$line_width = imagesx($im);
				list($r1,$g1,$b1) = hex2rgb($start);
				list($r2,$g2,$b2) = hex2rgb($end);
				break;
			case 'ellipse':
			case 'circle':
				$line_numbers = sqrt(pow(imagesx($im),2)+pow(imagesy($im),2));
				$center_x = imagesx($im)/2;
				$center_y = imagesy($im)/2;
				list($r1,$g1,$b1) = hex2rgb($end);
				list($r2,$g2,$b2) = hex2rgb($start);
				break;
			case 'square':
			case 'rectangle':
				$width = imagesx($im);
				$height = imagesy($im);
				$line_numbers = max($width,$height)/2;
				list($r1,$g1,$b1) = hex2rgb($end);
				list($r2,$g2,$b2) = hex2rgb($start);
				break;
			case 'diamond':
				list($r1,$g1,$b1) = hex2rgb($end);
				list($r2,$g2,$b2) = hex2rgb($start);
				$width = imagesx($im);
				$height = imagesy($im);
				if($height > $width) {
					$rh = 1;
				}
				else {
					$rh = $width/$height;
				}
				if($width > $height) {
					$rw = 1;
				}
				else {
					$rw = $height/$width;
				}
				$line_numbers = min($width,$height);
				break;
			default:
				list($r,$g,$b) = hex2rgb($start);
				$col = imagecolorallocate($im,$r,$g,$b);
				imagefill($im, 0, 0, $col);
				return true;

		}

		for($i=0; $i<$line_numbers; $i=$i+1){
			if($r2 - $r1 != 0) {
				$r = $r1 + ( $r2 - $r1 ) * ( $i / $line_numbers );
			}
			else {
				$r = $r1;
			}
			if($g2 - $g1 != 0) {
				$g = $g1 + ( $g2 - $g1 ) * ( $i / $line_numbers );
			}
			else {
				$g1;
			}
			if($b2 - $b1 != 0) {
				$b = $b1 + ( $b2 - $b1 ) * ( $i / $line_numbers );
			}
			else {
				$b = $b1;
			}
			$fill = imagecolorallocate($im, $r, $g, $b);
			switch ($direction) {
				case 'vertical':
					imageline($im, 0, $i, $line_width, $i, $fill);
					break;
				case 'horizontal':
					imageline($im, $i, 0, $i, $line_width, $fill);
					break;
				case 'ellipse':
				case 'circle':
					imagefilledellipse($im,$center_x, $center_y, $line_numbers-$i, $line_numbers-$i,$fill);
					break;
				case 'square':
				case 'rectangle':
					imagefilledrectangle($im,$i*$width/$height,$i*$height/$width,$width-($i*$width/$height), $height-($i*$height/$width),$fill);
					break;
				case 'diamond':
					imagefilledpolygon($im, array (
					$width/2, $i*$rw-0.5*$height,
					$i*$rh-0.5*$width, $height/2,
					$width/2,1.5*$height-$i*$rw,
					1.5*$width-$i*$rh, $height/2 ), 4, $fill);
					break;
				default:
			}
		}
	}
}