<?php

	class ISC_NEWS
	{

		private $_newsid = 0;
		private $_newstitle = "";
		private $_newscontent = "";
		private $_newssearchkeywords = "";
		private $_newsdate = "";

		public function __construct()
		{
			$this->_SetPageData();
		}

		public function _SetPageData()
		{

			if (isset($_REQUEST['newsid'])) {
				$newsid = (int)$_REQUEST['newsid'];
			}
			else {
				$newsid = preg_replace('#\.html$#i', '', $GLOBALS['PathInfo'][1]);
				$newsid = $GLOBALS['ISC_CLASS_DB']->Quote(MakeURLNormal($newsid));
			}

			$query = sprintf("select * from [|PREFIX|]news where newsid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($newsid));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$this->_newsid = $row['newsid'];
				$this->_newstitle = $row['newstitle'];
				$this->_newscontent = $row['newscontent'];
				$this->_newssearchkeywords = $row['newssearchkeywords'];
				$this->_newsdate = $row['newsdate'];
			}
		}

		public function getNewsId()
		{
			return $this->_newsid;
		}

		public function HandlePage()
		{
			$this->ShowNews();
		}

		public function ShowNews()
		{
			if ($this->_newsid > 0) {
				$GLOBALS['NewsTitle'] = $this->_newstitle;
				$GLOBALS['NewsContent'] = $this->_newscontent;

				if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
					$GLOBALS['NewsContent'] = str_replace($GLOBALS['ShopPathNormal'], $GLOBALS['ShopPathSSL'], $GLOBALS['NewsContent']);
				}

				$GLOBALS['NewsDate'] = isc_date(GetConfig('ExtendedDisplayDateFormat'), $this->_newsdate);

				$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle($this->_newstitle);
				$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("news");
				$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
			}
			else {
				ob_end_clean();
				header("Location: " . $GLOBALS['ShopPath']);
				die();
			}
		}

		/**
		 * Get the search SQL
		 *
		 * Method will return the search SQL
		 *
		 * @access public
		 * @param array $searchQuery The search query array. Currently will only understand the 'search_query' option
		 * @param int $start The optional start position of the result total. Default is 0
		 * @param int $limit The optional limit position of the result total. Default is -1 (no limit)
		 * @param string $fieldsToUse the optional fields to select from. Default is * (all). Score is appended regardless
		 * @param bool $includeOrder TRUE to include the ORDER BY statement. Default is TRUE
		 * @return string The search SQL on success, FALSE on error
		 */
		static public function searchForItemsSQL($searchQuery, $start=0, $limit=-1, $fieldsToUse="", $includeOrder=true)
		{
			if (!is_array($searchQuery)) {
				return false;
			}

			if (!array_key_exists("search_query", $searchQuery) || trim($searchQuery["search_query"]) == "") {
				return false;
			}

			$fullTextFields = array("ns.newstitle", "ns.newscontent", "ns.newssearchkeywords");

			if (trim($fieldsToUse) == "") {
				$fieldsToUse = "SQL_CALC_FOUND_ROWS n.* ";
			}

			$fieldsToUse = trim($fieldsToUse);

			// Hard code in the score SQL
			if (substr($fieldsToUse, -1) !== ",") {
				$fieldsToUse .= ", ";
			}

			$fieldsToUse .= " (IF(n.newstitle='" . $GLOBALS["ISC_CLASS_DB"]->Quote($searchQuery["search_query"]) . "', 10000, 0) +
							   ((" . $GLOBALS["ISC_CLASS_DB"]->FullText(array("ns.newstitle"), $searchQuery["search_query"], false) . ") * 10) +
								 " . $GLOBALS["ISC_CLASS_DB"]->FullText($fullTextFields, $searchQuery["search_query"], false) . ") AS score";

			$query = "SELECT " . $fieldsToUse . "
						FROM [|PREFIX|]news n
							INNER JOIN [|PREFIX|]news_search ns ON n.newsid = ns.newsid
						WHERE n.newsvisible = 1";

			$searchPart = array();

			if (GetConfig("SearchOptimisation") == "fulltext" || GetConfig("SearchOptimisation") == "both") {
				$searchPart[] = $GLOBALS["ISC_CLASS_DB"]->FullText($fullTextFields, $searchQuery["search_query"], true);
			}

			if (GetConfig("SearchOptimisation") == "like" || GetConfig("SearchOptimisation") == "both") {
				$searchPart[] = "n.newstitle LIKE '%" . $GLOBALS["ISC_CLASS_DB"]->Quote($searchQuery["search_query"]) . "%'";
				$searchPart[] = "n.newssearchkeywords LIKE '%" . $GLOBALS["ISC_CLASS_DB"]->Quote($searchQuery["search_query"]) . "%'";
			}

			$query .= " AND (" . implode(" OR ", $searchPart) . ") ";

			if ($includeOrder) {
				$query .= " ORDER BY score DESC";
			}

			if (is_numeric($limit) && $limit > 0) {
				if (is_numeric($start) && $start > 0) {
					$query .= " LIMIT " . (int)$start . "," . (int)$limit;
				} else {
					$query .= " LIMIT " . (int)$limit;
				}
			}

			return $query;
		}

		/**
		 * Build the search SQL used in the 'content' search
		 *
		 * Method will build the SQL used in the 'content' search
		 *
		 * @access public
		 * @param array $searchQuery The search query array. Currently will only understand the 'search_query' option
		 * @param int $start The optional start position of the result total. Default is 0
		 * @param int $limit The optional limit position of the result total. Default is -1 (no limit)
		 * @param bool $isFirst TRUE to specify that this is the first SELECT in the UNION. Default is FALSE
		 * @return string The search SQL on success, FALSE on error
		 */
		static public function searchForItemsSQLAsContent($searchQuery, $start=0, $limit=-1, $isFirst=false)
		{
			$fields = "";

			if ($isFirst) {
				$fields .= " SQL_CALC_FOUND_ROWS ";
			}

			$fields .= "'news' AS nodetype, n.newsid AS nodeid, n.newstitle AS nodetitle, n.newscontent AS nodecontent,
						NULL AS nodelink, NULL AS nodepagetype, NULL AS nodevendorid, NULL AS nodevendorfriendlyname";

			return self::searchForItemsSQL($searchQuery, $start, $limit, $fields, false);
		}

		/**
		 * Search for news
		 *
		 * Method will search for all the news and return an array for news records
		 *
		 * @access public
		 * @param array $searchQuery The search query array. Currently will only understand the 'search_query' option
		 * @param int &$totalAmount The referenced variable to store in the total amount of the result
		 * @param int $start The optional start position of the result total. Default is 0
		 * @param int $limit The optional limit position of the result total. Default is -1 (no limit)
		 * @param string $fieldsToUse the optional fields to select from. Default is * (all). Score is appended regardless
		 * @return array The array result set on success, FALSE on error
		 */
		static public function searchForItems($searchQuery, &$totalAmount, $start=0, $limit=-1, $fieldsToUse="")
		{
			if (!is_array($searchQuery)) {
				return false;
			}

			$totalAmount = 0;
			$query = self::searchForItemsSQL($searchQuery, $start, $limit);

			if (trim($query) == "") {
				return array();
			}

			$news = array();
			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			$row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result);

			if (!$row) {
				return array();
			}

			$totalAmount = $GLOBALS["ISC_CLASS_DB"]->FetchOne("SELECT FOUND_ROWS()");
			$news[] = $row;

			while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
				$news[] = $row;
			}

			return $news;
		}

		/**
		 * Build the content searched item result HTML
		 *
		 * Method will build the content searched item result HMTL
		 *
		 * @access public
		 * @param array $news The content news search record array
		 * @return string The search item result HTML on success, empty string on error
		 */
		static public function buildContentSearchResultHTML($news)
		{
			if (!is_array($news)) {
				return "";
			}

			$map = array(
				"nodeid" => "newsid",
				"nodetitle" => "newstitle",
				"nodecontent" => "newscontent"
			);

			$remappedNews = array();

			foreach ($map as $fromKey => $toKey) {
				if (!array_key_exists($fromKey, $news)) {
					$remappedNews[$toKey] = "";
				} else {
					$remappedNews[$toKey] = $news[$fromKey];
				}
			}

			return self::buildSearchResultHTML($remappedNews);
		}

		/**
		 * Build the searched item results HTML
		 *
		 * Method will build the searched item results HMTL. Method will work with the ISC_SEARCH class to get the results
		 * so make sure that the object is initialised and the DoSearch executed.
		 *
		 * @access public
		 * @return string The search item result HTML on success, empty string on error
		 */
		static public function buildSearchResultsHTML()
		{
			if (!isset($GLOBALS["ISC_CLASS_SEARCH"]) || !is_object($GLOBALS["ISC_CLASS_SEARCH"])) {
				return "";
			}

			$totalRecords = $GLOBALS["ISC_CLASS_SEARCH"]->GetNumResults("news");

			if ($totalRecords == 0) {
				return "";
			}

			$results = $GLOBALS["ISC_CLASS_SEARCH"]->GetResults("news");
			$resultHTML = "";

			if (!array_key_exists("results", $results) || !is_array($results["results"])) {
				return "";
			}

			foreach ($results["results"] as $news) {
				$resultHTML .= self::buildSearchResultHTML($news);
			}

			$resultHTML = trim($resultHTML);
			return $resultHTML;
		}

		/**
		 * Build the searched item result HTML
		 *
		 * Method will build the searched item result HMTL
		 *
		 * @access public
		 * @param array $news The news search record array
		 * @return string The search item result HTML on success, empty string on error
		 */
		static public function buildSearchResultHTML($news)
		{
			if (!is_array($news) || !array_key_exists("newsid", $news)) {
				return "";
			}

			$normalContent = strip_tags($news["newscontent"]);
			$smallContent = substr($normalContent, 0, 199);

			if (strlen($normalContent) > 200 && substr($smallContent, -1, 1) !== ".") {
				$smallContent .= " ...";
			}

			$GLOBALS["NewsTitle"] = isc_html_escape($news["newstitle"]);
			$GLOBALS["NewsSmallContent"] = $smallContent;
			$GLOBALS["NewsURL"] = BlogLink($news["newsid"], $news["newstitle"]);

			return $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SearchResultNews");
		}

		/**
		 * Build the array of searched item results for the AJAX request
		 *
		 * Method will build an array of searched item results for the AJAX request. Method will work with the ISC_SEARCH
		 * class to get the results so make sure that the object is initialised and the DoSearch executed.
		 *
		 * Each key in the array will be the 'score' value (as a string) so it can be merged in with other results and can
		 * then be further sorted using any PHP array sorting functions, so output would be something like this:
		 *
		 * EG: return = array(10, // result count
		 *                    array(
		 *                        "12.345" => array(
		 *                                          0 => [page HTML]
		 *                                          1 => [page HTML]
		 *                                          2 => [page HTML]
		 *                                    ),
		 *                        "2.784" => array(
		 *                                          0 => [page HTML]
		 *                                    ),
		 *                        "6.242" => array(
		 *                                          0 => [page HTML]
		 *                                          1 => [page HTML]
		 *                                   )
		 *                    )
		 *              );
		 *
		 * @access public
		 * @return array An array with two values, first is total number of search results. Other is the search item results AJAX array on success, empty array on error
		 */
		static public function buildSearchResultsAJAX()
		{
			if (!isset($GLOBALS["ISC_CLASS_SEARCH"]) || !is_object($GLOBALS["ISC_CLASS_SEARCH"])) {
				return array(0, array());
			}

			$totalRecords = $GLOBALS["ISC_CLASS_SEARCH"]->GetNumResults("news");

			if ($totalRecords == 0) {
				return array(0, array());
			}

			$results = $GLOBALS["ISC_CLASS_SEARCH"]->GetResults("news");
			$ajaxArray = array();

			if (!array_key_exists("results", $results) || !is_array($results["results"])) {
				return array();
			}

			foreach ($results["results"] as $news) {
				if (!isset($news["score"])) {
					$news["score"] = 0;
				}

				$normalContent = strip_tags($news["newscontent"]);
				$smallContent = substr($normalContent, 0, 49);

				if (strlen($normalContent) > 50 && substr($smallContent, -1, 1) !== ".") {
					$smallContent .= " ...";
				}

				$GLOBALS["NewsTitle"] = isc_html_escape($news["newstitle"]);
				$GLOBALS["NewsURL"] = BlogLink($news["newsid"], $news["newstitle"]);
				$GLOBALS["NewsSmallContent"] = isc_html_escape($smallContent);

				$sortKey = (string)$news["score"];

				$ajaxArray[$sortKey][] = $GLOBALS["ISC_CLASS_TEMPLATE"]->GetSnippet("SearchResultAJAXNews");
			}

			return array($totalRecords, $ajaxArray);
		}
	}