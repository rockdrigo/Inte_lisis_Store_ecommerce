<?php

// include the base class
require ISC_BASE_PATH."/lib/designmode/class.designmode.php";

class ISC_ADMIN_DESIGNMODE extends DesignMode
{
	public function __construct()
	{
		$this->template = Interspire_Template::getInstance('admin');
		$this->auth = getClass('ISC_ADMIN_AUTH');
		$this->engine = getClass('ISC_ADMIN_ENGINE');

		$this->templateDirectories = array(
			ISC_BASE_PATH.'/templates/__master/',
			ISC_BASE_PATH.'/templates/'.GetConfig('template').'/',
		);

		$this->directoryTypes = array(
			'StyleSheet' => 'Styles',
			'Layout' => '',
			'Snippet' => 'Snippets',
			'Panel' => 'Panels'
		);
	}

  /**
   * ISC_ADMIN_DESIGNMODE::HandleToDo()
   *
   * @return
   */
	public function HandleToDo()
	{
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('layout');
		if(isset($_REQUEST['ToDo'])) {
			$do = $_REQUEST['ToDo'];
		}
		else {
			$do = '';
		}

		// Include the Admin authorisation class
		if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->IsLoggedIn() && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Design_Mode)) {
			switch(isc_strtolower($do)) {
				case "saveupdatedfile": {
						$this->SaveFile();
						break;
				}
				case "editfile": {
						$this->EditFile();
						break;
				}
				case "revertfile": {
						$this->RevertFile();
						break;
				}
				default: {
						$this->UpdateLayoutPanels();
				}
			}
		} else {
			$GLOBALS["ISC_CLASS_ADMIN_ENGINE"]->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
		}
	}

  /**
   * ISC_ADMIN_DESIGNMODE::SaveFile()
   *
   * @return
   */
	protected function SaveFile()
	{
		// set the filename and content to the class variables
		$this->Set('FileContent',$_REQUEST['FileContent']);
		$this->Set('FileName',$_REQUEST['File']);

		if(parent::SaveFile()) {
			// file saved!

			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($_REQUEST['File']);

			$GLOBALS['SavedOK'] = true;
			$this->EditFile();
		} else {
			// file didn't save =(
			$GLOBALS['SavedOK'] = false;
			$this->EditFile();
		}

	}

  /**
   * ISC_ADMIN_DESIGNMODE::EditFile()
   *
   * @return
   */
	protected function EditFile()
	{
		if (isset($_REQUEST['File'])) {
			// set the filename
			$this->Set('FileName', $_REQUEST['File']);

			$filePath = '';
			foreach(array_reverse($this->templateDirectories) as $directory) {
				if(strpos(realpath($directory.$this->FileName), ISC_BASE_PATH) === 0 && file_exists($directory.$this->FileName)) {
					$filePath = realpath($directory.$this->FileName);
					break;
				}
			}

			if(!$filePath) {
				exit;
			}

			$this->Set('FileContent', file_get_contents($filePath));
			$this->FilePath = $filePath;

			// do all the hard-yakka in the parent class
			parent::EditFile();

			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($_REQUEST['File']);

			if(!array_key_exists('Ajax', $_REQUEST)) {
				echo $this->template->render('designmode.edit.tpl');
			}
		}
	}

  /**
   * ISC_ADMIN_DESIGNMODE::RevertFile()
   *
   * @return
   */
	protected function RevertFile()
	{
		// set the filename and content to the class variables
		$this->Set('FileName',$_REQUEST['File']);

		if(parent::RevertFile()) {
			// file reverted!
			$GLOBALS['FileRevertedOK'] = true;

			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($_REQUEST['File']);

			$this->EditFile();
		} else {
			// file didn't revert =(
			$GLOBALS['SavedOK'] = false;
			$this->EditFile();
		}
	}

  /**
   * ISC_ADMIN_DESIGNMODE::UpdateLayoutPanels()
   *
   * @return
   */
	protected function UpdateLayoutPanels()
	{
		$this->Set('FileName',    $_POST["dm_template"]);
		$this->Set('PanelString', $_POST["dm_panels"]);

		// Log this action
		$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($_POST['dm_template']);

		if (isset($_POST["dm_url"]) && isset($_POST["dm_template"]) && isset($_POST["dm_panels"])) {

			$ReturnURL = $_POST["dm_url"];

			if(parent::UpdateLayoutPanels()) {
				echo '<meta http-equiv="refresh" content="0;url='.$ReturnURL.'" /><script type="text/javascript">alert("' . GetLang('DesignModeChangesSaved') . '"); </script>';
				die();
			}
			else {
				$bad_file = str_replace(ISC_BASE_PATH, "", $this->GetError());
				$error = sprintf(GetLang('DesignModePermissionsError'), $bad_file);
				echo '<meta http-equiv="refresh" content="0;url='.$ReturnURL.'" /><script type="text/javascript">alert("' . $error . '"); </script>';
				die();
			}
		} else {
			$this->SetError('No Request Data.');
			return false;
		}
	}
}