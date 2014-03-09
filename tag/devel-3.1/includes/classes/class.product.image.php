<?php

class ISC_PRODUCT_IMAGE_EXCEPTION extends Exception { }

class ISC_PRODUCT_IMAGE_INVALIDSIZE_EXCEPTION extends ISC_PRODUCT_IMAGE_EXCEPTION {

	public function __construct($size)
	{
		parent::__construct(sprintf(GetLang('ProductImagesInvalidSize'), $size));
	}
}

class ISC_PRODUCT_IMAGE_INVALIDID_EXCEPTION extends ISC_PRODUCT_IMAGE_EXCEPTION { }
class ISC_PRODUCT_IMAGE_DBERROR_EXCEPTION extends ISC_PRODUCT_IMAGE_EXCEPTION { }
class ISC_PRODUCT_IMAGE_RECORDNOTFOUND_EXCEPTION extends ISC_PRODUCT_IMAGE_EXCEPTION { }
class ISC_PRODUCT_IMAGE_CANNOTDELETEFILE_EXCEPTION extends ISC_PRODUCT_IMAGE_EXCEPTION { }

class ISC_PRODUCT_IMAGE_UNSUPPORTEDIMAGETYPE_EXCEPTION extends ISC_PRODUCT_IMAGE_EXCEPTION {
	public function __construct($imageType)
	{
		return parent::__construct(sprintf(GetLang('ProductImageUnsupportedImageType'), $imageType));
	}
}

class ISC_PRODUCT_IMAGE_SOURCEFILEDOESNTEXIST_EXCEPTION extends ISC_PRODUCT_IMAGE_EXCEPTION {
	public function __construct($filename = null)
	{
		if ($filename) {
			return parent::__construct(sprintf(GetLang('ProductImageFileDoesNotExistSpecific'), $filename));
		} else {
			return parent::__construct(GetLang('ProductImageFileDoesNotExist'));
		}
	}
}

class ISC_PRODUCT_IMAGE_IMPORT_EXCEPTION extends ISC_PRODUCT_IMAGE_EXCEPTION { }

class ISC_PRODUCT_IMAGE_IMPORT_INVALIDFILENAME_EXCEPTION extends ISC_PRODUCT_IMAGE_IMPORT_EXCEPTION {
	public function __construct($filename)
	{
		return parent::__construct(sprintf(GetLang('ProductImageInvalidFilename'), $filename));
	}
}

class ISC_PRODUCT_IMAGE_IMPORT_INVALIDIMAGEFILE_EXCEPTION extends ISC_PRODUCT_IMAGE_IMPORT_EXCEPTION {
	public function __construct()
	{
		return parent::__construct(GetLang('ProductImageFileNotAnImage'));
	}
}

class ISC_PRODUCT_IMAGE_IMPORT_NOPHPSUPPORT_EXCEPTION extends ISC_PRODUCT_IMAGE_IMPORT_EXCEPTION {
	public function __construct()
	{
		return parent::__construct(GetLang('ProductImageNoProcessors'));
	}
}

class ISC_PRODUCT_IMAGE_IMPORT_EMPTYIMAGE_EXCEPTION extends ISC_PRODUCT_IMAGE_IMPORT_EXCEPTION {
	public function __construct()
	{
		return parent::__construct(GetLang('ProductImageNoWidthNoHeight'));
	}
}

class ISC_PRODUCT_IMAGE_IMPORT_CANTCREATEDIR_EXCEPTION extends ISC_PRODUCT_IMAGE_IMPORT_EXCEPTION {
	public function __construct($directory)
	{
		return parent::__construct(sprintf(GetLang('ProductImageDestinationDirectoryError'), GetConfig('ImageDirectory')));
	}
}

class ISC_PRODUCT_IMAGE_IMPORT_CANTMOVEFILE_EXCEPTION extends ISC_PRODUCT_IMAGE_IMPORT_EXCEPTION {
	public function __construct($filename)
	{
		return parent::__construct(sprintf(GetLang('ProductImageDestinationFileError'), GetConfig('ImageDirectory')));
	}
}

class ISC_PRODUCT_IMAGE_CREATEDIRECTORY_EXCEPTION extends ISC_PRODUCT_IMAGE_IMPORT_CANTCREATEDIR_EXCEPTION { }

class ISC_PRODUCT_IMAGE {

	/**
	* Product image id (pkey) from database
	*
	* @var int
	*/
	protected $_productImageId = 0;

	/**
	* Path of source (non-water-marked) image file relative to product_images directory
	*
	* @var string
	*/
	protected $_sourceFilePath;

	/**
	* Internal storage of resize results, either from resizing operations or from values saved in the database
	*
	* @var array
	*/
	protected $_resizedFileDimensions = array(
		ISC_PRODUCT_IMAGE_SIZE_ZOOM => null,
		ISC_PRODUCT_IMAGE_SIZE_STANDARD => null,
		ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL => null,
		ISC_PRODUCT_IMAGE_SIZE_TINY => null,
	);

	/**
	* Internal storage of paths to resized images, relative to the product_images directory
	*
	* @var string
	*/
	protected $_resizedFilePaths = array(
		ISC_PRODUCT_IMAGE_SIZE_ZOOM => null,
		ISC_PRODUCT_IMAGE_SIZE_STANDARD => null,
		ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL => null,
		ISC_PRODUCT_IMAGE_SIZE_TINY => null,
	);

	/**
	* Whether or not this image is selected as the thumbnail image for the product it belongs to
	*
	* @var bool
	*/
	protected $_isThumbnail;

	/**
	* The id of the product this image belongs to
	*
	* @var int
	*/
	protected $_productId = 0;

	/**
	* Intended for storing an object pointing to full information about the product this image belongs to but is currently unused -- see getProduct()
	*
	* @var stdClass
	*/
	protected $_product;

	/**
	* Whether this image is visible or not -- not currently used
	*
	* @var mixed
	*/
	protected $_visible;

	/**
	* Storage for alternate text -- not currently used
	*
	* @var string
	*/
	protected $_alternateText = '';

	/**
	* Storage for caption text -- not currently used
	*
	* @var string
	*/
	protected $_caption = '';

	/**
	* Storage for description text
	*
	* @var string
	*/
	protected $_description = '';

	/**
	* Storage for the date added timestamp
	*
	* @var int
	*/
	protected $_dateAdded = 0;

	/**
	* Stores a reference to the image library used for manipulating this product image's source image file, may be null -- see getImageLibrary()
	*
	* @var ISC_IMAGE_LIBRARY_INTERFACE
	*/
	protected $_imageLibrary;

	/**
	* Sorting value for this product image
	*
	* @var int
	*/
	protected $_sort;

	/**
	* Hash value of the product this image belongs to (only valid for images that belong to a product currently being added or copied)
	*
	* @var mixed
	*/
	protected $_productHash = '';

	/**
	* The name of the product this image belongs to, only stored if the database row that populated this instance had a product name in it
	*
	* @var string
	*/
	protected $_productName = '';

	/**
	* A shortcut to ISC_IMAGE_LIBRARY_FACTORY
	*
	* @param mixed $filePath
	*/
	public static function isValidImageFile($filePath)
	{
		return ISC_IMAGE_LIBRARY_FACTORY::isValidImageFile($filePath);
	}

	/**
	* Will add $prepend before the extension of $fileName - e.g.: prependToFileExtension('a.b.c.ext', '_d') == 'a.b.c_d.ext'
	*
	* If no extension is found, no appending will take place
	*
	* @param string $fileName
	* @param string $prepend
	*/
	public static function prependToFileExtension($fileName, $prepend)
	{
		return preg_replace('#^(.*)\.(.*)$#', '\1' . $prepend . '.\2', $fileName);
	}

	/**
	* Generate a random string of characters to a specific length based on the specified selection of characters
	*
	* @param int $length
	* @param string $selection
	*/
	public static function randomString($length, $selection = '0123456789abcdefghijklmnopqrstuvwxyz')
	{
		return Interspire_String::generateRandomString($length, $selection);
	}

	/**
	* When a new product image is uploaded, it must be assigned a directory based on safe_mode settings -- this function will generate an appropriate path (relative to the product_images directory) to move the newly uploaded image to
	*
	* @param string $fileName The filename of the new image
	* @param bool $safeMode Force the filename to be generated with safe mode either on or off, leave as default (null) to autodetect
	*/
	public static function generateSourceImageRelativeFilePath($fileName, $safeMode = null)
	{
		if ($safeMode === null) {
			$safeMode = ini_get('safe_mode');
			if ($safeMode == 1 || strtolower($safeMode) == 'on') {
				$safeMode = true;
			} else {
				$safeMode = false;
			}
		}

		$exists = true;

		while ($exists) {
			// keep generating paths until we find one that doesn't exist
			$path = chr(rand(97,122)) . '/';
			if (!$safeMode) {
				$path .= self::randomString(3, '0123456789') . '/';
			}

			$randomString = self::randomString(5, '0123456789');

			$path .= self::prependToFileExtension($fileName, '__' . $randomString);

			$exists = file_exists($path);
		}

		return $path;
	}

	/**
	* Returns the image for the given product id that would be used as the base thumbnail - if no image is marked as such, the page thumbnail will be returned, otherwise the first image according to sort order
	*
	* @param int|string $productId
	* @param bool $hash if true, will treat $productId as a hash string of a product being added, instead of a product id
	* @throws ISC_PRODUCT_IMAGE_DBERROR_EXCEPTION If an unhandled database error occurrs while attempting to fetch product image data
	* @return ISC_PRODUCT_IMAGE or false if no usable image was found
	*/
	public static function getBaseThumbnailImageForProduct($productId, $hash = false)
	{
		$db = $GLOBALS['ISC_CLASS_DB'];

		if ($hash) {
			$sql = "SELECT /*ISC_PRODUCT_IMAGE::getBaseThumbnailImageForProduct*/ * FROM `[|PREFIX|]product_images` WHERE imageprodhash = '" . $db->Quote($productId) . "' ORDER BY imageisthumb desc, imagesort LIMIT 1";
		} else {
			$sql = "SELECT /*ISC_PRODUCT_IMAGE::getBaseThumbnailImageForProduct*/ * FROM `[|PREFIX|]product_images` WHERE imageprodid = " . (int)$productId . " ORDER BY imageisthumb desc, imagesort LIMIT 1";
		}

		$result = $db->Query($sql);
		if (!$result) {
			throw new ISC_PRODUCT_IMAGE_DBERROR_EXCEPTION(sprintf(GetLang('ProductImageDatabaseError'), __CLASS__, __METHOD__, $db->GetErrorMsg()));
		}

		$row = $db->Fetch($result);
		if (!$row) {
			return false;
		}

		$image = new ISC_PRODUCT_IMAGE();
		$image->populateFromDatabaseRow($row);

		return $image;
	}

	/**
	* This is a maintenance method which will clean up the product_images table and any associated files to remove images which are no longer associated with any product.
	*
	* @return void
	*/
	public static function deleteOrphanedProductImages()
	{
		// select images where imageprodid matches no productid
		$sql = "(SELECT /*ISC_PRODUCT_IMAGE::deleteOrphanedProductImages*/ pi.* FROM `[|PREFIX|]product_images` pi LEFT JOIN `[|PREFIX|]products` p ON p.productid = pi.imageprodid WHERE pi.imageprodid <> 0 AND p.productid IS NULL)";

		// also select images where imageprodid is 0 and the added date is older than 24 hours
		$sql .= " UNION ";
		$sql .= "(SELECT * FROM `[|PREFIX|]product_images` WHERE imageprodid = 0 AND imagedateadded < " . (time() - 86400) . ")";
		$sql .= " LIMIT 200"; // limit returned items to 200, any remaining images should be picked up by subsequent calls to this method

		// call ->delete() for each image
		$db = $GLOBALS['ISC_CLASS_DB'];
		$result = $db->Query($sql);
		if (!$result) {
			throw new ISC_PRODUCT_IMAGE_DBERROR_EXCEPTION(sprintf(GetLang('ProductImageDatabaseError'), __CLASS__, __METHOD__, $db->GetErrorMsg()));
		}

		while ($row = $db->Fetch($result)) {
			$image = new ISC_PRODUCT_IMAGE();
			$image->populateFromDatabaseRow($row);
			try {
				$null = null;
				$image->delete(false, true, $null, false);
			} catch (ISC_PRODUCT_IMAGE_CANNOTDELETEFILE_EXCEPTION $exception) {
				// disregard
			}
		}
	}

	/**
	* Generate and return the SQL statement used by getAllProductImagesFromDatabase
	*
	* @return string
	*/
	public static function generateGetAllProductImagesFromDatabaseSql()
	{
		$sql = "SELECT /*ISC_PRODUCT_IMAGE::generateGetAllProductImageFromDatabaseSql*/ * FROM `[|PREFIX|]product_images`";

		return $sql;
	}

	/**
	* Retrieves all product images as an array of ISC_PRODUCT_IMAGE instances. If a large number of product images are expected use generateGetProductImagesFromDatabaseSql to directly query the db and process individual ISC_PRODUCT_IMAGE instances instead.
	*
	* @return array
	* @throws ISC_PRODUCT_IMAGE_DBERROR_EXCEPTION IF an unhandled database error occurrs while attempting to fetch product image data
	*/
	public static function getAllProductImagesFromDatabase()
	{
		$db = $GLOBALS['ISC_CLASS_DB'];

		$result = $db->Query(self::generateGetAllProductImagesFromDatabaseSql());
		if (!$result) {
			throw new ISC_PRODUCT_IMAGE_DBERROR_EXCEPTION(sprintf(GetLang('ProductImageDatabaseError'), __CLASS__, __METHOD__, $db->GetErrorMsg()));
		}

		$images = array();
		while ($row = $db->Fetch($result)) {
			$image = new ISC_PRODUCT_IMAGE();
			$image->populateFromDatabaseRow($row);
			$images[] = $image;
		}
		return $images;
	}

	/**
	* Generate and return the SQL statement used by getProductImagesFromDatabase
	*
	* @param int|string $productId The product id (or hash when $hash is true) to retrieve product images for
	* @param int $page Optional. Page number of product images to retrieve. If unspecified or null, all images will be returned. The page size is based on any settings for the front end.
	* @param bool $hash If true, $productId is treated as a hash of an unsaved product
	* @return string
	*/
	public static function generateGetProductImagesFromDatabaseSql($productId, $page = null, $hash = false)
	{
		if ($page !== null) {
			throw new Exception('$page parameter for generateGetProductImagesFromDatabaseSql is not yet implemented.');
		}

		// nearly all columns are required for populateFromDatabaseRow, so just select *
		if ($hash) {
			$sql = "SELECT /*ISC_PRODUCT_IMAGE::generateGetProductImagesFromDatabaseSql*/ * FROM `[|PREFIX|]product_images` WHERE imageprodhash = '" . $GLOBALS['ISC_CLASS_DB']->Quote($productId) . "' ORDER BY imagesort";
		} else {
			$sql = "SELECT /*ISC_PRODUCT_IMAGE::generateGetProductImagesFromDatabaseSql*/ * FROM `[|PREFIX|]product_images` WHERE imageprodid = " . (int)$productId . " ORDER BY imagesort";
		}

		return $sql;
	}

	/**
	* Retrieves images for a product as an array of ISC_PRODUCT_IMAGE instances. If a large number of product images are expected, use paging and perhaps use generateGetProductImagesFromDatabaseSql to directly query the db and process individual ISC_PRODUCT_IMAGE instances instead.
	*
	* @param int|string $productId The product id (or hash when $hash is true) to retrieve product images for
	* @param int $page Optional. Page number of product images to retrieve. If unspecified or null, all images will be returned. The page size is based on any settings for the front end.
	* @param bool $hash If true, $productId is treated as a hash of an unsaved product
	* @throws ISC_PRODUCT_IMAGE_DBERROR_EXCEPTION If an unhandled database error occurrs while attempting to fetch product image data
	* @return array An array of ISC_PRODUCT_IMAGE or empty array if no images were found
	*/
	public static function getProductImagesFromDatabase($productId, $page = null, $hash = false)
	{
		$db = $GLOBALS['ISC_CLASS_DB'];

		$result = $db->Query(self::generateGetProductImagesFromDatabaseSql($productId, $page, $hash));
		if (!$result) {
			throw new ISC_PRODUCT_IMAGE_DBERROR_EXCEPTION(sprintf(GetLang('ProductImageDatabaseError'), __CLASS__, __METHOD__, $db->GetErrorMsg()));
		}

		$images = array();
		while ($row = $db->Fetch($result)) {
			$image = new ISC_PRODUCT_IMAGE();
			$image->populateFromDatabaseRow($row);
			$images[] = $image;
		}
		return $images;
	}

	/**
	* Retrieves a single product image from the database as an instance of ISC_PRODUCT_IMAGE
	*
	* @param int $productImageId
	* @return ISC_PRODUCT_IMAGE
	*/
	public static function getProductImageFromDatabase($productImageId)
	{
		return new ISC_PRODUCT_IMAGE($productImageId);
	}

	/**
	* Returns the default JPEG compression quality defined by ISC settings (if any) as a value between 0 (worst) and 100 (best)
	*
	* @return float
	*/
	public static function getDefaultJpegQuality()
	{
		// if this is ever implemented on the settings page, change this to return a dynamic value
		return 90;
	}

	/**
	* Returns the default PNG compression level defined by ISC settings (if any) as a value between 0 (no compress) and 9 (maximum compression)
	*
	* @return int
	*/
	public static function getDefaultPngCompression()
	{
		// if this is ever implemented on the settings page, change this to return a dynamic value
		return 9;
	}

	/**
	* Returns the default PNG filters to use for product images defined by ISC settings (if any) as a bitmask value based on values of PNG_FILTER_XXX constants
	*
	*/
	public static function getDefaultPngFilters()
	{
		// if this is ever implemented on the settings page, change this to return a dynamic value
		return PNG_ALL_FILTERS;
	}

	/**
	* Returns the width, in pixels, that has been configured for a given product image size
	*
	* @param int $size One of ISC_PRODUCT_IMAGE_SIZE_XXX
	* @throws ISC_PRODUCT_IMAGE_INVALIDSIZE_EXCEPTION If an invalid size is specified
	* @return int
	*/
	public static function getSizeWidth($size)
	{
		switch ($size) {
			case ISC_PRODUCT_IMAGE_SIZE_STANDARD:
				if(isset($GLOBALS['ISC_CFG']['ProductImagesProductPageImage_width']) && (int)$GLOBALS['ISC_CFG']['ProductImagesProductPageImage_width'] > 0) {
					return min(ISC_PRODUCT_IMAGE_MAXLONGEDGE, (int)$GLOBALS['ISC_CFG']['ProductImagesProductPageImage_width']);
				} else {
					return ISC_PRODUCT_DEFAULT_IMAGE_SIZE_STANDARD;
				}
				break;

			case ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL:
				if(isset($GLOBALS['ISC_CFG']['ProductImagesStorewideThumbnail_width']) && (int)$GLOBALS['ISC_CFG']['ProductImagesStorewideThumbnail_width'] > 0) {
					return min(ISC_PRODUCT_IMAGE_MAXLONGEDGE, (int)$GLOBALS['ISC_CFG']['ProductImagesStorewideThumbnail_width']);
				} else {
					return ISC_PRODUCT_DEFAULT_IMAGE_SIZE_THUMBNAIL;
				}
				break;

			case ISC_PRODUCT_IMAGE_SIZE_TINY:
				if(isset($GLOBALS['ISC_CFG']['ProductImagesGalleryThumbnail_width']) && (int)$GLOBALS['ISC_CFG']['ProductImagesGalleryThumbnail_width'] > 0) {
					return min(ISC_PRODUCT_IMAGE_MAXLONGEDGE, (int)$GLOBALS['ISC_CFG']['ProductImagesGalleryThumbnail_width']);
				} else {
					return ISC_PRODUCT_DEFAULT_IMAGE_SIZE_TINY;
				}
				break;

			case ISC_PRODUCT_IMAGE_SIZE_ZOOM:
				if(isset($GLOBALS['ISC_CFG']['ProductImagesZoomImage_width']) && (int)$GLOBALS['ISC_CFG']['ProductImagesZoomImage_width'] > 0) {
					return min(ISC_PRODUCT_IMAGE_MAXLONGEDGE, (int)$GLOBALS['ISC_CFG']['ProductImagesZoomImage_width']);
				} else {
					return ISC_PRODUCT_DEFAULT_IMAGE_SIZE_ZOOM;
				}
				break;

			default:
				throw new ISC_PRODUCT_IMAGE_INVALIDSIZE_EXCEPTION($size);
		}

		return $width;
	}

	/**
	* Returns the height, in pixels, that has been configured for a given product image size
	*
	* @param int $size One of ISC_PRODUCT_IMAGE_SIZE_XXX
	* @throws ISC_PRODUCT_IMAGE_INVALIDSIZE_EXCEPTION If an invalid size is specified
	* @return int
	*/
	public static function getSizeHeight($size)
	{
		switch ($size) {
			case ISC_PRODUCT_IMAGE_SIZE_STANDARD:
				if(isset($GLOBALS['ISC_CFG']['ProductImagesProductPageImage_height']) && (int)$GLOBALS['ISC_CFG']['ProductImagesProductPageImage_height'] > 0) {
					return min(ISC_PRODUCT_IMAGE_MAXLONGEDGE, (int)$GLOBALS['ISC_CFG']['ProductImagesProductPageImage_height']);
				} else {
					return ISC_PRODUCT_DEFAULT_IMAGE_SIZE_STANDARD;
				}
				break;

			case ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL:
				if(isset($GLOBALS['ISC_CFG']['ProductImagesStorewideThumbnail_height']) && (int)$GLOBALS['ISC_CFG']['ProductImagesStorewideThumbnail_height'] > 0) {
					return min(ISC_PRODUCT_IMAGE_MAXLONGEDGE, (int)$GLOBALS['ISC_CFG']['ProductImagesStorewideThumbnail_height']);
				} else {
					return ISC_PRODUCT_DEFAULT_IMAGE_SIZE_THUMBNAIL;
				}
				break;

			case ISC_PRODUCT_IMAGE_SIZE_TINY:
				if(isset($GLOBALS['ISC_CFG']['ProductImagesGalleryThumbnail_height']) && (int)$GLOBALS['ISC_CFG']['ProductImagesGalleryThumbnail_height'] > 0) {
					return min(ISC_PRODUCT_IMAGE_MAXLONGEDGE, (int)$GLOBALS['ISC_CFG']['ProductImagesGalleryThumbnail_height']);
				} else {
					return ISC_PRODUCT_DEFAULT_IMAGE_SIZE_TINY;
				}
				break;

			case ISC_PRODUCT_IMAGE_SIZE_ZOOM:
				if(isset($GLOBALS['ISC_CFG']['ProductImagesZoomImage_height']) && (int)$GLOBALS['ISC_CFG']['ProductImagesZoomImage_height'] > 0) {
					return min(ISC_PRODUCT_IMAGE_MAXLONGEDGE, (int)$GLOBALS['ISC_CFG']['ProductImagesZoomImage_height']);
				} else {
					return ISC_PRODUCT_DEFAULT_IMAGE_SIZE_ZOOM;
				}
				break;

			default:
				throw new ISC_PRODUCT_IMAGE_INVALIDSIZE_EXCEPTION($size);
		}

		return $width;
	}

	/**
	* Returns a set of default image write options based on the given image type (PNG/JPG compression settings, etc.) and any product image settings in ISC
	*
	* @param int $imageType
	* @return ISC_IMAGE_WRITEOPTIONS A child class of ISC_IMAGE_WRITEOPTIONS such as ISC_IMAGE_WRITEOPTIONS_JPEG, ISC_IMAGE_WRITEOPTIONS_PNG, ISC_IMAGE_WRITEOPTIONS_GIF, etc.
	*/
	public static function getWriteOptionsForImageType($imageType)
	{
		switch ($imageType) {
			case IMAGETYPE_JPEG:
				$writeOptions = new ISC_IMAGE_WRITEOPTIONS_JPEG();
				$writeOptions->setQuality(self::getDefaultJpegQuality());
				break;

			case IMAGETYPE_PNG:
				$writeOptions = new ISC_IMAGE_WRITEOPTIONS_PNG();
				$writeOptions->setCompression(self::getDefaultPngCompression());
				$writeOptions->setFilters(self::getDefaultPngFilters());
				break;

			case IMAGETYPE_GIF:
				$writeOptions = new ISC_IMAGE_WRITEOPTIONS_GIF();
				break;

			default:
				throw new ISC_PRODUCT_IMAGE_UNSUPPORTEDIMAGETYPE_EXCEPTION($imageType);
				break;
		}

		return $writeOptions;
	}

	/**
	* Imports a temporary image file on the server to the given product. Performs validation, moves the file to it's final location and filename and returns an instance of ISC_PRODUCT_IMAGE.
	*
	* It is up to the method calling this to delete any temporary file if something goes wrong.
	*
	* @param string $temporaryPath Absolute path to the temporary image file stored on the server to be imported -- this file will need to be read so if it is an uploaded file and is in the tmp folder you should move it to the cache directory first since open_basedir restrictions may prevent the file being read from the tmp folder
	* @param string $originalFilename Original intended filename (such as the name provided by the browser when uploading a file) which may differ from the temporary file at $temporaryPath -- this should not include any directory components
	* @param int|string|bool $productId The id (or hash when $hash is true) of the product to import to, or supply as false to not save any info to the database but still return an instance of ISC_PRODUCT_IMAGE
	* @param bool $hash If true, $productId will be treated as a hash of a product in the process of being added
	* @param bool $moveTemporaryFile If true, the provided temporary file will be moved to it's new location, otherwise it will be copied
	* @param bool $generateImages If true, when importing, will attempt to generate thumbnail images -- may not be desirable if importing many images at once
	* @throws ISC_PRODUCT_IMAGE_IMPORT_INVALIDIMAGEFILE_EXCEPTION If the file is not a valid image
	* @throws ISC_PRODUCT_IMAGE_IMPORT_NOPHPSUPPORT_EXCEPTION If the image could not be processed by any installed php extensions
	* @throws ISC_PRODUCT_IMAGE_IMPORT_EMPTYIMAGE_EXCEPTION If the image is 'empty' - has 0 width or 0 height
	* @throws ISC_PRODUCT_IMAGE_IMPORT_CANTCREATEDIR_EXCEPTION If an error prevented the image's destination directory from being created (usually lack of write permissions on parent directory)
	* @throws ISC_PRODUCT_IMAGE_IMPORT_CANTMOVEFILE_EXCEPTION If an error prevented the image from being moved to the destination directory (usually lack of write permissions on parent directory)
	* @return ISC_PRODUCT_IMAGE If everything went OK
	*/
	public static function importImage($temporaryPath, $originalFilename, $productId, $hash = false, $moveTemporaryFile = true, $generateImages = true)
	{
		if (!file_exists($temporaryPath)) {
			throw new ISC_PRODUCT_IMAGE_SOURCEFILEDOESNTEXIST_EXCEPTION($temporaryPath);
		}

		try {
			$library = ISC_IMAGE_LIBRARY_FACTORY::getImageLibraryInstance($temporaryPath);
		} catch (ISC_IMAGE_LIBRARY_FACTORY_INVALIDIMAGEFILE_EXCEPTION $ex) {
			throw new ISC_PRODUCT_IMAGE_IMPORT_INVALIDIMAGEFILE_EXCEPTION();
		} catch (ISC_IMAGE_LIBRARY_FACTORY_NOPHPSUPPORT_EXCEPTION $ex) {
			throw new ISC_PRODUCT_IMAGE_IMPORT_NOPHPSUPPORT_EXCEPTION();
		}

		if ($library->getWidth() < 1 || $library->getHeight() < 1) {
			throw new ISC_PRODUCT_IMAGE_IMPORT_EMPTYIMAGE_EXCEPTION();
		}

		$finalName = $originalFilename;


		$finalName = basename($finalName); // remove any path components from the filename
		$finalName = self::sanitiseFilename($finalName);

		if (!self::isValidFilename($finalName, false)) {
			throw new ISC_PRODUCT_IMAGE_IMPORT_INVALIDFILENAME_EXCEPTION($finalName);
		}

		// correct the uploaded extension
		$correctExtension = $library->getImageTypeExtension(false);
		if (strtolower(pathinfo($finalName, PATHINFO_EXTENSION)) != $correctExtension) {
			// remove existing extension and trailing . if any
			$finalName = preg_replace('#\.[^\.]*$#', '', $finalName);
			// add correct extension
			$finalName .= '.' . $correctExtension;
		}

		// generate a path for storing in the product_images directory
		$finalRelativePath = self::generateSourceImageRelativeFilePath($finalName);

		$image = new ISC_PRODUCT_IMAGE();
		$image->setSourceFilePath($finalRelativePath);

		$finalAbsolutePath = $image->getAbsoluteSourceFilePath();
		$finalDirectory = dirname($finalAbsolutePath);

		if (!file_exists($finalDirectory)) {
			if (!isc_mkdir($finalDirectory, ISC_WRITEABLE_DIR_PERM, true)) {
				throw new ISC_PRODUCT_IMAGE_IMPORT_CANTCREATEDIR_EXCEPTION($finalDirectory);
			}
		}

		if ($moveTemporaryFile) {
			if (!@rename($temporaryPath, $finalAbsolutePath)) {
				throw new ISC_PRODUCT_IMAGE_IMPORT_CANTMOVEFILE_EXCEPTION($finalAbsolutePath);
			}
		} else {
			if (!@copy($temporaryPath, $finalAbsolutePath)) {
				throw new ISC_PRODUCT_IMAGE_IMPORT_CANTMOVEFILE_EXCEPTION($finalAbsolutePath);
			}
		}

		// check to see if the uploaded image exceeds our internal maximum image size: ISC_PRODUCT_IMAGE_MAXLONGEDGE
		if ($library->getWidth() > ISC_PRODUCT_IMAGE_MAXLONGEDGE || $library->getHeight() > ISC_PRODUCT_IMAGE_MAXLONGEDGE) {
			// if it is, resize it and overwrite the uploaded source image because we only want to store images to a maximum size of ISC_PRODUCT_IMAGE_MAXLONGEDGE x ISC_PRODUCT_IMAGE_MAXLONGEDGE
			$library->setFilePath($finalAbsolutePath);
			$library->loadImageFileToScratch();
			$library->resampleScratchToMaximumDimensions(ISC_PRODUCT_IMAGE_MAXLONGEDGE, ISC_PRODUCT_IMAGE_MAXLONGEDGE);
			$library->saveScratchToFile($finalAbsolutePath, self::getWriteOptionsForImageType($library->getImageType()));
		}

		if ($productId === false) {
			// do not assign product hash, id or save to database if $productId is false
			if ($generateImages) {
				// manually generate images since, normally, a call to saveToDatabase would do it
				$image->getResizedFileDimensions(ISC_PRODUCT_IMAGE_SIZE_TINY, true, false);
				$image->getResizedFileDimensions(ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL, true, false);
				$image->getResizedFileDimensions(ISC_PRODUCT_IMAGE_SIZE_STANDARD, true, false);
				$image->getResizedFileDimensions(ISC_PRODUCT_IMAGE_SIZE_ZOOM, true, false);
			}

			return $image;
		}

		if ($hash) {
			$image->setProductHash($productId);
		} else {
			$image->setProductId($productId);
		}

		// ISC_PRODUCT_IMAGE_SOURCEFILEDOESNTEXIST_EXCEPTION should never really happen at this point with all the checks above so, if it does, let the exception go unhandled to bubble up to a fatal error
		$image->saveToDatabase($generateImages);

		return $image;
	}

	/**
	* Takes a given filename and checks it against various rules created to suit windows / *nix systems running fat32, ntfs, ext3 file systems, returning a ruling on whether the provided filename can be used as a filename on all systems.
	*
	* @param string $filename
	* @param bool $characterCheck If true, also check characters in filename for valid characters. Can be set to false if sanitiseFilename() was just called on the string since it uses the same rules, otherwise should be left as true.
	* @return bool
	*/
	public static function isValidFilename($filename, $characterCheck = true)
	{
		// RESERVED NAMES
		// Windows: CON PRN AUX NUL CLOCK$ COM(1-9)[.*] LPT(1-9)[.*]
		// NTFS: $MFT $MFTMirr $LogFile $Volume $AttrDef $Bitmap $Boot $BadClus $Secure $Upcase $Extend
		// Common: . (dot) .. (two dots)

		if (in_array($filename, array('CON', 'PRN', 'AUX', 'CLOCK$', '$MFT', '$MFTMirr', '$LogFile', '$Volume', '$AttrDef', '$Bitmap', '$BadClus', '$Secure', '$Upcase', '$Extend', '.', '..'))) {
			return false;
		}

		if (preg_match('#^(COM|LPT)[1-9](\.[^\.]+)?$#', $filename)) {
			return false;
		}

		if ($characterCheck && strcmp($filename, self::sanitiseFilename($filename)) !== 0) {
			// character check was enabled but the result of sanitisedFilename is different from provided $filename which means $filename is too long or has invalid characters
			return false;
		}

		return true;
	}

	/**
	* Takes a given filename and returns a name which is valid on most common operating & file systems (such as Windows / *nix using FAT32, NTFS, EXT2) in terms of length and allowed characters
	*
	* @param string $filename Presumed-unsafe filename without any directory components
	* @param mixed $replacementCharacter Character to replace unsafe characters in the filename with. Typically this is an underscore and should not be more than 1 character. It can also be blank to remove invalid characters instead of replacing them.
	* @param bool $truncate If true, will also truncate the provided filename to acceptable file system limits (after any invalid character replacements). Default is true.
	* @return string Sanitised filename
	*/
	public static function sanitiseFilename($filename, $replacementCharacter = '_', $truncate = true)
	{
		// remove or replace any characters which are invalid on *nix and windows systems

		// CHARACTERS
		// FAT32 Includes: A-Z, 0-9, ! # $ % & ' ( ) - @ ^ _ ` { } ~, no trailing spaces on base name or extension, ascii values 128255
		// FAT32 Excludes: " * / : < > ? \ |, Control characters 031, Value 127 (DEL)
		// NTFS Includes: Any UTF-16 code unit except
		// NTFS Excludes: NUL (0000), / (under linux) plus \ : * ? " < > | (under windows)
		// EXT3 Includes: All bytes
		// EXT3 Excludes: NUL and /
		//
		// Worst-case support (windows + fat32 restrictions):
		// 33 !
		// 35-41 # $ % & ' ( )
		// 45-46 - .
		// 48-57 (0-9)
		// 64 @
		// 65-90 (A-Z)
		// 94-123 ^ _ ` a-z {
		// 125-126 } ~
		// 128-255 (lots)

		// replace invalid characters with placeholder character
		$filename = preg_replace('#[^!\#-)\-\.0-9@A-Z^-{\}\~\x80-\xFF]#', $replacementCharacter, $filename);

		// truncate the filename if necessary, while maintaining the file extension

		// LENGTHS
		// FAT32: 255 UTF-8 characters
		// NTFS: 255 UTF-16 code units
		// EXT3: 254 bytes
		// Worst-case support: 254 bytes (EXT3 maximum)

		if ($truncate) {
			$maxLength = 254;

			if (strlen($filename) > $maxLength) {
				$pathInfo = pathinfo($filename);
				if (isset($pathInfo['extension'])) {
					$extension = '.' . $pathInfo['extension'];
					$extensionLength = strlen($pathInfo['extension']) + 1;
				} else {
					$extension = '';
					$extensionLength = 0;
				}

				if ($extensionLength > $maxLength) {
					// this is probably something bogus, normally you won't want to change extensions but if the extension is this long then... too bad
					// this will also account for ".longfilenameslikethis"
					// truncate the whole filename
					$filename = substr($filename, 0, $maxLength);
				} else {
					// the extension is small enough to be kept, truncate the basename
					$maxBasenameLength = $maxLength - $extensionLength;
					$basename = substr($filename, 0, $maxBasenameLength);
					$filename = $basename . $extension;
				}
			}
		}

		return $filename;
	}

	/**
	* If possible will remove the extended product image directory $directoryPath if it's empty, but will not remove the standard /product_images/[a-z]/ base directories
	*
	* @param mixed $directoryPath
	* @return bool Returns true if the directory was deleted otherwise false. False may not indicate error though, the directory may just be not empty.
	*/
	public static function removeProductImageDirectory($directoryPath)
	{
		if (!preg_match('#^' . preg_quote(ISC_BASE_PATH, '#') . '/' . preg_quote(GetConfig('ImageDirectory'), '#') . '/[a-z]/[0-9]{3}$#', $directoryPath)) {
			// given directory does not match the pattern like /product_images/a/123
			return false;
		}

		if (!is_dir($directoryPath)) {
			// for some reason the given path is not a directory, leave it alone
			return false;
		}

		if (!@rmdir($directoryPath)) {
			// rmdir internally checks for empty directories
			return false;
		}

		$GLOBALS['ISC_CLASS_LOG']->LogSystemDebug('general', 'ISC_PRODUCT_IMAGE::removeProductImageDirectory has removed directory ' . $directoryPath, trace());

		return true;
	}

	/**
	* Copies all product images from the product $productId to the temporary, being-added product $productHash - this is used mainly by the 'copy product' functionality
	*
	* @param int $productId
	* @param string $productHash
	* @return array Array of ISC_PRODUCT_IMAGE instances of the new image copies
	*/
	public static function copyImagesToProductHash($productId, $productHash)
	{
		$productId = (int)$productId;
		$result = array();
		$existingImages = new ISC_PRODUCT_IMAGE_ITERATOR("SELECT * FROM `[|PREFIX|]product_images` WHERE imageprodid = " . $productId . " ORDER BY imagesort");
		foreach ($existingImages as $existingImage) {
			/** @var $existingImage ISC_PRODUCT_IMAGE */
			$image = $existingImage->copyToProductHash($productHash);

			// perform additional work specific to copying all images assigned to a product as a set
			$save = false;
			if ($existingImage->getIsThumbnail()) {
				$save = true;
				$image->setIsThumbnail(true);
			}

			if ($save) {
				$image->saveToDatabase(false);
			}

			$result[] = $image;
		}
		return $result;
	}

	/**
	 * Determines if the specified image (relative to product_images) is currently in use in the database or not based
	 * on references from variation combinations, product, category and brand images.
	 *
	 * @return bool true if the specified image is in use, otherwise false
	 * @throws Exception throws an exception with database error message if a database error occurrs
	 */
	public static function isImageInUse ($image)
	{
		$db = $GLOBALS['ISC_CLASS_DB'];

		$dbQuotedImage = $db->Quote($image);

		$queries = array();

		// variation images
		$queries[] = "
			SELECT
				combinationid
			FROM
				[|PREFIX|]product_variation_combinations
			WHERE
				vcimage = '" . $dbQuotedImage . "'
				OR vcimagethumb = '" . $dbQuotedImage . "'
				OR vcimagestd = '" . $dbQuotedImage . "'
				OR vcimagezoom = '" . $dbQuotedImage . "'
			LIMIT
				1
		";

		// product images
		$queries[] = "
			SELECT
				imageid
			FROM
				[|PREFIX|]product_images
			WHERE
				imagefile = '" . $dbQuotedImage . "'
				OR imagefiletiny = '" . $dbQuotedImage . "'
				OR imagefilethumb = '" . $dbQuotedImage . "'
				OR imagefilestd = '" . $dbQuotedImage . "'
				OR imagefilezoom = '" . $dbQuotedImage . "'
			LIMIT
				1
		";

		// category images
		$queries[] = "
			SELECT
				categoryid
			FROM
				[|PREFIX|]categories
			WHERE
				catimagefile = '" . $dbQuotedImage . "'
			LIMIT
				1
		";

		// brand images
		$queries[] = "
			SELECT
				brandid
			FROM
				[|PREFIX|]brands
			WHERE
				brandimagefile = '" . $dbQuotedImage . "'
			LIMIT
				1
		";

		foreach ($queries as $query) {
			$result = $db->Query($query);
			if (!$result) {
				// throw exception instead of returning false, since true/false is needed as a real result
				throw new Exception($db->GetErrorMsg());
			}

			$row = $db->Fetch($result);
			if ($row !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	*
	*
	* @param int $productImageId
	* @return ISC_PRODUCT_IMAGE
	*/
	public function __construct($productImageId = null)
	{
		if ($productImageId !== null) {
			$productImageId = (int)$productImageId;
			if ($productImageId) {
				$this->setProductImageId($productImageId);
				$this->loadFromDatabase();
			}
		}
	}

	public function __clone()
	{
		$this->clearImageLibrary(); // don't keep image library references after cloning since they'll point to the same memory resources
	}

	public function __destruct()
	{
		$this->clearImageLibrary(); // don't keep image library references after the product image object is invalid
	}

	/**
	* Given a database row returned by PHP database functions (that is, an array of field values with named indexes), this function will populate the current instance with those values.
	*
	* @param array $row
	* @return void
	* @throws ISC_PRODUCT_IMAGE_RECORDNOTFOUND_EXCEPTION if $row is empty
	*/
	public function populateFromDatabaseRow($row)
	{
		if (empty($row)) {
			throw new ISC_PRODUCT_IMAGE_RECORDNOTFOUND_EXCEPTION;
		}

		$this->setProductImageId((int)$row['imageid']);
		$this->setProductId((int)$row['imageprodid']);
		$this->setProductHash($row['imageprodhash']);
		$this->setSourceFilePath($row['imagefile']);
		$this->setIsThumbnail($row['imageisthumb'] == 1);
		$this->setSort((int)$row['imagesort']);
		$this->setResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_STANDARD, $row['imagefilestd']);
		$this->setResizedFileDimensions(ISC_PRODUCT_IMAGE_SIZE_STANDARD, $row['imagefilestdsize']);
		$this->setResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL, $row['imagefilethumb']);
		$this->setResizedFileDimensions(ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL, $row['imagefilethumbsize']);
		$this->setResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_TINY, $row['imagefiletiny']);
		$this->setResizedFileDimensions(ISC_PRODUCT_IMAGE_SIZE_TINY, $row['imagefiletinysize']);
		$this->setResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_ZOOM, $row['imagefilezoom']);
		$this->setResizedFileDimensions(ISC_PRODUCT_IMAGE_SIZE_ZOOM, $row['imagefilezoomsize']);
		$this->setDateAdded((int)$row['imagedateadded']);

		// if the query used joins the products table, load in useful information
		if(isset($row['prodname'])) {
			$this->setProductName($row['prodname']);
		}

		// upgrades from previous versions will probably have null as some column values when we want blank strings instead -- set internally as a blank string so when/if it's re-edited it'll be updated

		if ($row['imagedesc'] === null) {
			$this->setDescription('');
		} else {
			$this->setDescription($row['imagedesc']);
		}
	}

	/**
	* Loads data for this product image from the database into the current instance.
	*
	* Throws exceptions if anything goes wrong.
	*
	* @param int $productImageId Optional. If specified, will load data for the given product image id. Otherwise, will use the current product image id.
	* @throws ISC_PRODUCT_IMAGE_INVALIDID_EXCEPTION If an invalid product image id is specified
	* @throws ISC_PRODUCT_IMAGE_DBERROR_EXCEPTION If an unhandled database error occurred while attempting to fetch product image information
	* @throws ISC_PRODUCT_IMAGE_RECORDNOTFOUND_EXCEPTION If on otherwise valid request for product image id resulted in 0 records being returned from the database
	* @return int The product image id
	*/
	public function loadFromDatabase($productImageId = null)
	{
		if ($productImageId === null) {
			$productImageId = $this->getProductImageId();
		}

		$productImageId = (int)$productImageId;
		if (!$productImageId) {
			throw new ISC_PRODUCT_IMAGE_INVALIDID_EXCEPTION();
		}

		$sql = "SELECT /*ISC_PRODUCT_IMAGE->loadFromDatabase*/ * FROM `[|PREFIX|]product_images` WHERE imageid = " . $productImageId;
		$result = $GLOBALS['ISC_CLASS_DB']->Query($sql);

		if (!$result) {
			throw new ISC_PRODUCT_IMAGE_DBERROR_EXCEPTION(sprintf(GetLang('ProductImageDatabaseError'), __CLASS__, __METHOD__, $db->GetErrorMsg()));
		}

		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		if (!$row) {
			throw new ISC_PRODUCT_IMAGE_RECORDNOTFOUND_EXCEPTION(sprintf(GetLang("ProductImageRecordNotFound"), $productImageId));
		}

		$this->populateFromDatabaseRow($row);

		return $this->getProductImageId();
	}

	/**
	* Saves the data for the current instance to the database. If the current product image id is unspecified or 0, a new record will be created.
	*
	* Throws exceptions if anything goes wrong (several varieties are thrown by other methods called by saveToDatabase, but not all are listed here yet)
	*
	* @param bool $generateImages Default true. If true will attempt to generate all thumbnails (if necessary).
	* @return void
	* @throws ISC_PRODUCT_IMAGE_SOURCEFILEDOESNTEXIST_EXCEPTION If $generateImages is true and the source image file does not exist to be processed
	*/
	public function saveToDatabase($generateImages = true)
	{
		$db = $GLOBALS['ISC_CLASS_DB'];
		$productImageId = $this->getProductImageId();

		if (!$productImageId) {
			// checks that are performed when inserting an image

			// look for existing images against the product the image is being added to
			if ($this->getProductId()) {
				// an existing product
				$sql = "SELECT COUNT(*) FROM `[|PREFIX|]product_images` WHERE imageprodid = " . $this->getProductId();
			} else {
				// the product is being added
				$sql = "SELECT COUNT(*) FROM `[|PREFIX|]product_images` WHERE imageprodhash = '" . $db->Quote($this->getProductHash()) . "'";
			}

			$existingImageCount = $db->FetchOne($sql);

			if (!$existingImageCount) {
				// when inserting the first image
				$this->setSort(0);
				$this->setIsThumbnail(true);
			}

			if ($this->getSort() === null) {
				if ($existingImageCount) {
					// if inserting with a null sort value, and there are existing images, discover a new sort value based on the other images
					if ($this->getProductId()) {
						$sql = "SELECT MAX(imagesort) + 1 FROM `[|PREFIX|]product_images` WHERE imageprodid = " . $this->getProductId();
					} else {
						$sql = "SELECT MAX(imagesort) + 1 FROM `[|PREFIX|]product_images` WHERE imageprodhash = '" . $db->Quote($this->getProductHash()) . "'";
					}

					$sort = $db->FetchOne($sql);
					$this->setSort($sort);
				} else {
					// otherwise set the sort value to 0
					$this->setSort(0);
				}
			}

			if ($this->getIsThumbnail() === null) {
				if ($existingImageCount) {
					$this->setIsThumbnail(false);
				} else {
					// set the base thumbnail flag if we're inserting the first image with a null value
					$this->setIsThumbnail(true);
				}
			}
		}


		$data = array(
			'imageprodid' => $this->getProductId(),
			'imageprodhash' => $this->getProductHash(),
			'imagefile' => $this->getSourceFilePath(),
			'imageisthumb' => '0',
			'imagesort' => $this->getSort(),
			'imagefilestd' => $this->getResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_STANDARD, $generateImages, false),
			'imagefilethumb' => $this->getResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL, $generateImages, false),
			'imagefiletiny' => $this->getResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_TINY, $generateImages, false),
			'imagefilezoom' => $this->getResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_ZOOM, $generateImages, false),
			'imagefilestdsize' => $this->getResizedFileDimensions(ISC_PRODUCT_IMAGE_SIZE_STANDARD, $generateImages, false),
			'imagefilethumbsize' => $this->getResizedFileDimensions(ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL, $generateImages, false),
			'imagefiletinysize' => $this->getResizedFileDimensions(ISC_PRODUCT_IMAGE_SIZE_TINY, $generateImages, false),
			'imagefilezoomsize' => $this->getResizedFileDimensions(ISC_PRODUCT_IMAGE_SIZE_ZOOM, $generateImages, false),
			'imagedesc' => $this->getDescription(),
			'imagedateadded' => $this->getDateAdded(),
		);

		if ($this->getIsThumbnail()) {
			$data['imageisthumb'] = '1';
		}

		// the results of getResizedFileDimensions may be null or blank string but if it's an array it needs to be serialized to the db (popoulateFromDatabaseRow does the opposite)
		if (is_array($data['imagefilestdsize'])) {
			$data['imagefilestdsize'] = implode('x', $data['imagefilestdsize']);
		}

		if (is_array($data['imagefilethumbsize'])) {
			$data['imagefilethumbsize'] = implode('x', $data['imagefilethumbsize']);
		}

		if (is_array($data['imagefiletinysize'])) {
			$data['imagefiletinysize'] = implode('x', $data['imagefiletinysize']);
		}

		if (is_array($data['imagefilezoomsize'])) {
			$data['imagefilezoomsize'] = implode('x', $data['imagefilezoomsize']);
		}

		if (!$productImageId) {
			// record when the image was inserted so that uploads during the product add stage can be cleaned up later if the product itself is never added
			$data['imagedateadded'] = time();

			$result = $db->InsertQuery('product_images', $data);

			if ($result === false) {
				throw new ISC_PRODUCT_IMAGE_DBERROR_EXCEPTION(sprintf(GetLang('ProductImageDatabaseError'), __CLASS__, __METHOD__, $db->GetErrorMsg()));
			} else {
				$this->setProductImageId($result);
				$this->setDateAdded($data['imagedateadded']);
			}
		} else {
			if (!$db->UpdateQuery('product_images', $data, "imageid = " . $productImageId)) {
				throw new ISC_PRODUCT_IMAGE_DBERROR_EXCEPTION(sprintf(GetLang('ProductImageDatabaseError'), __CLASS__, __METHOD__, $db->GetErrorMsg()));
			}

			if ($this->getIsThumbnail()) {
				// this image is the thumbnail, make sure others are not
				if ($this->getProductHash()) {
					$db->Query("UPDATE /*ISC_PRODUCT_IMAGE->saveToDatabase*/ `[|PREFIX|]product_images` SET imageisthumb = 0 WHERE imageid <> " . $this->getProductImageId() . " AND imageprodhash = '" . $db->Quote($this->getProductHash()) . "'");
				} else if ($this->getProductId()) {
					$db->Query("UPDATE /*ISC_PRODUCT_IMAGE->saveToDatabase*/ `[|PREFIX|]product_images` SET imageisthumb = 0 WHERE imageid <> " . $this->getProductImageId() . " AND imageprodid = " . $this->getProductId());
				}
			}
		}
	}

	/**
	* Sets the id of this product image
	*
	* @param int $productImageId
	*/
	public function setProductImageId($productImageId)
	{
		$this->_productImageId = (int)$productImageId;
	}

	/**
	* Returns the id of this product image
	*
	* @return int
	*/
	public function getProductImageId()
	{
		return $this->_productImageId;
	}

	/**
	* Sets the path to the source image file for the product image, relative to the product_images directory
	*
	* @param string $sourceFilePath
	*/
	public function setSourceFilePath($sourceFilePath)
	{
		if ($this->_sourceFilePath !== $sourceFilePath) {
			$this->_sourceFilePath = $sourceFilePath;

			// clear any generated size paths
			$this->_resizedFilePaths = array(
				ISC_PRODUCT_IMAGE_SIZE_ZOOM => null,
				ISC_PRODUCT_IMAGE_SIZE_STANDARD => null,
				ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL => null,
				ISC_PRODUCT_IMAGE_SIZE_TINY => null,
			);

			$this->_resizedFileDimensions = array(
				ISC_PRODUCT_IMAGE_SIZE_ZOOM => null,
				ISC_PRODUCT_IMAGE_SIZE_STANDARD => null,
				ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL => null,
				ISC_PRODUCT_IMAGE_SIZE_TINY => null,
			);

			// as the file has changed, the image library to handle it may also change -- remove any existing library object
			$this->_imageLibrary = null;
		}
		return $this;
	}

	/**
	* Returns the path to the source image for this product image, relative to the product_imags directory
	*
	* @return string
	*/
	public function getSourceFilePath()
	{
		return $this->_sourceFilePath;
	}

	/**
	* Returns the absolute path to the source image file on the filesystem, calculated based on the ISC base directory and the image's relative directory
	*
	* @return string
	*/
	public function getAbsoluteSourceFilePath()
	{
		return ISC_BASE_PATH . '/' . GetConfig('ImageDirectory') . '/' . $this->getSourceFilePath();
	}

	/**
	* Returns only the filename of the source image for this product image. To get the full path, use getSourceFilePath or getAbsoluteSourceFilePath instead.
	*
	* @return string The source image filename, not including path
	*/
	public function getFileName()
	{
		return basename($this->getAbsoluteSourceFilePath());
	}

	/**
	* Flags the current image as the preferred thumbnail for the product it's related to. Does not automatically update other images
	*
	* @param boolean $isThumbnail
	*/
	public function setIsThumbnail($isThumbnail)
	{
		$this->_isThumbnail = !!$isThumbnail;
	}

	/**
	* Returns whether the current image is the preferred thumbnail for this product or not
	*
	* @return bool True if the image is the preferred thumbnail.
	*/
	public function getIsThumbnail()
	{
		return $this->_isThumbnail;
	}

	/**
	* Sets the product id value for this product image
	*
	* @param int $productId
	*/
	public function setProductId($productId)
	{
		$productId = (int)$productId;
		if ($this->_productId !== $productId) {
			$this->_productId = $productId;

			// as the product id has changed, remove any information about the product
			$this->_product = null;
		}
	}

	/**
	* Returns the product id for this product image
	*
	* @return int
	*/
	public function getProductId()
	{
		return $this->_productId;
	}

	/**
	* Returns information about the product this image is attached to
	*
	*/
	public function getProduct()
	{
		throw new Exception('Not Yet Implemented');
	}

	/**
	* Sets whether or not this image is visible on the front end
	*
	* @param bool $visible
	*/
	public function setVisible($visible)
	{
		$this->_visible = !!$visible;
	}

	/**
	* Gets whether or not this image is visible on the front end
	*
	* @return bool
	*/
	public function getVisible()
	{
		return $this->_visible;
	}

	/**
	* Sets the product name for the product that uses the current image
	*
	* @param string $name
	*/
	public function setProductName($name)
	{
		$this->_productName = $name;
	}

	/**
	* Returns the name of the product that owns the current product image
	*
	* @return string
	*/
	public function getProductName()
	{
		return $this->_productName;
	}

	/**
	* Sets the alternate text for this image -- intended for use in ALT or TITLE attributes.
	*
	* @param string $alternateText
	*/
	public function setAlternateText($alternateText)
	{
		$this->_alternateText = $alternateText;
	}

	/**
	* Returns the alternate text for this image -- intended for use in ALT or TITLE attributes.
	*
	* @return string
	*/
	public function getAlternateText()
	{
		return $this->_alternateText;
	}

	/**
	* Sets the caption for this image -- intended for use as a short / one-line description shown below or beside the image
	*
	* @param mixed $caption
	*/
	public function setCaption($caption)
	{
		$this->_caption = $caption;
	}

	/**
	* Returns the caption for this image -- intended for use as a short / one-line description shown below or beside the image
	*
	* @return string
	*/
	public function getCaption()
	{
		return $this->_caption;
	}

	/**
	* Sets the description for this image -- intended for use as a long image description
	*
	* @param string $description
	*/
	public function setDescription($description)
	{
		$this->_description = $description;
	}

	/**
	* Return the description for this image -- intended for use as a long image description
	*
	* @return string
	*/
	public function getDescription()
	{
		return $this->_description;
	}

	/**
	* Sets the date added timestamp value
	*
	* @param int $dateAdded
	*/
	public function setDateAdded($dateAdded)
	{
		$this->_dateAdded = $dateAdded;
	}

	/**
	* Returns timestamp value representing the date this image was added to the database
	*
	* @return int
	*/
	public function getDateAdded()
	{
		return $this->_dateAdded;
	}

	/**
	* Sets the display order for this image -- will not update the display order for other images for this product
	*
	* @param int $sort
	*/
	public function setSort($sort)
	{
		$this->_sort = (int)$sort;
	}

	/**
	* Gets the display order for this image
	*
	* @return int
	*/
	public function getSort()
	{
		return $this->_sort;
	}

	/**
	* Creates and returns an image manipulation library depending on the current image type and the libraries available on the server.
	*
	* @return ISC_IMAGE_LIBRARY_INTERFACE
	*/
	public function getImageLibrary()
	{
		if (!$this->_imageLibrary) {
			$this->_imageLibrary = ISC_IMAGE_LIBRARY_FACTORY::getImageLibraryInstance($this->getAbsoluteSourceFilePath());
		}

		return $this->_imageLibrary;
	}

	/**
	* Wipes the current image manipulation library and it's resources, if any
	*
	* @return void
	*/
	public function clearImageLibrary()
	{
		$this->_imageLibrary = null;
	}

	/**
	 * Returns the time when the given ISC_PRODUCT_IMAGE_SIZE_XXX setting was last changed according to the config file.
	 *
	 * @return int the time when the dimensions for the given size was last changed, or 0 (zero) if never changed
	 */
	public function getSizeChangedTime ($size)
	{
		switch ($size) {
			case ISC_PRODUCT_IMAGE_SIZE_STANDARD:
				$value = GetConfig('ProductImagesProductPageImage_timeChanged');
				break;
			case ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL:
				$value = GetConfig('ProductImagesStorewideThumbnail_timeChanged');
				break;
			case ISC_PRODUCT_IMAGE_SIZE_TINY:
				$value = GetConfig('ProductImagesGalleryThumbnail_timeChanged');
				break;
			case ISC_PRODUCT_IMAGE_SIZE_ZOOM:
				$value = GetConfig('ProductImagesZoomImage_timeChanged');
				break;
		}
		return (int)$value;
	}

	/**
	* Generate resized version of this product image according to ISC settings, returning the absolute file path of the newly created image. If the resized image already exists and is up to date, a new one will not be generated but the file path will still be returned.
	*
	* @param int $size One of ISC_PRODUCT_IMAGE_SIZE_XXX
	* @param bool $save If necessary, update image in database with the correct path
	* @param bool $force Set to true to bypass existing file and mtime checks to force the resized image to be generated
	* @return string Absolute file path pointing to resized image
	* @throws ISC_PRODUCT_IMAGE_SOURCEFILEDOESNTEXIST_EXCEPTION If the source file does not exist
	*/
	public function generateResizedFile($size, $save = true, $force = false)
	{
		$resizedFilePath = $this->getAbsoluteResizedFilePath($size, false, $save);

		$absoluteSourceFilePath = $this->getAbsoluteSourceFilePath();
		if (!file_exists($absoluteSourceFilePath)) {
			throw new ISC_PRODUCT_IMAGE_SOURCEFILEDOESNTEXIST_EXCEPTION($absoluteSourceFilePath);
		}

		$sourceImageModifiedTime = filemtime($absoluteSourceFilePath);
		$resizedImageModifiedTime = 0;
		$resizedImageFileExists = file_exists($resizedFilePath);
		if ($resizedImageFileExists) {
			$resizedImageModifiedTime = filemtime($resizedFilePath);
		}

		$configDimensionSettingTime = $this->getSizeChangedTime($size);

		if (!$force && $resizedImageFileExists && $sourceImageModifiedTime < $resizedImageModifiedTime && $configDimensionSettingTime < $resizedImageModifiedTime) {
			// resized file exists
			// ... and source file is hasn't changed since it was resized
			// ... and config settings haven't changed since it was resized
			// use the existing file.
			return $resizedFilePath;
		}

		$width = self::getSizeWidth($size);
		$height = self::getSizeHeight($size);

		// clamp the width and height to maximum internal sizes
		if ($width > ISC_PRODUCT_IMAGE_MAXLONGEDGE) {
			$width = ISC_PRODUCT_IMAGE_MAXLONGEDGE;
		}

		if ($height > ISC_PRODUCT_IMAGE_MAXLONGEDGE) {
			$height = ISC_PRODUCT_IMAGE_MAXLONGEDGE;
		}

		// it's possible that the destination directory may not exist yet if safe mode is off -- attempt to create it
		$resizedDirectoryPath = dirname($resizedFilePath);
		if (!file_exists($resizedDirectoryPath)) {
			if (!isc_mkdir($resizedDirectoryPath)) {
				throw new ISC_PRODUCT_IMAGE_CREATEDIRECTORY_EXCEPTION(null);
			}
		}

		// since the image library is cached we need to clear it just incase a resized image is already in memory
		$this->clearImageLibrary();

		$image = $this->getImageLibrary();
		$writeOptions = self::getWriteOptionsForImageType($image->getImageType());

		if ($image->getWidth() > $width || $image->getHeight() > $height) {
			// the source image is larger than the specified resize, so scale it down
			$image->loadImageFileToScratch();
			$image->resampleScratchToMaximumDimensions($width, $height);
			$image->saveScratchToFile($resizedFilePath, $writeOptions);
		} else {
			// the source image is smaller or equal to the specified resize, make a copy only
			copy($image->getFilePath(), $resizedFilePath);
			isc_chmod($image->getFilePath(), ISC_WRITEABLE_FILE_PERM);
		}

		$relativePath = $this->getResizedFilePath($size);
		$this->_resizedFilePaths[$size] = $relativePath;
		$this->_resizedFileDimensions[$size] = array($image->getWidth(), $image->getHeight());

		if ($save) {
			$this->saveToDatabase(false);
		}

		// remove resizing resources
		$this->clearImageLibrary();

		return $resizedFilePath;
	}

	/**
	* Remove all files associated with this image, including the original source file
	*
	* @throws ISC_PRODUCT_IMAGE_CANNOTDELETEFILE_EXCEPTION If any of the image files existed but could not be deleted
	* @return void
	*/
	public function removeFiles()
	{
		$this->removeResizedFiles();
		$this->removeSourceFile();
	}

	/**
	* Removes the source image file from the file system for this image
	*
	* @throws ISC_PRODUCT_IMAGE_CANNOTDELETEFILE_EXCEPTION If the image file exists but could not be deleted
	* @return void
	*/
	public function removeSourceFile()
	{
		$filePath = $this->getAbsoluteSourceFilePath();
		if (file_exists($filePath) && is_file($filePath)) {
			if (!@unlink($filePath)) {
				$error = error_get_last();
				throw new ISC_PRODUCT_IMAGE_CANNOTDELETEFILE_EXCEPTION(GetLang('ProductImageDeleteSourceFileError'));
			}
			$GLOBALS['ISC_CLASS_LOG']->LogSystemDebug('general', 'ISC_PRODUCT_IMAGE::removeSourceFile has deleted ' . $filePath . ' for product ' . $this->getProductId() . '#' . $this->getProductHash(), trace());
		}

		self::removeProductImageDirectory(dirname($filePath));
	}

	/**
	* Removes all resized files for the current product image from the filesystem but does not remove their paths from the database record
	*
	* @throws ISC_PRODUCT_IMAGE_CANNOTDELETEFILE_EXCEPTION If any of the image files existed but could not be deleted
	* @return void
	*/
	public function removeResizedFiles()
	{
		$this->removeResizedFile(ISC_PRODUCT_IMAGE_SIZE_ZOOM);
		$this->removeResizedFile(ISC_PRODUCT_IMAGE_SIZE_STANDARD);
		$this->removeResizedFile(ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL);
		$this->removeResizedFile(ISC_PRODUCT_IMAGE_SIZE_TINY);
	}

	/**
	* Removes the resized file for the specified size for the current product image from the filesystem but does not remove it's path from the database record
	*
	* @param int $size One of the defined ISC_PRODUCT_IMAGE_SIZE_? constants
	* @throws ISC_PRODUCT_IMAGE_CANNOTDELETEFILE_EXCEPTION If the image file exists but could not be deleted
	* @return void
	*/
	public function removeResizedFile($size)
	{
		$filePath = $this->getAbsoluteResizedFilePath($size, false);
		if (file_exists($filePath) && is_file($filePath)) {
			if (!@unlink($filePath)) {
				$error = error_get_last();
				throw new ISC_PRODUCT_IMAGE_CANNOTDELETEFILE_EXCEPTION(GetLang('ProductImageDeleteResizedFileError'));
			}
			$GLOBALS['ISC_CLASS_LOG']->LogSystemDebug('general', 'ISC_PRODUCT_IMAGE::removeResizedFile has deleted ' . $filePath . ' for product ' . $this->getProductId() . '#' . $this->getProductHash() . ' size ' . $size, trace());
		}

		self::removeProductImageDirectory(dirname($filePath));
	}

	/**
	* Returns the width and height of the actual resized version of a product image in the form of an arrray where index 0 is width, index 1 is height.
	*
	* @param int $size One of the defined ISC_PRODUCT_IMAGE_SIZE_? constants
	* @param bool $generate If necessary, generate the resized image to determine it's size otherwise will rely purely on any internally cached values. If this is called with $generate as false and no value has been stored yet, null will be returned.
	* @param bool $save If necessary, update image in the database with the correct sizes
	* @throws ISC_PRODUCT_IMAGE_INVALIDSIZE_EXCEPTION If an invalid size is specified
	* @return array index 0 is width, index 1 is height
	*/
	public function getResizedFileDimensions($size, $generate = true, $save = true)
	{
		if ($generate || $this->_resizedFileDimensions[$size] === null) {
			$imageFilePath = $this->getAbsoluteResizedFilePath($size, $generate, $save);

			// if the call above actually generated the file then the size will now be cached and we don't need to calculate it again
			if ($this->_resizedFileDimensions[$size] === null) {
				try {
					$library = ISC_IMAGE_LIBRARY_FACTORY::getImageLibraryInstance($imageFilePath);
					$this->_resizedFileDimensions[$size] = array($library->getWidth(), $library->getHeight());
				} catch (ISC_IMAGE_LIBRARY_FACTORY_FILEDOESNTEXIST_EXCEPTION $exception) {
					// do nothing, keep size as null
				}
			}
		}

		return $this->_resizedFileDimensions[$size];
	}

	/**
	* Sets the dimensions of a resized file. This sets the value only and does not actually perform any resizing. This method is primarily used when populating an ISC_PRODUCT_IMAGE instance based on database data.
	*
	* If no valid input is specified the internal value will change to null.
	*
	* @param int $size One of the defined ISC_PRODUCT_IMAGE_SIZE_? constants
	* @param array|string $dimensions Either an array(width,height) or a string in the format of "widthxheight" (i.e. "400x300")
	* @return void
	*/
	public function setResizedFileDimensions($size, $dimensions)
	{
		if (is_array($dimensions)) {
			$this->_resizedFileDimensions[$size] = $dimensions;
			return;
		}

		if (is_string($dimensions) && $dimensions && strpos($dimensions, 'x') !== false) {
			$this->_resizedFileDimensions[$size] = explode('x', $dimensions, 2);
			$this->_resizedFileDimensions[$size][0] = (int)$this->_resizedFileDimensions[$size][0];
			$this->_resizedFileDimensions[$size][1] = (int)$this->_resizedFileDimensions[$size][1];
			return;
		}

		$this->_resizedFileDimensions[$size] = null;
	}

	/**
	* Returns the file path that a resized version of this image would use, relative to the configured product_images directory. The resized image may not exist, this function simply predicts the path to the generated file. You can supply $generate as true to ensure the image exists.
	*
	* @param int $size One of the defined ISC_PRODUCT_IMAGE_SIZE_? constants
	* @param bool $generate Set to true to attempt to generate the resized image if it does not exist or it's out of date
	* @param bool $save If necessary, update image in database with the correct path
	* @throws ISC_PRODUCT_IMAGE_INVALIDSIZE_EXCEPTION If an invalid size is specified
	* @throws ISC_PRODUCT_IMAGE_SOURCEFILEDOESNTEXIST_EXCEPTION If the source file does not exist
	* @return string
	*/
	public function getResizedFilePath($size, $generate = false, $save = true)
	{
		if ($generate) {
			// the call to generateResizedFile will also call getResizedFilePath again with $generate as false - we can return here without calculating the path twice since generateResizedFile returns the file path
			$filePath = $this->generateResizedFile($size, $save);
			// generateResizedFile will return an absolute path but we require the relative path
			$filePath = substr($filePath, strlen(ISC_BASE_PATH . '/' . GetConfig('ImageDirectory') . '/'));
			return $filePath;
		}

		// the resize type affects the filename
		switch ($size) {
			case ISC_PRODUCT_IMAGE_SIZE_STANDARD:
				$sizeTag = 'std';
				break;

			case ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL:
				$sizeTag = 'thumb';
				break;

			case ISC_PRODUCT_IMAGE_SIZE_TINY:
				$sizeTag = 'tiny';
				break;

			case ISC_PRODUCT_IMAGE_SIZE_ZOOM:
				$sizeTag = 'zoom';
				break;

			default:
				throw new ISC_PRODUCT_IMAGE_INVALIDSIZE_EXCEPTION($size);
		}

		if ($this->_resizedFilePaths[$size]) {
			return $this->_resizedFilePaths[$size];
		}

		$sourceFilePath = $this->getSourceFilePath();
		$sourceFileName = basename($sourceFilePath);

		// the end of source files should be __##### -- strip it and let ::generateSourceImage... do it again
		$resizedFileName = preg_replace('#__([0-9]{5})\.([^\.]+)$#', '.\\2', $sourceFileName);

		// generate a new path to place the resized image in
		$resizedFilePath = self::generateSourceImageRelativeFilePath($resizedFileName);

		// insert the size tag before the file extension
		$resizedFilePath = self::prependToFileExtension($resizedFilePath, '_' . $sizeTag);

		// cache it
		$this->_resizedFilePaths[$size] = $resizedFilePath;

		return $resizedFilePath;
	}

	/**
	 * Returns the absolute file path that a resized version of this image would use. The resized image may not exist, this function simply predicts the path to the generated file. You can supply $generate as true to ensure the image exists.
	 *
	 * @param int $size One of the defined ISC_PRODUCT_IMAGE_SIZE_? constants
	 * @param bool $generate Set to true to attempt to generate the resized image if it does not exist or it's out of date
	 * @param bool $save If necessary, update image in database with the correct path
	 * @return string
	 * @throws ISC_PRODUCT_IMAGE_SOURCEFILEDOESNTEXIST_EXCEPTION If the source file does not exist
	 */
	public function getAbsoluteResizedFilePath($size, $generate = false, $save = true)
	{
		$path = $this->getResizedFilePath($size, $generate, $save);
		$path = ISC_BASE_PATH . '/' . GetConfig('ImageDirectory') . '/' . $path;
		return $path;
	}

	/**
	* Sets the file path to a resized version of this image -- does not perform any validation so it assumes the image is present and correct.
	*
	* @param int $size One of the defined ISC_PRODUCT_IMAGE_SIZE_? constants
	* @param string $filePath Path to the resized version of this image, relative to the product_images directory
	*/
	public function setResizedFilePath($size, $filePath)
	{
		$this->_resizedFilePaths[$size] = $filePath;
		return $this;
	}

	/**
	* Returns a URL path to the source image file.
	*
	* @param bool $ssl You may provide this option as either true or false to force the link returned by this function to either HTTPS (true) or HTTP (false), otherwise leave as the default (null) to auto-detect
	* @return string;
	*/
	public function getSourceUrl($ssl = null)
	{
		$filePath = $this->getAbsoluteSourceFilePath();

		// cut the left portion of the path off, which will be the ISC_BASE_PATH - the remaining directory should be the URL relative to the installation
		$filePath = substr($filePath, strlen(ISC_BASE_PATH));

		// as this function should return a usable url, we need encode the path incase the filename contains brackets such as "(1)" after images copied in Windows
		$filePath = explode('/', $filePath);
		foreach ($filePath as &$filePathComponent) {
			$filePathComponent = rawurlencode($filePathComponent);
		}
		$filePath = implode('/', $filePath);

		if ($ssl === null) {
			$shopPath = GetConfig('ShopPath');
		} else if ($ssl === true) {
			$shopPath = GetConfig('ShopPathSSL');
		} else {
			$shopPath = GetConfig('ShopPathNormal');
		}

		$filePath = $shopPath . $filePath;

		return $filePath;
	}

	/**
	* Returns a URL path to the specified resized version of this product image based on the current website settings. If the resized version doesn't exist yet it will be created if $generate is true.
	*
	* @param int $size One of the defined ISC_PRODUCT_IMAGE_SIZE_? constants
	* @param bool $generate Set to true to attempt to generate the resized image if it does not exist or it's out of date
	* @param bool $save If necessary, update image in database with the correct path
	* @param bool $ssl You may provide this option as either true or false to force the link returned by this function to either HTTPS (true) or HTTP (false), otherwise leave as the default (null) to auto-detect
	* @return string
	* @throws ISC_PRODUCT_IMAGE_SOURCEFILEDOESNTEXIST_EXCEPTION If the source file does not exist
	*/
	public function getResizedUrl($size, $generate = false, $save = true, $ssl = null)
	{
		$filePath = $this->getAbsoluteResizedFilePath($size, $generate, $save);

		// cut the left portion of the path off, which will be the ISC_BASE_PATH - the remaining directory should be the URL relative to the installation
		$filePath = substr($filePath, strlen(ISC_BASE_PATH));

		// as this function should return a usable url, we need encode the path incase the filename contains brackets such as "(1)" after images copied in Windows
		$filePath = explode('/', $filePath);
		foreach ($filePath as &$filePathComponent) {
			$filePathComponent = rawurlencode($filePathComponent);
		}
		$filePath = implode('/', $filePath);

		if ($ssl === null) {
			$shopPath = GetConfig('ShopPath');
		} else if ($ssl === true) {
			$shopPath = GetConfig('ShopPathSSL');
		} else {
			$shopPath = GetConfig('ShopPathNormal');
		}

		$filePath = $shopPath . $filePath;

		return $filePath;
	}

	public function setProductHash($productHash)
	{
		$this->_productHash = $productHash;
	}

	public function getProductHash()
	{
		return $this->_productHash;
	}

	/**
	* Removes the image from the database as well as removes any files recorded against it, updates sorting values of other images in the database and sets new thumbnails if the current image was the default thumbnail
	*
	* @param bool $loadFirst If specified as true (default) the latest data will be loaded from the database first before deleting to ensure all other resources being deleted are up to date, instead of relying on in-memory values
	* @param bool $deleteFiles If specified as true (default) will also attempt to delete all files on the file system associated with the image
	* @param int &$newThumbnailId By reference variable will be populated with the id of the new thumbnail image id if the image being deleted was the current thumbnail, if no new thumbnail was chosen or the current image is not a thumbnail the value set will be null
	* @param bool $adjustSort If specified as true (default) the sort index for the remaining images for this product with an index greater than this image will be decremented by 1
	* @throws ISC_PRODUCT_IMAGE_INVALIDID_EXCEPTION If the current product image id is invalid
	* @throws ISC_PRODUCT_IMAGE_DBERROR_EXCEPTION If an unhandled database error occurred while attempting to delete product image data
	* @throws ISC_PRODUCT_IMAGE_CANNOTDELETEFILE_EXCEPTION If an error occurred while attempting to delete any files from the file system
	* @return void
	*/
	public function delete($loadFirst = true, $deleteFiles = true, &$newThumbnailId = null, $adjustSort = true)
	{
		$newThumbnailId = null;
		$imageId = $this->getProductImageId();
		if (!$imageId) {
			throw new ISC_PRODUCT_IMAGE_INVALIDID_EXCEPTION();
		}

		// to properly delete an image, it's files and to update other images accordingly, we need to make sure we have the latest data
		if ($loadFirst) {
			$this->loadFromDatabase();
		}

		$db = $GLOBALS['ISC_CLASS_DB'];
		$db->StartTransaction();

		// delete the image record
		if (!$db->Query("DELETE FROM /*ISC_PRODUCT_IMAGE->delete*/ `[|PREFIX|]product_images` WHERE imageid = " . $imageId)) {
			$db->RollbackTransaction();
			throw new ISC_PRODUCT_IMAGE_DBERROR_EXCEPTION(sprintf(GetLang('ProductImageDatabaseError'), __CLASS__, __METHOD__, $db->GetErrorMsg()));
		}

		// shift remaining image sorting values
		if ($adjustSort) {
			if ($this->getProductId()) {
				$sql = "UPDATE /*ISC_PRODUCT_IMAGE->delete*/ `[|PREFIX|]product_images` SET imagesort = imagesort - 1 WHERE imageprodid = " . $this->getProductId() . " AND imagesort > " . $this->getSort();
			} else {
				$sql = "UPDATE /*ISC_PRODUCT_IMAGE->delete*/ `[|PREFIX|]product_images` SET imagesort = imagesort - 1 WHERE imageprodhash = '" . $GLOBALS['ISC_CLASS_DB']->Quote($this->getProductHash()) . "' AND imagesort > " . $this->getSort();
			}

			if (!$db->Query($sql)) {
				$db->RollbackTransaction();
				throw new ISC_PRODUCT_IMAGE_DBERROR_EXCEPTION(sprintf(GetLang('ProductImageDatabaseError'), __CLASS__, __METHOD__, $db->GetErrorMsg()));
			}
		}

		// if necessary, set another image as the thumbnail
		if ($this->getIsThumbnail()) {
			// find the next thumbnail candidate
			if ($this->getProductId()) {
				$sql = "SELECT /*ISC_PRODUCT_IMAGE->delete*/ imageid FROM `[|PREFIX|]product_images` WHERE imageprodid = " . $this->getProductId() . " ORDER BY imagesort ASC LIMIT 1";
			} else {
				$sql = "SELECT /*ISC_PRODUCT_IMAGE->delete*/ imageid FROM `[|PREFIX|]product_images` WHERE imageprodhash = '" . $GLOBALS['ISC_CLASS_DB']->Quote($this->getProductHash()) . "' ORDER BY imagesort ASC LIMIT 1";
			}

			$result = $db->Query($sql);
			if (!$result) {
				$db->RollbackTransaction();
				throw new ISC_PRODUCT_IMAGE_DBERROR_EXCEPTION(sprintf(GetLang('ProductImageDatabaseError'), __CLASS__, __METHOD__, $db->GetErrorMsg()));
			}

			$row = $db->Fetch($result);
			if ($row !== false) {
				// update it to be the new thumbnail
				if (!$db->Query("UPDATE /*ISC_PRODUCT_IMAGE->delete*/ `[|PREFIX|]product_images` SET imageisthumb = 1 WHERE imageid = " . (int)$row['imageid'])) {
					$db->RollbackTransaction();
					throw new ISC_PRODUCT_IMAGE_DBERROR_EXCEPTION(sprintf(GetLang('ProductImageDatabaseError'), __CLASS__, __METHOD__, $db->GetErrorMsg()));
				}

				$newThumbnailId = (int)$row['imageid'];
			}
		}

		$db->CommitTransaction();

		if ($deleteFiles) {
			$this->removeFiles();
		}
	}

	/**
	* Copies the currently loaded image record to an in-progress product $productHash
	*
	* @param string $productHash
	* @return ISC_PRODUCT_IMAGE New instance of ISC_PRODUCT_IMAGE representing the copied database record
	*/
	public function copyToProductHash($productHash)
	{
		// this is easiest done by 'importing' based on the current source image
		// if copying a product with many images is slow, this can be optimised by directly copying records and files because the import process will do all sorts of validation and may resize files which are already valid and sized

		$existingSourceFilePath = $this->getAbsoluteSourceFilePath();
		if (!file_exists($existingSourceFilePath)) {
			throw new ISC_PRODUCT_IMAGE_SOURCEFILEDOESNTEXIST_EXCEPTION($existingSourceFilePath);
		}

		// base the new filename off the old one but remove random numbering so the import process can randomise it again
		$newSourceFileName = basename($existingSourceFilePath);
		$newSourceFileName = preg_replace('#__([0-9]{5})\.([^\.]+)$#', '.\\2', $newSourceFileName);

		$image = self::importImage($existingSourceFilePath, $newSourceFileName, $productHash, true, false, false);

		// perform additional work to inherit image properties that aren't carried over by a raw file import
		$save = false;

		if ($this->getDescription()) {
			$save = true;
			$image->setDescription($this->getDescription());
		}

		// note: probably don't want to copy thumbnail and sort values, but alternate text and caption will have to go here if they are implemented in future

		if ($save) {
			$image->saveToDatabase(false);
		}

		return $image;
	}
}
