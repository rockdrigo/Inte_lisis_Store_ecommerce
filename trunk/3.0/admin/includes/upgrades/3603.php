<?php
class ISC_ADMIN_UPGRADE_3603 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array(
		'redo_indonesian_states',
		'move_spool_job_files',
	);

	public function redo_indonesian_states()
	{
		$queries = array(
			"DELETE FROM [|PREFIX|]country_states WHERE statecountry='100'",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Bali', 100, 'BA');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Bangka Belitung', 100, 'BB');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Banten', 100, 'BT');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Bengkulu', 100, 'BE');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Daista Aceh', 100, 'DA');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Jakarta', 100, 'JK');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Sumatera Utara', 100, 'SU');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Sumatera Barat', 100, 'SB');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Riau', 100, 'SI');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Jambi', 100, 'JA');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Sumatera Selatan', 100, 'SS');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Lampung', 100, 'LA');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Jawa Barat', 100, 'JB');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Jawa Tengah', 100, 'JT');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Daista Yogyakarta', 100, 'DY');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Jawa Timur', 100, 'JT');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Kalimantan Barat', 100, 'KB');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Kalimantan Tengah', 100, 'KT');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Kalimantan Timur', 100, 'KI');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Kalimantan Selatan', 100, 'KS');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Nusa Tenggara Barat', 100, 'NB');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Nusa Tenggara Timur', 100, 'NT');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Sulawesi Selatan', 100, 'SN');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Sulawesi Tengah', 100, 'ST');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Sulawesi Utara', 100, 'SA');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Sulawesi Tenggara', 100, 'SG');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Maluku', 100, 'MA');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Maluku Utara', 100, 'MU');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Irian Jaya Timur', 100, 'IJ');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Irian Jaya Tengah', 100, 'IT');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Irian Jawa Barat', 100, 'IB');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Gorontalo', 100, 'GO');"
		);

		foreach($queries as $query) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function move_spool_job_files()
	{
		if (is_dir(ISC_BASE_PATH.'/cache/spool/quickbooks')) {

			$path = realpath(ISC_BASE_PATH.'/cache/spool/quickbooks');

			if (!($dir = dir($path))) {
				$this->SetError('Unable to open the directory "' . $path . '"');
				return false;
			}

			// Move all our existng spools files into the new path and format
			while (($file = $dir->read()) !== false) {
				$file = strtolower($file);
				if ($file == '.' || $file == '..' || substr($file, 0, 6) !== 'spool.') {
					continue;
				}

				$newfile = ISC_BASE_PATH.'/cache/spool/spool.quickbooks.' . substr($file, 6);

				rename($path . '/' . $file, $newfile);
			}

			//Ok, delete the quickbooks directory as it is not needed anymore
			rmdir($path);

			$dir->close();
			unset($dir);
		}

		return true;
	}
}