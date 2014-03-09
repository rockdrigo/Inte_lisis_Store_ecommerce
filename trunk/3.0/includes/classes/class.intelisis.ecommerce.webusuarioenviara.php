<?php

class ISC_INTELISIS_ECOMMERCE_WEBUSUARIOENVIARA extends ISC_INTELISIS_ECOMMERCE {
	function ProcessData() {
		$class = GetClass('ISC_INTELISIS_ECOMMERCE_WEBCTEENVIARA');
		$class->setXMLdom($this->getXMLdom());
		$class->setXMLfilename($this->getXMLfilename());
		return $class->ProcessData();
	}
}