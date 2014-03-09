<?php

	/**
	* This addon will check the permissions of the install to make sure that the files
	* and directories that are supposed to be writable actually are.
	*
	* @author: Rodney Amato
	* @copyright: Interspire Pty. Ltd.
	* @date: 13 June 2008
	*/

	require_once(dirname(__FILE__) . '/../../includes/classes/class.addon.php');

	class ADDON_VERIFYFILES extends ISC_ADDON
	{
		/**
		 * The path to the hash file
		 *
		 * @var string
		 **/
		private $hashPath = 'http://www.buildertemplates.com/isc/hashes/';

		/**
		 * The hash file extension
		 *
		 * @var string
		 **/
		private $hashExtension = '.txt';

		/**
		 * The full url to the hash file
		 *
		 * @var string
		 **/
		private $hashUrl = '';

		/**
		 * The full system path to the cache file
		 *
		 * @var string
		 **/
		private $cacheFile = '';

		/**
		 * The list of official file hashes
		 *
		 * @var array
		 **/
		private $hashes = array();

		/**
		* Constructor
		* Setup the addon-specific variables through the addon parent class
		*/
		public function __construct()
		{
			// Call all standard addon functions
			$this->SetId(strtolower(__CLASS__));
			$this->LoadLanguageFile();
			$this->SetName(GetLang('VerifyFilesName'));

			$this->RegisterMenuItem(
				array(
				'location'		=> 'mnuTools',
				'text'			=> GetLang('VerifyFilesMenuText'),
				'break'			=> true,
				'icon'			=> '',
				'description'	=> '',
				'id'			=> strtolower(__CLASS__)
				)
			);

			$this->SetImage('logo.gif');
		}

		/**
		* Init
		* Initialize any other addon-specific code that needs to run
		*/
		public function init()
		{
			$this->SetHelpText(GetLang('VerifyFilesHelpText'));
			$this->ShowSaveAndCancelButtons(false);

			$this->hashUrl = $this->hashPath.PRODUCT_VERSION_CODE.$this->hashExtension;
			$this->cacheFile = ISC_CACHE_DIRECTORY.'/'.PRODUCT_VERSION_CODE.'_hashes.txt';
		}

		/**
		 * Get the list of hashes from the remote file
		 *
		 * @return void
		 **/
		private function GetHashes()
		{
			if (!file_exists($this->cacheFile)) {
				$result = PostToRemoteFileAndGetResponse($this->hashUrl);
				if (strpos($result, 'init.php') === false) {
					return;
				}
				file_put_contents($this->cacheFile, $result);
				unset($result);
			}

			$lines = file($this->cacheFile);
			reset($lines);
			while (list($key, $line) = each($lines)) {
				list ($hash, $file) = preg_split('#\s+#', $line, 2, PREG_SPLIT_NO_EMPTY);
				$file = preg_replace('#^\./#', ISC_BASE_PATH.'/', trim($file));
				$this->hashes[$file][] = $hash;
				unset($lines[$key]);
			}
		}

		/**
		 * The main function for the addon. Get the file list, get the hashes and compare them
		 * then display any messages.
		 *
		 * @return void
		 **/
		public function EntryPoint()
		{
			$this->init();

			$files = $this->Find(ISC_BASE_PATH);
			$this->GetHashes();

			$modified = array();

			foreach ($files as $file) {
				if (isset($this->hashes[$file]) && is_array($this->hashes[$file])) {
					$md5 = md5_file($file);

					if (!in_array($md5, $this->hashes[$file])) {
						$modified[] = isc_html_escape(str_replace(ISC_BASE_PATH.'/', '', $file));
					}

					unset($this->hashes[$file]);
				}
			}

			// ANy files left in $hashes now do not exist on the server, and are obviously missing
			$missing = array_keys($this->hashes);

			echo '<div style="padding:10px 10px 10px 20px">';
				echo '<div class="Heading1">' . GetLang('VerifyFilesName') . '</div><br />';

				if (empty($modified) && empty($missing)) {
					echo MessageBox(GetLang('VerifyFilesNoneModified'), MSG_SUCCESS);
				} else {
					echo MessageBox(GetLang('VerifyFilesModifiedListed'), MSG_ERROR);
					echo '<br /><ul>';
					foreach($missing as $file) {
						$file = str_replace(ISC_BASE_PATH.'/', '', $file);
						// Skip the hashes file, the templates directory and the languages directory
						if($file == 'hashes.txt' || substr($file, 0, 10) == 'templates/' || substr($file, 0, 9) == 'language/') {
							continue;
						}
						// If this is a module, and they don't have the module installed at all, skip it
						if(substr($file, 0, 8) == 'modules/' || substr($file, 0, 7) == 'addons/') {
							$moduleDirectory = explode('/', $file);
							if($moduleDirectory[0] == 'addons') {
								$moduleDirectory = $moduleDirectory[0].'/'.$moduleDirectory[1];
							}
							else {
								$moduleDirectory = $moduleDirectory[0].'/'.$moduleDirectory[1].'/'.$moduleDirectory[2];
							}
							if(!file_exists(ISC_BASE_PATH.'/'.$moduleDirectory)) {
								continue;
							}
						}
						echo '<li><span style="color: red;">'.$file.' ('.GetLang('Missing').')</span></li>';
					}
					foreach ($modified as $file) {
						echo '<li>'.$file.'</li>';
					}
					echo '</ul>';
				}

			echo '</div>';
		}

		/**
		 * Gets a list of files to compare the hash of
		 *
		 * @param string Path The starting path
		 *
		 * @return array
		 **/
		private function Find($path)
		{
			$return = array();
			$ignore = array ('.', '..', 'CVS', '.svn', '.git');

			if (!is_dir($path)) {
				return $return;
			}

			$dh = opendir($path);
			if (!$dh) {
				return $return;
			}

			while (($file = readdir($dh)) !== false) {
				if (in_array($file, $ignore)) {
					continue;
				}

				$filepath = $path.'/'.$file;

				if (is_dir($filepath)) {
					$return = array_merge($return, $this->Find($filepath));
				} elseif (is_file($filepath)) {
					$return[] = $path.'/'.$file;
				}
			}
			closedir($dh);

			return $return;
		}
	}