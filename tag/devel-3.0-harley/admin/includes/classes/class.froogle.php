<?php
require_once "class.ajaxexporter.php";

class ISC_ADMIN_FROOGLE extends ISC_ADMIN_AJAXEXPORTER
{
	public function __construct()
	{
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('froogle');

		$this->exportName = GetLang('FroogleFeed');
		$this->className = 'Froogle';
		$this->displayAutoExport = true;
		$this->exportIcon = 'froogle.gif';

		$GLOBALS['ExportName'] = GetLang('FroogleFeed');
		$GLOBALS['ExportIntro'] = GetLang('FroogleFeedIntro');
		$GLOBALS['ExportGenerate'] = GetLang('GenerateFroogleFeed');

		parent::__construct();
			}

	protected function GetResultCount()
	{
		$query = "
			SELECT COUNT(*) FROM (
				SELECT
					p.productid
				FROM
					[|PREFIX|]products p
					LEFT JOIN [|PREFIX|]categoryassociations ca ON (p.productid=ca.productid)
				WHERE
					p.prodvisible=1
					AND p.proddesc <> ''
				GROUP BY
					p.productid
			) as count
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$count = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

		return $count;
	}

	protected function WriteHeader()
	{
				$exportDate = isc_date("Y-m-d\TH:i:s\Z", time());
				$header = '<?xml version="1.0" encoding="' . GetConfig('CharacterSet') . '"?>
				<feed xmlns="http://www.w3.org/2005/Atom" xmlns:g="http://base.google.com/ns/1.0">
					<title>' . isc_html_escape($GLOBALS['StoreName']) . '</title>
					<link rel="self" href="'.str_replace("https://", "http://",$GLOBALS['ShopPath']).'"/>
					<updated>'.$exportDate.'</updated>
					<author>
						<name>' . isc_html_escape($GLOBALS['StoreName']) . '</name>
					</author>
					<id>tag:'.time().'</id>
				';

		fwrite($this->handle, $header);
	}

	protected function WriteFooter()
	{
		fwrite($this->handle, '</feed>');
	}

	protected function GetResult($generateFull = false, $start = 0)
	{
			$query = "
			SELECT
				p.*,
				c.catname,
					(SELECT b.brandname FROM [|PREFIX|]brands b WHERE b.brandid=p.prodbrandid) AS brandname,
				pi.*
			FROM
				[|PREFIX|]products p
				INNER JOIN [|PREFIX|]categoryassociations ca ON (p.productid = ca.productid)
				INNER JOIN [|PREFIX|]categories c ON (ca.categoryid = c.categoryid)
				LEFT JOIN [|PREFIX|]product_images pi ON (pi.imageprodid = p.productid AND pi.imageisthumb = 1)
			WHERE
				p.prodvisible=1
				AND p.proddesc <> ''
			GROUP BY
				p.productid
			";
		if (!$generateFull) {
			$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($start, ISC_AJAX_EXPORT_PER_PAGE);
			}
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		return $result;
	}

	public function WriteRow($row)
	{
		$expirationDate = isc_date("Y-m-d", strtotime('+29 days'));

		$link = ProdLink($row['prodname']);
		$desc = strip_tags($row['proddesc']);
		// Strip out invalid characters
		$desc = StripInvalidXMLChars($desc);

		if(isc_strlen($desc) > 1000) {
			$desc = isc_substr($desc, 0, 997)."...";
		}

		// Apply taxes to the price
		$price = getClass('ISC_TAX')->getPrice($row['prodcalculatedprice'], $row['tax_class_id'], getConfig('taxDefaultTaxDisplayProducts'));

		$entry = array(
			'title' => isc_html_escape($row['prodname']),
			'link' => isc_html_escape($link),
			'description' => isc_html_escape($desc),
			'g:department' => isc_html_escape($row['catname']),
			'g:expiration_date' => $expirationDate,
			'g:id' => $row['productid'],
			'g:condition' => isc_html_escape(isc_strtolower($row['prodcondition'])),
			'g:price' => $price
		);

		if($row['brandname']) {
			$entry['g:brand'] = isc_html_escape($row['brandname']);
		}

		if(!empty($row['imagefile'])) {
			try {
				$image = new ISC_PRODUCT_IMAGE();
				$image->populateFromDatabaseRow($row);
				$entry['g:image_link'] = isc_html_escape($image->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_ZOOM, true, true, false));
				}
			catch (Exception $ex) {
			}
		}

		if($row['prodcode']) {
			$entry['g:model_number'] = isc_html_escape($row['prodcode']);
		}

		if($row['prodweight'] > 0) {
			if(GetConfig('WeightMeasurement') == 'KGS') {
				$measure = 'kg';
			}
			else {
				$measure = strtolower(GetConfig('WeightMeasurement'));
			}
			$entry['g:weight'] = FormatWeight($row['prodweight'], false).' '.$measure;
		}

		$dimensions = array(
			'g:height' => 'prodheight',
			'g:length' => 'proddepth',
			'g:width' => 'prodwidth'
		);
		if(GetConfig('LengthMeasurement') == 'Centimeters') {
			$measure = 'cm';
		}
		else {
			$measure = strtolower(GetConfig('LengthMeasurement'));
		}

		foreach($dimensions as $google => $ours) {
			if($row[$ours] > 0) {
				$entry[$google] = $row[$ours].' '.$measure;
			}
		}

		// upc codes
		if(!empty($row['upc'])) {
			$entry['g:upc'] = StripInvalidXMLChars($row['upc']);
		}

		$xml = "<entry>\n";
		foreach($entry as $k => $v) {
			$xml .= "\t<".$k."><![CDATA[".$v."]]></".$k.">\n";
		}
		if(isset($row['prodfreeshipping']) && $row['prodfreeshipping'] != 1){
			$xml .= "</entry>\n";
		} else {
			$xml .= "\t<g:shipping><g:price><![CDATA[0]]></g:price></g:shipping>\n</entry>\n";
		}

		fwrite($this->handle, $xml);
	}
}