<?php





/**
 * FileClass
 * Class for using files and directories
 *
 */

class FileClass
{

	private $_dir;
	private $_count;
	private $_filelist;
	private $_handle;
	private $_currentfile;
	private $_currentdir;

	public function __construct()
	{

	}

	public function SetDir($dir)
	{
		if(isc_substr($dir,-1) != '/') {
			// need it to have a trailing slash to keep it consistent
			// so when we use it elsewhere we know it has it
			$dir = $dir . '/';
		}
		$this->_dir = $dir;
	}

	public function GetDir()
	{
		return $this->_dir;
	}

	public function SetHandle($handle)
	{
		$this->_handle = $handle;
	}

	public function GetHandle()
	{
		return $this->_handle;
	}

	public function SetLoadDir($dir)
	{
		$this->SetDir($dir);
		return $this->LoadDir();
	}

	public function LoadDir($dir=null)
	{
		if(is_null($dir)) {
			$dir = $this->_dir;
		}
		if (is_dir($dir)) {
			$dh = @opendir($dir);
			if ($dh !== false) {
				$this->SetHandle($dh);
			}else {
				return false;
			}
		}else {
			return false;
		}

		return true;
	}

	public function _set($name,$value)
	{
		$this->$name = $value;
	}

	public function _get($name)
	{
		return $this->$name;
	}

	public function GetCurrentFile()
	{
		return $this->_currentfile;
	}

	public function GetCurrentDir()
	{
		return $this->_currentdir;
	}

	public function NextFile()
	{
		$file = $this->NextDirElement();
		$this->_set('_currentfile',$file);
		if ($file === false) {
			return false;
		}

		if (is_file($this->GetDir() . $file)) {
			return $file;
		} else {
			return $this->NextFile();
		}
	}

	public function NextDirElement()
	{
		if (($file = readdir($this->GetHandle())) !== false) {
			return $file;
		} else {
			return false;
		}
	}

	public function ChangeMode($file, $dirmode, $filemode, $recursive=false)
	{
		if(in_array($file, array(".",".."))) {
			return false;
		}

		if (is_dir($this->GetDir() . $file)) {
			$mode = $dirmode;
		} elseif (is_file($this->GetDir() . $file)) {
			$mode = $filemode;
		} else {
			return false;
		}

		if(isc_chmod($this->GetDir() . $file,$mode)) {
			if($recursive === true && is_dir($this->GetDir() . $file)) {

				$tmp = new FileClass;
				$tmp->SetLoadDir($this->GetDir() . $file);

				while(($f = $tmp->NextDirElement()) !== false) {
					$tmp->ChangeMode($f, $dirmode, $filemode, $recursive);
				}

				$tmp->CloseHandle();
				unset($tmp);

			} else {
				return true;
			}
		}else {
			return false;
		}
	}


	public function DeleteFile($file)
	{
		if(is_file($this->GetDir() . $file)) {
			if(unlink($this->GetDir() . $file)) {
				return true;
			} else {
				return false;
			}
		}
	}

	public function DeleteDir($dir, $Recursive=false)
	{
		if (is_dir($this->GetDir() . $dir)) {
			if($Recursive === true) {
				$tmp = new FileClass;
				$tmp->SetLoadDir($this->GetDir() . $dir);

				while(($f = $tmp->NextFile()) !== false) {
					$tmp->DeleteFile($f);
				}

				$tmp->ResetHandle();

				while(($d = $tmp->NextDir()) !== false) {

					$tmp->DeleteDir($d, $Recursive);
				}

				$tmp->CloseDirHandle();
				unset($tmp);
			}

			if(rmdir($this->GetDir() . $dir)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function NextDir($NotDots=true)
	{
		return $this->NextFolder($NotDots);
	}

	public function NextFolder($NotDots=true)
	{
		$file = $this->NextDirElement();
		$this->_set('_currentdir',$file);
		if ($file === false) {
			return false;
		}
		if (is_dir($this->GetDir() .$file) && $NotDots == false) {
			return $file;
		} elseif (is_dir($this->GetDir() .$file) && $NotDots == true && !in_array($file,array(".",".."))) {
			return $file;
		} else {
			return $this->NextFolder();
		}
	}

	public function ListFiles()
	{
		if (($file = readdir($this->GetHandle())) !== false) {
			return $file;
		} else {
			return false;
		}
	}

	public function CloseHandle()
	{
		$this->CloseDirHandle();
	}

	public function CloseDirHandle()
	{
		if ($this->GetHandle()) {
			closedir($this->GetHandle());
		}
	}

	public function ResetHandle()
	{
		$this->ResetDir();
	}

	public function ResetDir()
	{
		rewinddir($this->GetHandle());
	}

	public function GetFileExtension($file)
	{
		if (empty($file)) {
			return false;
		}
		$tmp = explode(".",$file);
		$size = count($tmp);
		$lastvalue = $size - 1;
		return $tmp[$size];
	}

	/**
	* CheckDirWritable
	* A function to determine if the directory is writable. PHP's built in function
	* doesn't always work as expected.
	* This function creates the file, writes to it, closes it and deletes it. If all
	* actions work, then the directory is writable.
	* PHP's inbuilt
	*
	* @param String $dir full directory to test if writable
	*
	* @return Boolean
	*/
	public function CheckDirWriteable($dir)
	{
		return $this->CheckDirWritable($dir);
	}

	public function CheckDirWritable($dir)
	{
		$tmpfilename = $this->CleanPath($dir) . '/' . time() . '.txt';

		$fp = @fopen($tmpfilename, 'w+');

		// check we can create a file
		if (!$fp) {
			return false;
		}

		// check we can write to the file
		if (!@fputs($fp, "testing write")) {
			return false;
		}

		// check we can close the connection
		if(!@fclose($fp)) {
			return false;
		}

		// check we can delete the file
		if(!@unlink($tmpfilename)) {
			return false;
		}

		// if we made it here, it all works. =)
		return true;

	}

	/**
	* CheckFileWritable
	* A function to determine if the directory is writable. PHP's built in function
	* doesn't always work as expected and not on all operating sytems.
	*
	* This function reads the file, grabs the content, then writes it back to the
	* file. If this all worked, the file is obviously writable.
	*
	* @param String $filename full path to the file to test
	*
	* @return Boolean
	*/
	public function CheckFileWriteable($filename)
	{
		return $this->CheckFileWritable($filename);
	}

	public function CheckFileWritable($filename)
	{

		$OrigContent = "";
		$fp = @fopen($filename, 'r+');

		// check we can read the file
		if(!$fp) {
			return false;
		}

		while (!feof($fp)) {
			$OrigContent .= fgets($fp, 8192);
		}

		// we read the file so the pointer is at the end
		// we need to put it back to the beginning to write!
		fseek($fp, 0);

		// check we can write to the file
		if(!@fputs($fp, $OrigContent)) {
			return false;
		}

		// check we can close the connection
		if(!fclose($fp)) {
			return false;
		}

		// if we made it here, it all works. =)
		return true;

	}

	/**
	* CleanPath
	* This function takes in a path and resolves the directory structure. It makes
	* sure that there is no trailing slash for consistancy. (Its eaiser to add it
	* to the string later on than remove it!)
	*
	* @param 	string	$path Takes an unresolved or existing path as string
	*
	* @return 	string 	The resolved directory structure
	*/

	public function CleanPath($path)
	{
		// init
		$result = array();

		if(IsWindowsServer()) {
			// if its windows we need to change the path a bit!
			$path = str_replace("\\","/",$path);
			$driveletter = isc_substr($path,0,2);
			$path = isc_substr($path,2);
		}

		$pathA = explode('/', $path);

		if (!$pathA[0]) {
			$result[] = '';
		}

		foreach ($pathA as $key => $dir) {
			if ($dir == '..') {
				if (end($result) == '..') {
					$result[] = '..';
				} elseif (!array_pop($result)) {
					$result[] = '..';
				}
			} elseif (strlen($dir) > 0 && $dir != '.') {
				$result[] = $dir;
			}
		}

		if (!end($pathA)) {
			$result[] = '';
		}

		$path = implode('/', $result);

		if($this->IsWindowsServer()) {
			// if its windows we need to add the drive letter back on
			$path = $driveletter . $path;
		}
		if(isc_substr($path,strlen($path)-1,1) == '/' && strlen($path) > 1) {
			$path = isc_substr($path,0,strlen($path)-1);
		}
		return $path;
	}

	/*
	 * IsWindowsServer
	 * Returns true if the system is a windows server or not (for directory paths)
	 *
	 * @return Boolean True if windows, false otherwise.
	 */
	public function IsWindowsServer()
	{
		if(isc_substr(isc_strtolower(PHP_OS), 0, 3) == 'win') {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	* Reads file content into a string, returns false on error.
	*
	* @param string $filename Full path and name of the file to read.
	* @return boolean
	*/
	public function readFromFile($filename)
	{
		if (file_exists($filename) == false) {
			return false;
		}

		if (function_exists('file_get_contents') == true) {
			return file_get_contents($filename);
		} else {
			$handle = fopen($filename, "rb");
			$contents = fread($handle, filesize($filename));
			fclose($handle);

			return $contents;
		}

		return false;
	}

	/**
	* Writes a string to a file.
	*
	* @param string $content String to write to the file.
	* @param string $filename Full path and name of the file to be written to.
	* @return boolean
	*/
	public function writeToFile($content, $filename)
	{
		$res = $this->write($content, $filename, 'w+');

		// Set the chmod just in case this was a new file.
		isc_chmod($filename, ISC_WRITEABLE_FILE_PERM);

		return $res;
	}

	/**
	* Appends a string to a file.
	*
	* @param string $content String to append to the file.
	* @param string $filename Full path and name of the file to be written to.
	* @return boolean
	*/
	public function appendToFile($content, $filename)
	{
		// Not really appending anything, just touch the file.
		if ($content == '') {
			touch($filename);
			return true;
		}

		return $this->write($content, $filename, 'a+');
	}

	/**
	* Appends a string to a file.
	*
	* @param string $content String to append to the file.
	* @param string $filename Full path and name of the file to be written to.
	* @param string $mode The write mode.
	* @return boolean
	*/
	public function write($content, $filename, $mode)
	{
		// Write/append a string to the file, base on the mode.
		$fp = fopen($filename, $mode);
		if ($fp) {
			fwrite($fp, $content);
			fclose($fp);
			return true;
		} else {
			return false;
		}

		return false;
	}

}