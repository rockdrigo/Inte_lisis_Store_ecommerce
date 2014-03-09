<?php

	require_once(ISC_BASE_PATH.'/lib/class.xml.php');

	class ISC_SEARCH
	{
		private $_pagetitle = "";
		private $_searchterms = array();
		private $_searchresults = array();
		private $_searchtypes = array("product", "category", "brand", "content", "page", "news");

		public function __construct($altData=null)
		{
			$this->_SetSearchData($altData);
		}

		public function HandlePage()
		{
			$action = "";
			if (isset($_REQUEST['action'])) {
				$action = isc_strtolower($_REQUEST['action']);
			}

			switch ($action) {
				case "tips":
					$this->ShowSearchTips();
					break;

				case "ajaxsearch":
					$this->DoAjaxSearch();
					break;

				case "tracksearchclick":
					$this->TrackSearchClick();
					break;

				default:
					$this->ShowSearchPage();
			}
		}

		/**
		*	Return the number of results from a search
		*/
		public function GetNumResults($section='')
		{
			if (!is_array($this->_searchresults) || empty($this->_searchresults)) {
				return 0;
			}

			if (!is_array($section)) {
				$section = array($section);
			}

			$section = array_map("trim", $section);
			$section = array_filter($section);

			$total = 0;
			foreach ($this->_searchresults as $type => $result) {
				if (!isset($result["total"])) {
					continue;
				}

				if (!empty($section) && !in_array($type, $section)) {
					continue;
				}

				$total += (int)$result["total"];
			}

			return $total;
		}

		/**
		*	Return the number of pages
		*/
		public function GetNumPages($section='')
		{
			if ((int)GetConfig('SearchResultsPerPage') == 0) {
				return 0;
			}

			$total = $this->GetNumResults($section);

			if ((int)$total == 0) {
				return 0;
			}

			return ceil($total / GetConfig('SearchResultsPerPage'));
		}

		/**
		*	Return the search query
		*/
		public function GetQuery()
		{
			return $this->_searchterms;
		}

		/**
		*	Return the search query
		*/
		public function GetResults($sections='')
		{
			if (!is_array($sections)) {
				$sections = array($sections);
			}

			$sections = array_map("trim", $sections);
			$sections = array_filter($sections);

			if (empty($sections)) {
				return $this->_searchresults;
			}

			$results = array();
			foreach ($this->_searchresults as $type => $result) {
				if (!in_array($type, $sections)) {
					continue;
				}

				$results[$type] = $result;
			}

			if (count($sections) == 1) {
				if (!array_key_exists($sections[0], $results)) {
					return array();
				}

				return $results[$sections[0]];
			}

			return $results;
		}

		public function _SetSearchData($altData=null)
		{
			if (!is_array($altData)) {
				$altData = $_REQUEST;
			}

			if (isset($altData['search_query_adv'])) {
				$altData['search_query'] = $altData['search_query_adv'];
			}
			if (isset($altData['search_query'])) {

				// Set the incoming search terms
				$this->_searchterms = BuildProductSearchTerms($altData);

				$GLOBALS['HideIfSearchQuery'] = 'display: none';
				$GLOBALS['OriginalSearchQuery'] = isc_html_escape($altData['search_query']);
				$GLOBALS['FormattedSearchQuery'] = isc_html_escape($this->_searchterms['search_query']);
				$GLOBALS['SearchTitle'] = sprintf(GetLang('SearchResultsFor'), $GLOBALS['OriginalSearchQuery']);
				$GLOBALS['SearchTitleShort'] = getLang('SearchResults');
				$this->_pagetitle = sprintf(GetLang('SearchSimpleTitle'), GetConfig('StoreName'), $GLOBALS['SearchTitle']);

			}
			else {
				// No search query set, show the advanced search form
				$GLOBALS['SearchTitle'] = sprintf(GetLang('SearchXStore'), $GLOBALS['StoreName']);
				$GLOBALS['SearchTitleShort'] = sprintf(GetLang('SearchXStore'), $GLOBALS['StoreName']);
				$GLOBALS['HideSearchBox'] = 'display: none';
				$this->_pagetitle = sprintf(GetLang('SearchAdvancedTitle'), GetConfig('StoreName'));

			}
		}

		public function searchIsLoaded()
		{
			if (!is_array($this->_searchterms) || empty($this->_searchterms)) {
				return false;
			}

			return true;
		}

		public function TrackSearchClick()
		{
			if (!isset($_GET['searchid'])) {
				exit;
			}

			// Update the search click to record a visit to a product
			$query = sprintf("update [|PREFIX|]searches_extended set clickthru='1' where searchid='%d'", (int)$_GET['searchid']);
			$GLOBALS['ISC_CLASS_DB']->Query($query);
			// this is loaded via a script tag, so, give it some valid js
			header("Content-Type: text/javascript");
			echo "/* 1 */";
			exit;
		}

		public function DoAjaxSearch()
		{
			if (isset($_GET['search_query']) && isc_strlen($_GET['search_query']) >= 3) {

				// Do a search on 'product' and 'content', sort them all by relevance and then get the first 5
				$this->DoSearch(0, 5, array("product", "page", "news"));
				$items = array();

				$totalSearchResults = 0;

				foreach (array("product", "page", "news") as $type) {
					$className = "ISC_" . isc_strtoupper($type);

					if (!method_exists($className, "buildSearchResultsAJAX")) {
						continue;
					}

					$returnSearch = call_user_func(array($className, "buildSearchResultsAJAX"));
					$totalSearchResults = ($totalSearchResults + $returnSearch[0]);
					$tmpItems = $returnSearch[1];

					// Merge the results back into our root array
					if (!is_array($tmpItems)) {
						continue;
					}

					foreach ($tmpItems as $key => $val) {
						if (!array_key_exists($key, $items)) {
							$key = (string)$key;
							$items[$key] = $val;
						} else {
							$items[$key] = array_merge($items[$key], $val);
						}
					}
				}

				// No results?
				if (empty($items)) {
					exit;
				}

				// Sort our results
				krsort($items);

				$xmlParser = new ISC_XML_PARSER();
				$tags = array();

				$counter = 0;
				$showViewMoreLink = false;

				if($totalSearchResults > 5) {
					$showViewMoreLink = true;
				}

				foreach ($items as $item) {
					if (!is_array($item) || empty($item)) {
						continue;
					}

					foreach ($item as $val) {
						if ($counter >= 5) {
							break 2;
						}

						$tags[] = $xmlParser->MakeXMLTag("result", $val, true);
						$counter++;
					}
				}

				if ($showViewMoreLink) {
					$tags[] = $xmlParser->MakeXMLTag("viewmoreurl", "<a href=\"" . $GLOBALS["ShopPathNormal"] . "/search.php?search_query=" . urlencode($_REQUEST["search_query"]) . "\">" . GetLang("QuickSearchViewAll") . " &raquo;</a>", true);
				}

				$xmlParser->SendXMLHeader();
				$xmlParser->SendXMLResponse($tags);
			}

			exit;
		}

		public function EscapeEntity($string)
		{
			return str_replace(array('&', '"', '<', '>'), array( '&amp;','&quot;', '&lt;', '&gt;'), $string);
		}

		/**
		*	Is $Word a word that's saved in the *_words table? If so we wont spell check it because it's
		*	part of a item's name and we can assume it's spelled correctly already
		*/
		public function IsSuggestWord($word)
		{
			if (trim($word) == "") {
				return false;
			}

			$word = isc_strtolower(trim($word));
			$wordTables = array("product", "category", "brand", "page", "news");

			foreach ($wordTables as $wordTable) {
				$query = "SELECT *
							FROM [|PREFIX|]" . $wordTable . "_words
							WHERE `word` = '" . $GLOBALS['ISC_CLASS_DB']->Quote($word) . "'";

				if ($GLOBALS["ISC_CLASS_DB"]->CountResult($query) > 0) {
					return true;
				}
			}

			return false;
		}

		/**
		*	If pSpell is installed the we can run a spell check and suggest on their search keywords
		*	if it's enabled from the settings page and pSpell is installed
		*/
		public function Suggest($sentence, &$has_suggestion, &$changed_words)
		{

			if (function_exists("pspell_new") && GetConfig('SearchSuggest')) {
				$spell = @pspell_new("en");
				if ($spell === false) {
					return $sentence;
				}
				$words = explode(" ", $sentence);
				$word_count = 0;
				$has_suggestion = false;

				foreach ($words as $word) {
					if (!pspell_check($spell, $word) && !$this->IsSuggestWord(isc_strtolower($word))) {
						$suggestions = pspell_suggest($spell, $word);

						if (!empty($suggestions) && isc_strtolower($suggestions[0]) != isc_strtolower($words[$word_count])) {
							// There was at least one suggestion
							$changed_words[] = array($words[$word_count], $suggestions[0]);
							$words[$word_count] = $suggestions[0];
							$has_suggestion = true;
						}
					}

					$word_count++;
				}

				return implode(" ", $words);
			}
			else {
				$has_suggestion = false;
				return $sentence;
			}
		}

		/**
		*	Run the full text searches to find matching products
		*/
		public function DoSearch($start=0, $limit=-1, $sections='', $sortBy='')
		{
			$total = 0;
			$searchTypes = $this->_searchtypes;

			if (!is_array($sections)) {
				$sections = array($sections);
			}

			$sections = array_map("trim", $sections);
			$sections = array_filter($sections);

			if (!empty($sections)) {
				$newSearchTypes = array();
				foreach ($sections as $section) {
					if (in_array($section, $searchTypes)) {
						$newSearchTypes[] = $section;
					}
				}

				if (!empty($newSearchTypes)) {
					$searchTypes = $newSearchTypes;
				}
			}

			if (!is_array($this->_searchresults)) {
				$this->_searchresults = array();
			}

			foreach ($searchTypes as $searchType) {

				$subtotal = 0;

				// Special case for 'content' which is basically 'page' and 'news' results merged into one
				if ($searchType == "content") {
					$pageQuery = ISC_PAGE::searchForItemsSQLAsContent($this->_searchterms);
					$newsQuery = ISC_NEWS::searchForItemsSQLAsContent($this->_searchterms);
					$itemResults = array();

					if (trim($pageQuery) !== "" && trim($newsQuery) !== "") {
						$query = "(" . trim($pageQuery) . ") UNION (" . trim($newsQuery) . ")";

						if (trim($sortBy) == "") {
							$sortBy = GetConfig("SearchDefaultContentSort");
						}

						$orderBy = "";

						switch (isc_strtolower($sortBy)) {
							case "relevance":
								$orderBy = "score DESC";
								break;

							case "alphaasc":
								$orderBy = "nodetitle ASC";
								break;

							case "alphadesc":
								$orderBy = "nodetitle DESC";
								break;
						}

						if (trim($orderBy) !== "") {
							$query .= " ORDER BY " . $orderBy;
						}

						if (is_numeric($limit) && $limit > 0) {
							if (is_numeric($start) && $start > 0) {
								$query .= " LIMIT " . (int)$start . "," . (int)$limit;
							} else {
								$query .= " LIMIT " . (int)$limit;
							}
						}

						$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
						$row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result);

						if ($row) {
							$subtotal = $GLOBALS["ISC_CLASS_DB"]->FetchOne("SELECT FOUND_ROWS()");
							$itemResults[] = $row;

							while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
								$itemResults[] = $row;
							}
						}
					}
				} else {
					// The brand class id called 'brands' (wtf)
					if ($searchType == "brand") {
						$className = "ISC_BRANDS";
					} else {
						$className = "ISC_" . isc_strtoupper($searchType);
					}

					$itemResults = call_user_func_array(array($className, "searchForItems"), array($this->_searchterms, &$subtotal, $start, $limit, $sortBy));
				}

				$this->_searchresults[$searchType] = array(
					"results" => $itemResults,
					"total" => $subtotal
				);

				$total += $subtotal;
			}

			return $total;
		}

		/**
		*	Use full text to find related searches in the searches table
		*/
		public function GetRelatedSearchTerms($Query)
		{

			$related_searches = array();

			$query = "select searchtext, ";
			$query .= $GLOBALS['ISC_CLASS_DB']->FullText(array("searchtext"), $Query, true) . " as score ";
			$query .= "from [|PREFIX|]searches where ";
			$query .= $GLOBALS['ISC_CLASS_DB']->FullText(array("searchtext"), $Query, true);
			$query .= sprintf(" and searchtext != '%s'", $GLOBALS['ISC_CLASS_DB']->Quote($Query));
			$query .= " order by score desc ";

			// Add the limit
			$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, 3);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				array_push($related_searches, $row['searchtext']);
			}

			return $related_searches;
		}

		/**
		*	Save a record of this search in the searches table,
		*	or update the numsearches field if it's not a new search
		*/
		public function LogSearch($Query, $NumResults=0)
		{

			// Has this query already been logged?
			$query = sprintf("select searchid from [|PREFIX|]searches where searchtext='%s'", $GLOBALS['ISC_CLASS_DB']->Quote($Query));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			if (!isset($row['searchid']) || $row['searchid'] == 0) {
				// A search isn't already logged for this query by the same person
				$SearchLog = array(
					"searchtext" => $Query,
					"numsearches" => 1
				);
				$GLOBALS['ISC_CLASS_DB']->InsertQuery("searches", $SearchLog);
			}
			else {
				// This search term is already logged, just update the numsearches field
				$query = sprintf("update [|PREFIX|]searches set numsearches=numsearches+1 where searchid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote((int) $row['searchid']));
				$GLOBALS['ISC_CLASS_DB']->Query($query);
			}

			// Log the seach to our actual search cache table
			$SearchCache = array(
				"searchtext" => isc_strtolower($Query),
				"numresults" => $NumResults,
				"searchdate" => time()
			);
			$searchid = $GLOBALS['ISC_CLASS_DB']->InsertQuery("searches_extended", $SearchCache);

			// Was this search a recommendation or correction click?
			$this->_CheckSearchCorrection($Query, $NumResults);
			return $searchid;
		}

		/**
		 * Checks if a search correction is being performed, if so, logs it
		 */
		public function _CheckSearchCorrection($Query, $NumResults)
		{
			$oldsearchid = '';
			if (isset($_GET['correction'])) {
				$oldsearchid = (int)$_GET['correction'];
				unset($_GET['correction']);
				$type = 'correction';
			}
			else if (isset($_GET['recommendation'])) {
				$oldsearchid = (int)$_GET['recommendation'];
				unset($_GET['recommendation']);
				$type = 'recommendation';
			}
			if ($oldsearchid > 0) {
				// Fetch the old search from the database to get its result count & search terms
				$query = sprintf("select * from [|PREFIX|]searches_extended where searchid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($oldsearchid));
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
				if (!$row) {
					return $oldsearchid;
				}

				$SearchCorrection = array(
					"correctiontype" => $type,
					"correction" => $Query,
					"numresults" => $NumResults,
					"oldsearchtext" => $row['searchtext'],
					"oldnumresults" => $row['numresults'],
					"correctdate" => time()
				);
				$GLOBALS['ISC_CLASS_DB']->InsertQuery("search_corrections", $SearchCorrection);
			}
		}

		/**
		 * Show the search tips page.
		 */
		public function ShowSearchTips()
		{
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetLang("SearchTips"));
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("search_tips");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}

		/**
		* Shows a page of results when requested by an ajax query
		*
		*/
		public function ShowAjaxSearchPage()
		{
			$section = isc_strtolower(trim(@$_REQUEST["section"]));
			if (!$section) {
				exit;
			}

			$GLOBALS["ISC_CLASS_SEARCH"] = GetClass("ISC_SEARCH");

			$searchTerms = $GLOBALS["ISC_CLASS_SEARCH"]->readSearchSession();

			$sortBy = trim(@$_REQUEST["sortBy"]);

			$page = (int)@$_REQUEST['page'];
			if ($page < 1) {
				$page = 1;
			}

			// Do the search
			$limit = (int)GetConfig("SearchResultsPerPage");

			$start = ($page - 1) * $limit;

			// We have all the details, now we need to load up the ISC_SEARCH class
			$GLOBALS["ISC_CLASS_SEARCH"]->_SetSearchData($searchTerms);
			$GLOBALS["ISC_CLASS_SEARCH"]->DoSearch($start, $limit, $section, $sortBy);

			$GLOBALS["SectionResults"] = "";

			if ($section == "content") {
				$searchResults = $GLOBALS["ISC_CLASS_SEARCH"]->GetResults("content");

				if (is_array($searchResults["results"]) && !empty($searchResults["results"])) {
					foreach ($searchResults["results"] as $item) {
						if ($item["nodetype"] == "page") {
							$GLOBALS["SectionResults"] .= ISC_PAGE::buildContentSearchResultHTML($item);
						} else {
							$GLOBALS["SectionResults"] .= ISC_NEWS::buildContentSearchResultHTML($item);
						}
					}
				}
			} else if ($section == "product") {
				$GLOBALS["SectionResults"] = ISC_PRODUCT::buildSearchResultsHTML();
			} else {
				exit;
			}

			$totalPages = $GLOBALS['ISC_CLASS_SEARCH']->GetNumPages($section);
			$totalRecords = $GLOBALS['ISC_CLASS_SEARCH']->GetNumResults($section);

			if (GetConfig("SearchProductDisplayMode") == "list") {
				$GLOBALS["SectionExtraClass"] = 'List';
			} else {
				$GLOBALS["SectionExtraClass"] = '';
			}

			$GLOBALS["SectionType"] = ucfirst($section) . 'List';

			$navTitle = GetLang("SearchResultsTab" . ucfirst($section));

			// generate url with all current GET params except page and ajax
			$url = array();
			foreach ($_GET as $key => $value) {
				if ($key == 'page' || $key == 'ajax') {
					continue;
				}
				if (is_array($value)) {
					foreach ($value as $subvalue) {
						$url[] = urlencode($key . '[]') . '=' . urlencode($subvalue);
					}
				} else {
					$url[] = urlencode($key) . '=' . urlencode($value);
				}
			}
			$url[] = "page={page}";
			$url = 'search.php?' . implode('&', $url) . '#results';

			$GLOBALS['SectionPaging'] = '';

			$maxPagingLinks = 5;
			if($GLOBALS['ISC_CLASS_TEMPLATE']->getIsMobileDevice()) {
				$maxPagingLinks = 3;
			}

			$start = max($page - $maxPagingLinks, 1);
			$end = min($page + $maxPagingLinks, $totalPages);

			for ($i = $start; $i <= $end; $i++) {
				if($i == $page) {
					$snippet = "CategoryPagingItemCurrent";
				}
				else {
					$snippet = "CategoryPagingItem";
				}

				$GLOBALS['PageLink'] = str_replace('{page}', $i, $url);
				$GLOBALS['PageNumber'] = $i;
				$GLOBALS['SectionPaging'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet($snippet);
			}

			// Parse the paging snippet
			if($page > 1) {
				$prevPage = $page - 1;
				$GLOBALS['PrevLink'] = str_replace('{page}', $prevPage, $url);
				$GLOBALS['SectionPagingPrevious'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPagingPrevious");
			}

			if($page < $totalPages) {
				$prevPage = $page + 1;
				$GLOBALS['NextLink'] = str_replace('{page}', $prevPage, $url);
				$GLOBALS['SectionPagingNext'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPagingNext");
			}

			if ($totalPages > 1) {
				$GLOBALS["HideSectionPaging"] = "";
			} else {
				$GLOBALS["HideSectionPaging"] = "none";
			}

			$GLOBALS["SectionSortingOptions"] = getAdvanceSearchSortOptions($section, $sortBy);
			print $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SearchResultGrid");
			exit;
		}

		/**
		*	Show the search page. If there are results, show them too. If we're in advanced mode then
		*	show the advanced search options as well.
		*/
		public function ShowSearchPage()
		{
			if (isset($_GET['ajax'])) {
				return $this->ShowAjaxSearchPage();
			}

			if (isset($_GET['category'])) {
				$selected_cats = $_GET['category'];
			} else {
				$selected_cats = 0;
			}

			if (isset($_GET['mode']) && $_GET['mode'] == "advanced") {
				$GLOBALS['HideAdvancedLink'] = "none";
			}

			$GLOBALS['CompareLink'] = CompareLink();

			$GLOBALS['ISC_CLASS_ADMIN_BRANDS'] = GetClass('ISC_ADMIN_BRANDS');
			$GLOBALS['BrandNameOptions'] = $GLOBALS['ISC_CLASS_ADMIN_BRANDS']->GetBrandsAsOptions(@$this->_searchterms['brand']);
			$GLOBALS['ISC_CLASS_ADMIN_CATEGORY'] = GetClass('ISC_ADMIN_CATEGORY');
			$GLOBALS['CategoryOptions'] = $GLOBALS['ISC_CLASS_ADMIN_CATEGORY']->GetCategoryOptions($selected_cats, "<option %s value='%d'>%s</option>", 'selected="selected"', "", false, 1);

			if ((isset($_GET['searchsubs']) && $_GET['searchsubs'] == "ON") || @$this->_searchterms['search_query'] == "") {
				$GLOBALS['IsSearchSubs'] = 'checked="checked"';
			}

			if (isset($this->_searchterms['price_from']) && is_numeric($this->_searchterms['price_from'])) {
				$GLOBALS['PriceFrom'] = $this->_searchterms['price_from'];
			}

			if (isset($this->_searchterms['price_to']) && is_numeric($this->_searchterms['price_to'])) {
				$GLOBALS['PriceTo'] = $this->_searchterms['price_to'];
			}

			if (isset($this->_searchterms['featured'])) {
				$GLOBALS["Featured" . $this->_searchterms['featured']] = 'selected="selected"';
			}

			if (isset($this->_searchterms['shipping'])) {
				$GLOBALS["Shipping" . $this->_searchterms['shipping']] = 'selected="selected"';
			}

			if (@$this->_searchterms['search_query'] != "") {

				$GLOBALS['SNIPPETS']['RelatedSearches'] = "";
				$has_suggestion = false;
				$changed_words = array();
				$query = $this->Suggest($this->_searchterms['search_query'], $has_suggestion, $changed_words);

				// Did pSpell make a suggestion?
				if ($query != $this->_searchterms['search_query']) {
					// pSpell made a suggestion
					$words = explode(" ", isc_html_escape($this->_searchterms['search_query']));

					foreach ($words as $k => $word) {
						foreach ($changed_words as $changed_word) {
							if ($word == $changed_word[0]) {
								$words[$k] = "<strong>" . $changed_word[1] . "</strong>";
							}
						}
					}

					$GLOBALS['SuggestQuery'] = implode(" ", $words);
					$GLOBALS['SuggestQueryEscaped'] = urlencode($query);
					$GLOBALS['ShowSearchSuggestion'] = "";
				}
				else {
					// No search suggestion
					$GLOBALS['ShowSearchSuggestion'] = "none";
				}

				$page = (int)@$_GET['page'];
				if ($page < 1) {
					$page = 1;
				}

				$limit = (int)GetConfig("SearchResultsPerPage");

				$start = ($page - 1) * $limit;

				// Load up our search results
				$this->DoSearch($start, $limit, array("product", "content"));
				$this->DoSearch(0, -1, array("category", "brand"));

				// Commit the search terms to the session array
				$this->commitSearchSession();

				// Log the search result
				$GLOBALS['SearchId'] = $this->LogSearch($this->_searchterms['search_query'], $this->GetNumResults());

				// Load up a list of related searches
				$related_searches = $this->GetRelatedSearchTerms($this->_searchterms['search_query']);

				if (!empty($related_searches)) {
					foreach ($related_searches as $related_search) {
						$GLOBALS['RelatedSearchQuery'] = urlencode($related_search);
						$GLOBALS['RelatedSearchText'] = isc_html_escape($related_search);
						$GLOBALS['SNIPPETS']['RelatedSearches'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("RelatedSearchItem");
					}

					$GLOBALS['SNIPPETS']['RelatedSearches'] = trim($GLOBALS['SNIPPETS']['RelatedSearches'], ", ");
				}
				else {
					$GLOBALS['HideRelatedSearches'] = "none";
				}
			}
			else {
				// Show the advanced mode box instead and hide everything else if there's no search term
				if (@$this->_searchterms['search_query'] == "") {
					$GLOBALS['HideRelatedSearches'] = "none";
					$GLOBALS['ShowSearchSuggestion'] = "none";
					$GLOBALS['HideSearchResults'] = "none";
					$GLOBALS['HideNoResults'] = "none";
				}
			}

			// Hide the search form if we have just performed a search
			if (!empty($this->_searchterms)) {
				$GLOBALS['AutoHideSearchForm'] = "true";
			}

			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle($this->_pagetitle);
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("search");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}

		/**
		 * Save the search term
		 *
		 * Method will save the search term to the session array
		 *
		 * @access private
		 * @param array $searchTerms The optional search terms. Default is $this->_searchterms
		 * @return void
		 */
		private function commitSearchSession($searchTerms=array())
		{
			// HACK: Changed from SESSION to GLOBALS to stop search terms being tied to session, but to allow search terms to be reachable across function scopes as they were when based on session This should force search terms to be built up from the URL each time without major (time-heavy) code changes. -ge
			if (!is_array($searchTerms) || empty($searchTerms)) {
				$searchTerms = $this->_searchterms;
			}

			$GLOBALS["CustomerSearchTerms"] = $searchTerms;
		}

		/**
		 * Read the saved search terms
		 *
		 * Method will return the search terms that were saved to the session
		 *
		 * @access public
		 * @return array The saved search terms
		 */
		public function readSearchSession()
		{
			// HACK: Changed from SESSION to GLOBALS to stop search terms being tied to session, but to allow search terms to be reachable across function scopes as they were when based on session. This should force search terms to be built up from the URL each time without major (time-heavy) code code changes. -ge
			if (!isset($GLOBALS["CustomerSearchTerms"])) {
				return array();
			}

			return $GLOBALS["CustomerSearchTerms"];
		}
	}
