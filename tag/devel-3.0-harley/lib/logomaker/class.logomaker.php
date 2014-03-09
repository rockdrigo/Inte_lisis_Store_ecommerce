<?php
/**
 * class.logomaker.php
 *
 * This file contains the logo maker class.
 *
 * @package Interspire_Framework
 */

/**
 * LogoMaker
 *
 * This class will take in values such as an image file, font file and text to
 * then generate a logo. It returns the image content.
 *
 * Example Usage:
 * $logo = new LogoMaker();
 * $logo->FontPath  = './georgia.ttf'; // font file
 * $logo->ImagePath = './logo.png'; // image to use as a base
 * $logo->Text      = 'Interspire'; // text to put onto the image
 * $logo->FontSize  = '20'; // size of the font in points
 * $logo->MaxTextWidth = 190; // max width the text should take up
 * $logo->CenterText   = true; // should the text be centered on the max width
 * $logo->StartCoordinates = array(18,61); //  text starting coordinates array(x,y)
 * $data = $logo->MakeLogo(); // the final image data
 * file_put_contents('../../content_images/newlogo.png', $data); // do something with it
 *
 * @package Interspire_Framework
 */

 define('LOGOMAKER_REPEAT_X', 1);
 define('LOGOMAKER_REPEAT_Y', 2);
 define('LOGOMAKER_REPEAT', 3);
 define('LOGOMAKER_NO_REPEAT', 4);

class LogoMaker
{
	/**
	 * The full path to the image to be used for the logo
	 * @public string
	 */
	public $ImagePath = '';

	/**
	 * The full path to the font  to be used for the text of the logo
	 * @public string
	 */
	public $FontPath = '';

	/**
	 * This is used for displaying the logo in the control panel if it happens to have a transparent background
	 */
	public $displayBgImg = 'none';

	/**
	 * The size of the text to be used in points.
	 * @public string
	 */
	public $FontSize = '16';

	/**
	 * The hex color value for the font text color. 6 characters long, no #
	 * @public string
	 */
	public $FontColor = '000033';

	/**
	 * The text value that is to be placed onto the logo
	 * @public string
	 */
	public $Text;
	public $Images = array();
	public $ImageText = array();
	public $Lines = array();
	public $Shapes = array();

	/**
	 * The type of the file. Possible values: 'png', 'jpg' and 'gif'. This is
	 * defined by the class from the file path.
	 * @public string
	 */
	public $FileType = '';

	/**
	 * If the image uses a background image with text on top, set that image here
	 * @public string
	 */
	public $BackgroundImage = '';
	public $BackgroundRepeat = LOGOMAKER_NO_REPEAT;
	public $BackgroundColor = '';

	public $TransparentBackgroundColor = array(255, 255, 255);

	/**
	 * This defines whether the text should be centered in the max width, or
	 * aligned to the left.
	 * @public boolean
	 */
	public $CenterText = true;

	/**
	 * Defines the maximum width the text should take up. This is used when the
	 * $CenterText variable is true to locate the center.
	 * @public integer
	 */
	public $MaxTextWidth = 100;

	/**
	 * Defines the starting coordinates for the text within the image.
	 * $StartCoordinates[0] is the X coordinate
	 * $StartCoordinates[1] is the Y coordinate
	 * @public array
	 */
	public $StartCoordinates = array();

	/**
	 * Defined in the constructor. It returns true or false based on whether GD
	 * has been detected or not.
	 * @public boolean
	 */
	public $GDEnabled = false;

	/**
	 * Contains an error string should anything not work out.
	 * @public string
	 */
	public $Error = null;

	/**
	 * Constructor for PHP 5+. It determines whether not GD is enabled and sets
	 * the $GDEnabled variable.
	 */
	public function __construct()
	{
		// Is GD and the freetype library both enabled?
		if(function_exists('imagettftext')) {
			$this->GDEnabled = true;
		} else {
			$this->GDEnabled = false;
		}
	}

	/**
	 * NoGDError
	 * Determines the error message for when GD has not been detected. Either it
	 * is not installed or just the freetype library isn't installed.
	 *
	 * @return void Doesn't return anything.
	 */
	public function NoGDError()
	{
		if(!extension_loaded('gd')) {
			// GD isn't enabled at all
			$this->Error = 'The GD extension for PHP has not been loaded';
		}
		elseif(function_exists('imagettftext')) {
			// GD is enabled but the freetype library needed for using font files
			// is not enabled
			$this->Error = 'The FreeType library has not been included with GD for PHP.';
		}
	}

	/**
	 * NewLogo
	 * Sets up a new logo image with empty images and text
	 *
	 * @return void Doesn't return anything.
	 */
	public function NewLogo($type='png')
	{
		$this->ImageText = array();
		$this->Images = array();
		$this->SetFileType($type);
	}

	/**
	 * AddText
	 * Adds text into the image
	 * <b>Example</b>
	 * This will add some arial text to the image that is red, 30pt, 5 pixels from the top and left and
	 * is centered on a width of 190 pixels.
	 * $text_position = $this->AddText('Sample Text', 'arial.ttf', 'FF0000', 30, 5, 5, 190);
	 *
	 * @param String $text The text to be displayed on the image
	 * @param String $font The name of the font file to use for the text
	 * @param String $color The color of the text to display. Hex number without the hash #
	 * @param Integer $size The size of the text to be used in points
	 * @param Integer $x_pos The x position of the text (pixels from the top of the canvas)
	 * @param Integer $y_pos The y position of the text (pixels from the left of the canvas)
	 * @param Mixed $centerOnWidth False if no centering, an integer value representing the width that you want the text centered on.
	 *
	 * @return Array A list of the x,y cordinates of the text box just created
	 */
	public function AddText($text='Sample', $font='georgia.ttf', $color='000000', $size=20, $x_pos=0, $y_pos=0, $centerOnWidth=false, $angle=0)
	{
		// set up the new image element
		$key = count($this->ImageText);
		$this->ImageText[$key]['Text']	= $text;
		$this->ImageText[$key]['FontFile']	= $this->FontPath . $font;
		$this->ImageText[$key]['Color'] = $color;
		$this->ImageText[$key]['Size']	= $size;
		$this->ImageText[$key]['Angle'] = $angle;
		$this->ImageText[$key]['x']		= $x_pos;
		$this->ImageText[$key]['y']		= $y_pos;

		// determine the height of the text box
		$pos = $this->AccurateImageTtfBbox($size, $angle, $this->FontPath . $font, $text);
		$new_pos['width'] = abs($pos[2] - $pos[0]);
		$new_pos['height'] = $this->GetHeightText($size, $angle, $this->FontPath . $font);

		// determine the width of the image
		if($centerOnWidth !== false) {
			// is the text to be centered on a specified width?
			// if so this will alter our width, so change it in the image element
			$centerOnWidth = (int)$centerOnWidth;
			if($centerOnWidth > 0) {
				$width = floor(($centerOnWidth - $new_pos['width']) / 2);
				$width = max($width, 0);
				$x_pos = $x_pos + $width;
				$this->ImageText[$key]['x'] = $x_pos;
			}
		}

		$this->ImageText[$key]['y']	= $y_pos +  $new_pos['height'];

		// generate the array for the x and y coordinates of the text box
		$new_pos['bottom_left_x']	= $x_pos;
		$new_pos['bottom_left_y']	= $new_pos['height'] + $y_pos;
		$new_pos['bottom_right_x']	= $new_pos['width']+ $x_pos;
		$new_pos['bottom_right_y']	= $new_pos['height'] + $y_pos;
		$new_pos['top_right_x']		= $new_pos['width']+ $x_pos;
		$new_pos['top_right_y']		= $y_pos;
		$new_pos['top_left_x']		=  $x_pos;
		$new_pos['top_left_y']		= $y_pos;

		return $new_pos;
	}

	/**
	 * TextBox
	 * Works the same as AddText does not actually add the text, it just determines the position of the text
	 * <b>Example</b>
	 * This will return the size of an arial text box that is red, 30pt and 5 pixels from the top and left
	 * $text_position = $this->AddText('Sample Text', 'arial.ttf', 'FF0000', 30, 5, 5);
	 *
	 * @param String $text The text to be displayed on the image
	 * @param String $font The name of the font file to use for the text
	 * @param String $color The color of the text to display. Hex number without the hash #
	 * @param Integer $size The size of the text to be used in points
	 * @param Integer $x_pos The x position of the text (pixels from the top of the canvas)
	 * @param Integer $y_pos The y position of the text (pixels from the left of the canvas)
	 *
	 * @see AddText
	 *
	 * @return Array A list of the x,y cordinates of the text box
	 */

	public function TextBox($text='Sample', $font='georgia.ttf', $color='000000', $size=20, $x_pos=0, $y_pos=0, $angle=0)
	{
		// determine the height of the text box
		$pos = $this->AccurateImageTtfBbox($size, $angle, $this->FontPath . $font, $text);

		// set up blank values
		$pos['width']  = 0;
		$pos['height'] = 0;

		$new_pos['width'] = abs($pos[2] - $pos[0]);
		$new_pos['height'] = $this->GetHeightText($size, $angle, $this->FontPath . $font);

		// determine the width of the image
		if(isset($centerOnWidth) && $centerOnWidth !== false) {
			// is the text to be centered on a specified width?
			// if so this will alter our width, so change it in the image element
			$centerOnWidth = (int)$centerOnWidth;
			if($centerOnWidth > 0) {
				$width = floor(($centerOnWidth - $new_pos['width']) / 2);
				$width = max($width, 0);
				$x_pos = $x_pos + $width;
				$this->ImageText[$key]['x'] = $x_pos;
			}
		}

		$new_pos['bottom_left_x']	= $x_pos;
		$new_pos['bottom_left_y']	= $pos['height'] + $y_pos;
		$new_pos['bottom_right_x']	= $pos['width']+ $x_pos;
		$new_pos['bottom_right_y']	= $pos['height'] + $y_pos;
		$new_pos['top_right_x']		= $pos['width']+ $x_pos;
		$new_pos['top_right_y']		= $y_pos;
		$new_pos['top_left_x']		=  $x_pos;
		$new_pos['top_left_y']		= $y_pos;

		return $new_pos;
	}

	/**
	 * GetHeightText
	 * Determines the pizel height of a font file passed in at a particular point size.
	 * It is calculated by measuring the height of j and H (a high and low character)
	 *
	 * @param Integer $size The size of the text in points
	 * @param Integer $angle The angle of the font used, 99% of the time it will be 0
	 * @param String $font The font file used for the text
	 *
	 * @return Integer The height of the text at that font size and font file
	 */
	public function GetHeightText($size, $angle, $font)
	{

		$pos = $this->AccurateImageTtfBbox($size, $angle, $font , 'jH');

		// subtract the lower left corner from the upper left corner (both are Y positions)
		return (abs($pos[7]) - abs($pos[1]));
	}

	/**
	 * AddLine
	 * Add a line to the image with the specified width and color.
	 *
	 * @param Integer $width The width of the line to add
	 * @param Integer $color The color (hex) of the line to add
	 * @param Integer $x_pos The x position of the text (pixels from the top of the canvas)
	 * @param Integer $y_pos The y position of the text (pixels from the left of the canvas)
	 */
	public function AddLine($width, $color, $x_pos, $y_pos)
	{
		$this->Lines[] = array(
			'width' => $width,
			'color' => $color,
			'x' => $x_pos,
			'y' => $y_pos
		);
	}

	/**
	 * AddRectangle
	 * Add a rectangle to the image
	 *
	 * @param Integer $width The width of the line to add
	 * @param Integer $height The height of the line to add
	 * @param Integer $color The color (hex) of the line to add
	 * @param Integer $x_pos The x position of the text (pixels from the top of the canvas)
	 * @param Integer $y_pos The y position of the text (pixels from the left of the canvas)
	 * @param Boolean $filled Do we want to fill this image with the color? If so, set to true
	 */
	public function AddRectangle($width, $height, $color, $x_pos, $y_pos, $filled=false)
	{
		$this->Shapes[] = array(
			'type' => 'rectangle',
			'width' => $width,
			'height' => $height,
			'color' => $color,
			'x' => $x_pos,
			'y' => $y_pos,
			'filled' => $filled,
		);
	}

	/**
	 * AddImage
	 * Adds an image file to the current image
	 *
	 * @param String $image The filename of an image to add to the logo image
	 * @param Integer $x_pos The x position of the image (pixels from the top of the canvas)
	 * @param Integer $y_pos The y position of the image (pixels from the left of the canvas)
	 *
	 * @return Array The x and y coordinates of the 4 corners of the image
	 */
	public function AddImage($image, $x_pos, $y_pos)
	{
		// a new image element
		$key = count($this->Images);
		$this->Images[$key]['ImageFile'] = $this->ImagePath . $image;
		$this->Images[$key]['x'] = $x_pos;
		$this->Images[$key]['y'] = $y_pos;

		// get the size and height of the image
		list($width, $height, $type, $attr) = getimagesize($this->ImagePath . $image);

		// construct the array of the x and y positions of each image corner
		$new_pos['bottom_left_x']	= $x_pos;
		$new_pos['bottom_left_y']	= $y_pos + $height;
		$new_pos['bottom_right_x']	= $x_pos + $width;
		$new_pos['bottom_right_y']	= $y_pos + $height;
		$new_pos['top_right_x']		= $x_pos + $width;
		$new_pos['top_right_y']		= $y_pos;
		$new_pos['top_left_x']		= $x_pos;
		$new_pos['top_left_y']		= $y_pos;

		return $new_pos;
	}

	/**
	 * SetImageSize
	 * Sets the canvas width and height of the image
	 *
	 * @param Integer $width The total width of the logo image
	 * @param Integer $height The total height of the logo image
	 *
	 * @return void Doesn't return anything
	 */
	public function SetImageSize($width, $height)
	{
		$this->ImageWidth  = (int)$width;
		$this->ImageHeight = (int)$height;
	}

	/**
	 * SetBackgroundImage
	 * Sets the background image. A background image is not added to a blank canvas,
	 * but rather the background image is loaded as the canvas, so it determines the
	 * height and width of the logo image
	 *
	 * @param String $image The filename of the image to be used
	 *
	 * @return void Doesn't return anything
	 */
	public function SetBackgroundImage($image, $repeats=LOGOMAKER_NO_REPEAT)
	{
		$this->BackgroundImage = $this->ImagePath . $image;
		$this->BackgroundRepeat = $repeats;
	}

	/**
	 * SetBackgroundColor
	 * Sets the background color.
	 *
	 * @param string The background color to apply to the canvas.
	 */
	public function SetBackgroundColor($color)
	{
		$this->BackgroundColor = $color;
	}

	/**
	 * SetFileType
	 * Sets the fieltype of the current image. This determines what gd functions are used
	 * to save the image file
	 *
	 * @param String $type The filetype, can be one of these 3 options: 'png', 'gif' or 'jpg'
	 *
	 * @return void Doesn't return anything
	 */
	public function SetFileType($type)
	{
		if(in_array($type, array('png', 'gif', 'jpg'))) {
			$this->FileType = $type;
		}
	}

	/**
	 * MakeLogo
	 * The main function which checks all the input values and returns the logo
	 * image with the new text if succesful. If any errors occur, they are
	 * stored in the $Error class variable
	 *
	 * @return string The image binary data
	 */
	public function MakeLogo()
	{

		// if GD isn't enabled, we can't do anything so lets get out of here!
		if(!$this->GDEnabled) {
			$this->NoGDError();
			return false;
		}

		// if we don't know what file type it is, we can't work with it!
		if(!in_array($this->FileType, array('png', 'gif', 'jpg'))) {
			$this->Error = 'The logo image is not a known file format';
			return false;
		}

		// lets make sure GD has the right functions enabled to deal with our
		// particular file type.
		if(!$this->CanUseImageFile()) {
			$this->Error = 'The image file format "'.$FileType.'" is not supported by this copy of GD';
			return false;
		}

		if($this->BackgroundImage != null && is_file($this->BackgroundImage) && $this->BackgroundRepeat == LOGOMAKER_NO_REPEAT && !$this->BackgroundColor) {
			// everything seems good so far, lets make the new logo!
			$this->ImageHandle = $this->CreateNewImage($this->BackgroundImage);
		} else {
			if(function_exists('imagecreatetruecolor')) {
				$this->ImageHandle = imagecreatetruecolor($this->ImageWidth, $this->ImageHeight);
				if($this->FileType == "png") {
					imagesavealpha($this->ImageHandle, true);
					if(isset($this->TransparentBackground)) {
						imagealphablending($this->ImageHandle, false);
					}
					else {
						imagealphablending($this->ImageHandle, true);
					}
					$trans_color = imagecolorallocatealpha($this->ImageHandle, $this->TransparentBackgroundColor[0], $this->TransparentBackgroundColor[1], $this->TransparentBackgroundColor[2], 127);
					imagefilledrectangle($this->ImageHandle, 0, 0, $this->ImageWidth, $this->ImageHeight, $trans_color);
					imagecolortransparent($this->ImageHandle, $trans_color);
				}
				else if($this->FileType == "gif") {
					$trans_color = imagecolorallocate($this->ImageHandle, $this->TransparentBackgroundColor[0], $this->TransparentBackgroundColor[1], $this->TransparentBackgroundColor[2]);
					imagefill($this->ImageHandle, 0, 0, $trans_color);
					imagecolortransparent($this->ImageHandle, $trans_color);
				}
			} else {
				$this->ImageHandle = imagecreate($this->ImageWidth, $this->ImageHeight);
			}

			if($this->BackgroundColor) {
				$bg = $this->AddColor($this->BackgroundColor);
				imagefilledrectangle($this->ImageHandle, 0, 0, $this->ImageWidth, $this->ImageHeight, $bg);
			}

			// If we have a repeating background image, then we need to apply that now
			if($this->BackgroundImage != null && is_file($this->BackgroundImage)) {
				$InsertHandle = $this->CreateNewImage($this->BackgroundImage);
				imagealphablending($InsertHandle, true);
				$insert_x = imagesx($InsertHandle);
				$insert_y = imagesy($InsertHandle);
				$start_x = 0;
				$start_y = 0;
				switch($this->BackgroundRepeat) {
					case LOGOMAKER_REPEAT_X:
						while($start_x < $this->ImageWidth) {
							imagecopy($this->ImageHandle, $InsertHandle, $start_x, $start_y, 0, 0, $insert_x, $insert_y);
							$start_x += $insert_x;
						}
						break;
					case LOGOMAKER_REPEAT_Y:
						while($start_y < $this->ImageHeight) {
							imagecopy($this->ImageHandle, $InsertHandle, $start_x, $start_y, 0, 0, $insert_x, $insert_y);
							$start_y += $insert_y;
						}
						break;
					case LOGOMAKER_REPEAT:
						while($start_x < $this->ImageWidth) {
							while($start_y < $this->ImageHeight) {
								imagecopy($this->ImageHandle, $InsertHandle, $start_x, $start_y, 0, 0, $insert_x, $insert_y);
								$start_y += $insert_y;
							}
							imagecopy($this->ImageHandle, $InsertHandle, $start_x, $start_y, 0, 0, $insert_x, $insert_y);
							$start_x += $insert_x;
							$start_y = 0;
						}
						break;
					case LOGOMAKER_NO_REPEAT;
						imagecopy($this->ImageHandle, $InsertHandle, 0, 0, 0, 0, $insert_x, $insert_y);
						break;
				}
			}
		}
		// Loop through any lines and draw them
		if(is_array($this->Lines) && !empty($this->Lines)) {
			foreach($this->Lines as $line) {
				$color = $this->AddColor($line['color']);
				imageline($this->ImageHandle, $line['x'], $line['y'], $line['x']+$line['width'], $line['y'], $color);
			}
		}

		// Draw any shapes we need to
		if(is_array($this->Shapes) && !empty($this->Shapes)) {
			foreach($this->Shapes as $shape) {
				switch($shape['type']) {
					case "rectangle":
						if($shape['filled']) {
							$func = "imagefilledrectangle";
						}
						else {
							$func = "imagerectangle";
						}
						$color = $this->AddColor($shape['color']);
						$func($this->ImageHandle, $shape['x'], $shape['y'], $shape['x']+$shape['width'], $shape['y']+$shape['height'], $color);
						break;
				}
			}
		}

		// loop through any images and add them to the current image
		if(is_array($this->Images) && !empty($this->Images)) {
			foreach($this->Images as $key => $Image) {
				$InsertHandle = $this->CreateNewImage($Image['ImageFile']);
				imagealphablending($InsertHandle, true);
				$insert_x = imagesx($InsertHandle);
				$insert_y = imagesy($InsertHandle);
				imagecopy($this->ImageHandle, $InsertHandle, $Image['x'], $Image['y'], 0, 0, $insert_x, $insert_y);
			}
		}

		// loop through any text and add them to the current image
		if(is_array($this->ImageText) && !empty($this->ImageText)) {
			foreach($this->ImageText as $key => $Text) {
				$FontColor = $this->AddColor($Text['Color']);
				imagettftext($this->ImageHandle, $Text['Size'], $Text['Angle'], $Text['x'], $Text['y'], $FontColor, $Text['FontFile'], $Text['Text']);
			}
		}

		// Are we cropping the image to the set dimensions?
		if(isset($this->CropImage)) {
			$this->CropImageToDimensions();
		}

		// we're all done, lets return the file image
		return $this->FinishImage();
	}

	/**
	 * CropImageToDimensions
	 * Crops a final image to the dimensions specified in ImageWidth / ImageHeight.
	 */
	public function CropImageToDimensions()
	{
		$croppedImage = imagecreatetruecolor($this->ImageWidth, $this->ImageHeight);

		if (isset($this->TransparentBackground) && $this->TransparentBackground == true) {
			$transparencyIndex = imagecolortransparent($this->ImageHandle);
			$transparencyColor = array(
				'red' => $this->TransparentBackgroundColor[0],
				'green' => $this->TransparentBackgroundColor[1],
				'blue' => $this->TransparentBackgroundColor[0]
			);
			if($transparencyIndex >= 0) {
				$transparencyColor = imagecolorsforindex($this->ImageHandle, $transparencyIndex);
			}

			$transparencyIndex = imagecolorallocate($croppedImage, $transparencyColor['red'], $transparencyColor['green'], $transparencyColor['blue']);
			imagefill($croppedImage, 0, 0, $transparencyIndex);
			imagecolortransparent($croppedImage, $transparencyIndex);
		}

		$currentWidth = imagesx($this->ImageHandle);
		$currentHeight = imagesy($this->ImageHandle);
		if($currentWidth > $currentHeight) {
			$adjustedWidth = $currentWidth / ($currentHeight / $this->ImageHeight);
			$halfWidth = $adjustedWidth / 2;
			imagecopyresampled($croppedImage, $this->ImageHandle, 0, 0, 0, 0, $adjustedWidth, $this->ImageHeight, $currentWidth, $currentHeight);
			imagedestroy($this->ImageHandle);
			$this->ImageHandle = $croppedImage;
		}
		elseif(($currentWidth <$currentHeight) || ($currentWidth == $currentHeight)) {
			$adjustedHeight = $currentHeight / ($currentWidth / $this->ImageWidth);
			$halfHeight = $adjustedHeight / 2;
			imagecopyresampled($croppedImage, $this->ImageHandle, 0, 0, 0, 0, $this->ImageWidth, $adjustedHeight, $currentWidth, $currentHeight);
			imagedestroy($this->ImageHandle);
			$this->ImageHandle = $croppedImage;
		}
		else {
			imagedestroy($croppedImage);
		}
	}

	/**
	 * FinishImage
	 * Returns the final image data based upon what file type the image is.
	 *
	 * @return string The final image data value
	 */
	public function FinishImage()
	{
		// depending on the file type we need to use different functions to get
		// the final file output. These functions spit the image out if we don't
		// specify a path so we need to use ouput buffering to capture this output
		// so we can return it instead.
		ob_start();
		switch($this->FileType) {
			case 'png':
				imagepng($this->ImageHandle);
			break;

			case 'jpg':
				imagejpeg($this->ImageHandle);
			break;

			case 'gif':
				imagegif($this->ImageHandle);
			break;
		}
		$data = ob_get_contents();
		ob_end_clean();

		return $data;
	}

	/**
	 * AddColor
	 * Takes the text color value and allocates it as a color in the image
	 * handle
	 *
	 * @return integer The color allocation number to be used in gd functions
	 */
	public function AddColor($color)
	{
		// make sure they haven't included the # which is used in HTML
		$this->FontColor = str_replace('#','',$color);

		// run the function to convert the value
		$colorRGB = $this->hex2rgb($color);

		// return the GD color allocation
		return imagecolorallocate($this->ImageHandle, $colorRGB['r'], $colorRGB['g'], $colorRGB['b']);
	}

	/**
	 * hex2rgb
	 * Takes a hex color value and converts it to an 3 value array of color
	 * values for red, green and blue
	 *
	 * @return array ['r'] red color value, ['g'] green color value, ['b'] blue color value
	 */
	public function hex2rgb($hex)
	{
		// If the first char is a # strip it off
		if (substr($hex, 0, 1) == '#') {
			$hex = substr($hex, 1);
		}

		// If the string isnt the right length return false
		if (strlen($hex) != 6) {
			return false;
		}

		$vals = array();
		$vals[]  = hexdec(substr($hex, 0, 2));
		$vals[]  = hexdec(substr($hex, 2, 2));
		$vals[]  = hexdec(substr($hex, 4, 2));
		$vals['r'] = $vals[0];
		$vals['g'] = $vals[1];
		$vals['b'] = $vals[2];
		return $vals;
	}

	/**
	 * CreateNewImage
	 * Creates a new GD image handle using the appropriate image function
	 * based on the file type. It create the new image handle using the logo
	 * image.
	 *
	 * @return resource The new image handle
	 */
	public function CreateNewImage($image)
	{
		// depending on the file type we need to use different functions to
		// initialise our image and work with it

		switch($this->GetFileTypeFromFile($image)) {
			case 'png':
				return imagecreatefrompng($image);
			break;

			case 'jpg':
				return imagecreatefromjpeg($image);
			break;

			case 'gif':
				return imagecreatefromgif($image);
			break;
		}
	}

	/**
	 * CanUseImageFile
	 * Checks the file type and checks to see if GD's function for that file
	 * type is enabled.
	 *
	 * @return boolean True if the functions are enabled, false otherwise
	 */
	public function CanUseImageFile()
	{
		// if this function doesn't exist they're using a PHP version older than
		// 4.3 which isn't supported anyway, so we just return false
		if(!function_exists('gd_info')) {
			return false;
		}

		// here we find out what file formats that GD has support enabled for
		// so we're checking that it has support enabled for the file format of
		// the image file we want to use
		$GDInfo = gd_info();
		switch($this->FileType) {
			case 'jpg':
				return $GDInfo["JPG Support"];
			break;
			case 'gif':
				return ($GDInfo["GIF Create Support"] && $GDInfo["GIF Read Support"]);
			break;
			case 'png':
				return $GDInfo["PNG Support"];
			break;
			default:
				return false;
			break;
		}
	}

	/**
	 * GetFileType
	 * Takes the image file path and checks the extension to determine the file
	 * type then saves it to the class variable $FileType
	 *
	 * @return void Doesn't return anything.
	 */
	public function GetFileTypeFromFile($image)
	{
		// if there is no image defined, set the error and return false
		if(empty($image)) {
			$this->Error = 'No logo image path has been specified';
			return false;
		}
		// grabs the extension of the file
		$type = strtolower(substr(strrchr($image, "."), 1));

		if($type == 'png' || $type == 'jpg' || $type == 'gif') {
			// supported types only
			return $type;
		}elseif($type == 'jpeg') {
			// if the extension is jpeg, we want our type value to be 'jpg'
			return 'jpg';
		} else {
			// nfi what this file is, lets not try and use it
			return 'unknown';
		}
	}


	/**
	 * AccurateImageTtfBbox
	 * Creates an actual image box and measures the bounds of the box and returns
	 * The built in PHP function imagettfbbox sometimes returns different results
	 * to using the actual imagettftext function. Using this function we are guaranteed
	 * it will be the same.
	 *
	 * @return Array The x and y coordinates of the box corners.
	 */
	public function AccurateImageTtfBbox($size, $angle, $font, $text)
	{
		// creating a temporary image
		$tmpImage = imagecreate(1, 1);

		// we need a color for the text
		$black = imagecolorallocate($tmpImage, 0, 0, 0);

		// create our text box and get our position array
		$bbox = imagettftext($tmpImage, $size, $angle, 0, 0, $black, $font, $text);

		// kill the temp image, we don't need it anymore!
		imagedestroy($tmpImage);

		return $bbox;
	}
}
