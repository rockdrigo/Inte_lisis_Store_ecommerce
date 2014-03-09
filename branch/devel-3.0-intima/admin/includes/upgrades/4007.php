<?php
class ISC_ADMIN_UPGRADE_4007 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array (
		'add_uk_states',
		'add_isle_of_man'
	);

	public function add_uk_states()
	{
		$counties = array(
			'Bedfordshire',
			'Berkshire',
			'Buckinghamshire',
			'Cambridgeshire',
			'Cheshire',
			'Cornwall',
			'Cumberland',
			'Cumbria',
			'Derbyshire',
			'Devon',
			'Dorset',
			'Durham',
			'East Suffolk',
			'East Sussex',
			'Essex',
			'Gloucestershire',
			'Greater London',
			'Greater Manchester',
			'Hampshire',
			'Herefordshire',
			'Hertfordshire',
			'Isle of Wight',
			'Kent',
			'Lancashire',
			'Leicestershire',
			'Lincolnshire',
			'London',
			'Merseyside',
			'Middlesex',
			'Norfolk',
			'Northamptonshire',
			'Northumberland',
			'North Humberside',
			'North Yorkshire',
			'Nottinghamshire',
			'Oxfordshire',
			'Rutland',
			'Shropshire',
			'Somerset',
			'South Humberside',
			'South Yorkshire',
			'Staffordshire',
			'Suffolk',
			'Surrey',
			'Sussex',
			'Tyne and Wear',
			'Warwickshire',
			'West Midlands',
			'Westmorland',
			'West Suffolk',
			'West Sussex',
			'West Yorkshire',
			'Wiltshire',
			'Worcestershire',
			'Yorkshire',
			'Yorkshire, East Riding',
			'Yorkshire, North Riding',
			'Yorkshire, West Riding',
			'Antrim',
			'Armagh',
			'City of Belfast',
			'Down',
			'Fermanagh',
			'Londonderry',
			'City of Londonderry',
			'Tyrone',
			'City of Aberdeen',
			'Aberdeenshire',
			'Angus',
			'Argyll',
			'Ayrshire',
			'Banffshire',
			'Berwickshire',
			'Bute',
			'Caithness',
			'Clackmannanshire',
			'Cromartyshire',
			'Dumfriesshire',
			'Dunbartonshire',
			'City of Dundee',
			'East Lothian',
			'City of Edinburgh',
			'Fife',
			'City of Glasgow',
			'Inverness-shire',
			'Kincardineshire',
			'Kinross-shire',
			'Kirkcudbrightshire',
			'Lanarkshire',
			'Midlothian',
			'Moray',
			'Nairnshire',
			'Orkney',
			'Peeblesshire',
			'Perthshire',
			'Renfrewshire',
			'Ross and Cromarty',
			'Ross-shire',
			'Roxburghshire',
			'Selkirkshire',
			'Shetland',
			'Stirlingshire',
			'Sutherland',
			'West Lothian',
			'Wigtownshire',
			'Anglesey',
			'Brecknockshire',
			'Caernarfonshire',
			'Cardiganshire',
			'Carmarthenshire',
			'Clwyd',
			'Denbighshire',
			'Dyfed',
			'Flintshire',
			'Glamorgan',
			'Gwent',
			'Gwynedd',
			'Merionethshire',
			'Mid Glamorgan',
			'Monmouthshire',
			'Montgomeryshire',
			'Pembrokeshire',
			'Powys',
			'Radnorshire',
			'South Glamorgan',
			'West Glamorgan'
		);

		foreach($counties as $county) {
			$query = "SELECT * FROM [|PREFIX|]country_states WHERE statename='" . $county . "' AND statecountry = 225";
			$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
			if (!$GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
				$insert = array(
					'statename'		=> $county,
					'statecountry'	=> '225',
					'stateabbrv'	=> ''
				);

				$GLOBALS['ISC_CLASS_DB']->InsertQuery('country_states', $insert);
			}
		}
		return true;
	}

	public function add_isle_of_man()
	{
		$query = "SELECT * FROM [|PREFIX|]countries WHERE countryname = 'Isle of Man'";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (!$GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
			$insert = array(
					'countryname'		=> 'Isle of Man',
					'countryiso2'		=> 'IM',
					'countryiso3'		=> 'IMN'
			);

			$GLOBALS['ISC_CLASS_DB']->InsertQuery('countries', $insert);
		}

		return true;
	}
}