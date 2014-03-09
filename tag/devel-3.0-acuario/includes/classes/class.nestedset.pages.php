<?php

class ISC_NESTEDSET_PAGES extends ISC_NESTEDSET {

	public function __construct()
	{
		parent::__construct('pages', 'pageid', 'pageparentid', array('pagevendorid', 'pagesort', 'pagetitle'), 'pagensetleft', 'pagensetright', 'pagedepth');
	}
}
