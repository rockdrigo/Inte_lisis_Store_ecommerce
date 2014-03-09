<?php
require_once ISC_BASE_PATH."/lib/class.imagedir.php";
define("ITEMS_PER_PAGE", 10);

/**
 * This file contains the ISC_ADMIN_IMAGEMANAGER class ported from the IWP manager
 *
 * @version $Id$
 * @author Ray <ray.ward@interspire.com>
 *
 */

/**
 * Image Manager
 * This class is used to manage all the images within the /images/ directory of this website
 *
 */

class ISC_ADMIN_IMAGEMANAGER  extends ISC_ADMIN_BASE
{

	protected $imageDirectory = '';

	/**
	 * The constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->imageDirectory = GetConfig('ImageDirectory') . '/uploaded_images';
		$this->engine->LoadLangFile('imagemanager');
	}

	public function HandleToDo($do)
	{
		if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Images)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
		}

		$GLOBALS['BreadcrumEntries'] = array(
			GetLang('Home') => 'index.php',
		);

		switch (isc_strtolower($do)) {
			case 'downloadimage':
				$this->DownloadImage();
				break;
			default:
				$this->View();
		}


	}

	private function View()
	{
		$GLOBALS['BreadcrumEntries'][GetLang('ManageImages')] = 'index.php?ToDo=manageImages';

		// Display within the template
		$this->template->Assign('PageTitle', 'Manage Images');
		$this->template->Assign('PageIntro', 'ManageCatIntro');
		$this->template->Assign('CreateItem', 'CreateCategory');
		$this->template->Assign('DisplayFilters', 0);
		$this->template->Assign('MaxFileSize', GetMaxUploadSize());

		$currentPage = max((int)@$_GET['page'], 1);

		if(isset($_GET['perpage'])){
			$perPage = (int)$_GET['perpage'];
		}elseif(isset($_SESSION['imageManagerPagingPerPage']) && (int)$_SESSION['imageManagerPagingPerPage'] > 0){
			$perPage = (int)$_SESSION['imageManagerPagingPerPage'];
		}elseif(isset($_COOKIE['imageManagerPagingPerPage']) && (int)$_COOKIE['imageManagerPagingPerPage'] > 0){
			$perPage = (int)$_COOKIE['imageManagerPagingPerPage'];
		}else{
			$perPage = ITEMS_PER_PAGE;
		}

		$validSort = array("name.asc", "name.desc", "modified.asc", "modified.desc", "size.asc", "size.desc");
		$sortby = '';

		if(isset($_GET['sortby'])){
			$sortby = $_GET['sortby'];

		}elseif(isset($_SESSION['imageManagerSortBy'])){
			$sortby = $_SESSION['imageManagerSortBy'];
		}elseif(isset($_COOKIE['imageManagerSortBy'])){
			$sortby = $_COOKIE['imageManagerSortBy'];
		}

		if(empty($sortby) || !in_array($sortby, $validSort, true)){
			$sortby = 'name.asc';
		}

		setcookie('imageManagerSortBy', $sortby, time()+(60*60*24*365), '/');
		$_SESSION['imageManagerSortBy'] = $sortby;

		$sortBits = explode('.', $sortby);
		$sortField = $sortBits[0];
		$sortDirection = $sortBits[1];
		$this->template->Assign('Sort'.ucfirst(isc_strtolower($sortField)).ucfirst(isc_strtolower($sortDirection)), "selected=\"selected\"");

		setcookie('imageManagerPagingPerPage', $perPage, time()+(60*60*24*365), '/');
		$_SESSION['imageManagerPagingPerPage'] = $perPage;

		$imageDir = new ISC_IMAGEDIR($sortDirection, $sortField);
		$dirCount = $imageDir->CountDirItems();

		if($imageDir->CountDirItems() == 0){
			$this->template->Assign('hasImages', false);
		}else{
			$this->template->Assign('hasImages', true);
		}

		$imageDir->sortField = $sortField;
		$imageDir->sortDirection = $sortDirection;

		if ($perPage > 0) {
			$imageDir->start = ($perPage * $currentPage) - $perPage;
			$imageDir->finish = ($perPage * $currentPage);
		}

		$numPages = 1;
		if ($perPage == 0) {
			$this->template->Assign('PerPageAllSelected', "selected=\"selected\"");
		}
		else {
			$numPages = ceil($dirCount / $perPage);
			$this->template->Assign('paging', $this->GetNav($currentPage, $dirCount, $perPage));
			$this->template->Assign('PerPage'.$perPage.'Selected', "selected=\"selected\"");
		}

		$this->template->Assign('PageNumber', $currentPage);
		$this->template->Assign('sessionid', SID);
		// authentication checks the token stored in the cookie, however the flash uploader doesn't send cookies so we need to store the token in the session and then retrieve it
		$_SESSION['STORESUITE_CP_TOKEN'] = $_COOKIE['STORESUITE_CP_TOKEN'];

		if ($numPages > 1) {
			$this->template->Assign('ImagesTitle', sprintf(GetLang('imageManagerCurrentImages'), $imageDir->start+1, min($imageDir->finish, $dirCount), $dirCount));
		} else {
			$this->template->Assign('ImagesTitle', sprintf(GetLang('imageManagerCurrentImagesSingle'), $dirCount, $dirCount));
		}

		// generate list of images
		$images = $imageDir->GetImageDirFiles();
		$imagesList = "";
		foreach ($images as $image) {
			$image_name = isc_html_escape($image['name']);
			$image_size = isc_html_escape(Store_Number::niceSize($image['size']));

			$imagesList .= sprintf("AdminImageManager.AddImage('%s', '%s', '%s', '%s', '%s', '%s', '%s');\n",
				isc_html_escape($image['name']),
				isc_html_escape($image['url']),
				isc_html_escape(Store_Number::niceSize($image['size'])),
				$image['width'],
				$image['height'],
				$image['origheight'] . " x " . $image['origwidth'],
				$image['id']
			);
		}
		$this->template->Assign("imagesList", $imagesList);
		$this->template->Assign("sessionid", session_id());


		if (!empty($images)) {
			$this->template->Assign('hideHasNoImages', 'none');
		}
		else {
			$this->template->Assign('hideImages', 'none');
		}

		$this->engine->PrintHeader();
		$this->template->display('imgman.view.tpl');
		$this->engine->PrintFooter();
	}

	/**
	* Builds the pagination and navigation links
	*
	* @param int $page The current page we're on
	* @param int $total_items The total number of items to be paginated
	*/
	private function GetNav($page, $total_items, $items_per_page)
	{
		$searchURL = $this->GetSearchURL();

		$numPages = ceil($total_items / $items_per_page);

		// Add the "(Page x of n)" label
		if($total_items > $items_per_page) {
			$nav = sprintf("(%s %d %s %d) &nbsp;&nbsp;&nbsp;", GetLang('Page'), $page, GetLang('LittleOf'), $numPages);

			$nav .= BuildPagination($total_items, $items_per_page, $page, "index.php?ToDo=" . $_GET['ToDo'] . $searchURL);
		}
		else {
			$nav = "";
		}

		return rtrim($nav, ' |');
	}

	private function GetSearchURL($remove_sort = false)
	{
		// Build the pagination URL
		$searchURL = '';
		foreach($_GET as $k => $v) {
			if ($k == "ToDo" || $k == "page" || !$v) {
				continue;
			}
			if ($remove_sort && ($k == "sortField" || $k == "sortOrder")) {
				continue;
			}
			$searchURL .= sprintf("&%s=%s", $k, urlencode($v));
		}

		return $searchURL;
	}

	private function DownloadImage()
	{
		if (!isset($_GET['image'])) {
			die;
		}

		$imagefile = basename($_GET['image']);
		$imagepath = ISC_BASE_PATH . '/' . GetConfig('ImageDirectory') . '/uploaded_images/' . $imagefile;
		if (!file_exists($imagepath)) {
			die();
		}

		Interspire_Download::downloadFile($imagepath);
	}
}

