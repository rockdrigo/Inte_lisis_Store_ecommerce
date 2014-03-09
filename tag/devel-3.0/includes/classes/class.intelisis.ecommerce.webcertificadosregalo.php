<?php

class ISC_INTELISIS_ECOMMERCE_WEBCERTIFICADOSREGALO extends ISC_INTELISIS_ECOMMERCE
{

	public function create() {
		$amounts = explode(',', $this->getData('Montos'));
		
		$s = GetClass('ISC_ADMIN_SETTINGS');
		$GLOBALS['ISC_NEW_CFG']['GiftCertificateAmounts'] = $amounts;
		$s->CommitSettings();
		return true;
	}
	
	public function update(){
		return $this->create();
	}
	
	public function delete() {
		return $this->create();
	}
}
