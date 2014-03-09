<?php
if (!defined('ISC_BASE_PATH')) {
	die();
}

class ISC_ADMIN_REMOTE_DASHBOARD extends ISC_ADMIN_REMOTE_BASE
{
	public function HandleToDo()
	{
		$what = isc_strtolower(@$_REQUEST['w']);
		switch($what) {
			case 'loadrecentorders':
				$this->LoadRecentOrders();
				break;
			case 'getperformanceindicators':
				$this->GetPerformanceIndicators();
				break;
			case 'loadvideowalkthroughs':
				$this->GetVideoWalkthroughs();
		}
	}

	public function LoadRecentOrders()
	{
		echo GetClass('ISC_ADMIN_INDEX')->LoadRecentOrders();
	}

	public function GetPerformanceIndicators()
	{
		echo GetClass('ISC_ADMIN_INDEX')->GeneratePerformanceIndicatorsTable();
	}

	/**
	 * Generate the list of video walkthroughs.
	 */
	public function GetVideoWalkthroughs()
	{
		$expires = 86400; // 24 hr

		$cacheFile = ISC_BASE_PATH.'/cache/feeds/dashboard-videos.xml';
		if(file_exists($cacheFile) && filemtime($cacheFile) > time() - $expires) {
			$videoContents = file_get_contents($cacheFile);
			$modified = filemtime($cacheFile);
		}
		else {
			$videoContents = PostToRemoteFileAndGetResponse(GetConfig('VideoWalkthroughFeed'));
			if($videoContents) {
				@file_put_contents($cacheFile, $videoContents);
			}
			$modified = time();
		}

		if(!$videoContents) {
			exit;
		}

		$xml = @simplexml_load_string($videoContents);
		if(!is_object($xml)) {
			exit;
		}

		$output = '';
		$this->template->Assign('Width', (int)$xml->width);
		$this->template->Assign('Height', (int)$xml->height);
		foreach($xml->video as $video) {
			$this->template->Assign('Title', isc_html_escape($video->title));
			$this->template->Assign('URL', isc_html_escape($video->url));
			if($_SERVER['HTTPS'] == 'on') {
				$video->preview = str_replace('http://', 'https://', $video->preview);
			}
			$this->template->Assign('Preview', isc_html_escape($video->preview));
			$output .= $this->template->render('Snippets/DashboardVideoWalkthroughItem.html');
		}

		header("Last-Modified: " . gmdate("r", $modified));
		header("Pragma: public");
		header("Cache-control: public,maxage=" . $expires);
		header("Expires: " . gmdate("r", $modified + $expires));

		echo $output;
		exit;
	}
}