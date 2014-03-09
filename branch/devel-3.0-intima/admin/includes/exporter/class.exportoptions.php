<?php

class ISC_ADMIN_EXPORTOPTIONS {
	protected $_fileType;

	protected $_templateId;

	protected $_where;

	protected $_having;

	/**
	*
	* @param ISC_ADMIN_EXPORTFILETYPE $fileType
	* @return ISC_ADMIN_EXPORTOPTIONS
	*/
	public function setFileType(ISC_ADMIN_EXPORTFILETYPE $fileType)
	{
		$this->_fileType = $fileType;
		return $this;
	}

	/**
	*
	* @return ISC_ADMIN_EXPORTFILETYPE
	*/
	public function getFileType()
	{
		return $this->_fileType;
	}


	/**
	*
	* @return ISC_ADMIN_EXPORTOPTIONS
	*/
	public function setTemplateId($templateId)
	{
		$this->_templateId = $templateId;
		return $this;
	}

	public function getTemplateId()
	{
		return $this->_templateId;
	}

	/**
	* @return ISC_ADMIN_EXPORTOPTIONS
	*/
	public function setWhere($where)
	{
		$this->_where = $where;
		return $this;
	}

	public function getWhere()
	{
		return $this->_where;
	}

	/**
	* @return ISC_ADMIN_EXPORTOPTIONS
	*/
	public function setHaving($having)
	{
		$this->_having = $having;
		return $this;
	}

	public function getHaving()
	{
		return $this->_having;
	}
}
