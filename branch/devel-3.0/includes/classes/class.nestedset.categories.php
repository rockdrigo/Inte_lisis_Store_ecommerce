<?php

class ISC_NESTEDSET_CATEGORIES extends ISC_NESTEDSET {

	public function __construct()
	{
		parent::__construct('categories', 'categoryid', 'catparentid', array('catsort', 'catname'), 'catnsetleft', 'catnsetright', 'catdepth');
	}
}
