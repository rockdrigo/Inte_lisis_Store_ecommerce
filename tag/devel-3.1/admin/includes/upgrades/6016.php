<?php

/**
 * Upgrade class for 6.0.16
 * This class runs a series of methods used to upgrade the store to a specific version
 *
 * @package ISC
 * @subpackage ISC_Upgrade
 */
class ISC_ADMIN_UPGRADE_6016 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array(
		'updateFormfieldsImmutable',
		'autoSaltCustomerPassword',
		'autoSaltUserPassword',
	);

	public function updateFormfieldsImmutable()
	{
		// ISC-1497
		$query = '
			UPDATE
				`[|PREFIX|]formfields`
			SET
				`formfieldisimmutable` = 2
			WHERE
				`formfieldprivateid`  IN ("CompanyName", "AddressLine2")';
		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function autoSaltCustomerPassword()
	{
		$query = '
			SELECT
				customerid, salt, custpassword
			FROM
				`[|PREFIX|]customers`
			WHERE
				salt = ""
		';
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		$total = $GLOBALS['ISC_CLASS_DB']->countResult($result);
		$count = 0;
		while ($customer = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			// auto salt md5-ed customer password with a 15 len salt
			$updatedCustomer = $customer;
			$updatedCustomer['salt'] = substr(md5(uniqid()), 0, 15);
			$updatedCustomer['custpassword'] = getClass('ISC_ENTITY_CUSTOMER')->generatePasswordHash($customer['custpassword'], $updatedCustomer['salt']);
			$GLOBALS['ISC_CLASS_DB']->updateQuery('customers', $updatedCustomer, "customerid='".$GLOBALS['ISC_CLASS_DB']->quote($customer['customerid'])."'");
			$count++;
		}

		echo "\tAuto-salted password for $count/$total customer(s)\n";
		if ($count == $total) {
			return true;
		}

		return false;
	}

	public function autoSaltUserPassword()
	{
		$query = '
			SELECT
				pk_userid, salt, userpass
			FROM
				`[|PREFIX|]users`
			WHERE
				salt = ""
		';
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		$total = $GLOBALS['ISC_CLASS_DB']->countResult($result);
		$count = 0;
		while ($user = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			// auto salt md5-ed user password with a 15 len salt
			$updatedUser = $user;
			$updatedUser['salt'] = substr(md5(uniqid()), 0, 15);
			$updatedUser['userpass'] = getClass('ISC_ADMIN_USER')->generatePasswordHash($user['userpass'], $updatedUser['salt']);
			$GLOBALS['ISC_CLASS_DB']->updateQuery('users', $updatedUser, "pk_userid='".$GLOBALS['ISC_CLASS_DB']->quote($user['pk_userid'])."'");
			$count++;
		}

		echo "\tAuto-salted password for $count/$total user(s)\n";
		if ($count == $total) {
			return true;
		}

		return false;
	}
}
