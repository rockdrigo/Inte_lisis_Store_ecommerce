<?php

define('TRANS_STATUS_FAILED', 0);
define('TRANS_STATUS_NEW', 1);
define('TRANS_STATUS_CHARGED', 2);
define('TRANS_STATUS_CHARGEBACK', 3);
define('TRANS_STATUS_REFUND', 4);
define('TRANS_STATUS_PENDING', 5);
define('TRANS_STATUS_CANCELLED_REVERSAL', 6);
define('TRANS_STATUS_DENIED', 7);
define('TRANS_STATUS_COMPLETED', 8);
define('TRANS_STATUS_DECLINED', 9);

class ISC_TRANSACTION
{
	public $transaction = null;

	public function Create($transactionData)
	{
		if (!is_array($transactionData)) {
			return false;
		}

		if (!isset($transactionData['providerid'])) {
			return false;
		}

		if (!isset($transactionData['transactiondate'])) {
			return false;
		}

		if(isset($transactionData['extrainfo']) && is_array($transactionData['extrainfo'])) {
			$transactionData['extrainfo'] = serialize($transactionData['extrainfo']);
		}

		if(!is_array($transactionData['orderid'])) {
			$transactionData['orderid'] = array($transactionData['orderid']);
		}

		foreach($transactionData['orderid'] as $orderId) {
			$transactionInfo = $transactionData;
			$transactionInfo['orderid'] = $orderId;
			$GLOBALS['ISC_CLASS_DB']->InsertQuery('transactions', $transactionInfo);
		}

		return true;
	}

	public function LoadByTransactionId($transid, $providerId='')
	{
		$providerWhere = '';
		if($providerId) {
			$providerWhere .= " AND providerid='".$GLOBALS['ISC_CLASS_DB']->Quote($providerId)."'";
		}
		$query = "
			SELECT *
			FROM [|PREFIX|]transactions
			WHERE transactionid='".$GLOBALS['ISC_CLASS_DB']->Quote($transid)."' ".$providerWhere."
			AND orderid IS NOT NULL
			LIMIT 1
		";

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		if (!$result) {
			return false;
		}

		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		$this->transaction = $row;

		return $row;
	}

	public function Update($data, $where)
	{
		$result = $GLOBALS['ISC_CLASS_DB']->UpdateQuery('transactions', $data, $where);
		if ($result) {
			$this->transaction = array_mege($this->transaction, $data);
		}
		return $result;
	}

}