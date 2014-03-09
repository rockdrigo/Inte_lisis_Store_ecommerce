function sizeBox(resize) {
	var w = $('html').width();
	var h = $('html').height();

	$('#box').css('position', 'absolute');
	if(typeof(resize) == 'undefined') {
		var top = h/2-($('#box').height()/2);
		if (top < 0) {
			top = 0;
		}
		$('#box').css('top', top);
	}

	var left = w/2-($('#box').width()/2);
	if (left < 0) {
		left = 0;
	}

	$('#box').css('left', left);
}

function DBHelp(DBType) {
	if(DBType == "cpanel") {
		LaunchHelp(673);
	}
	else if(DBType == "plesk") {
		LaunchHelp(674);
	}
	else {
		// Other
		LaunchHelp(689);
	}
}

function checkInstallForm() {
	if(is_trial != 1) {
		if($('#LK').val() == '') {
			alert('Please enter your license key.');
			$('#LK').focus();
			return false;
		}
	}

	if($('#ShopPath').val() == '' || $('#ShopPath').val().indexOf('http://') != 0 ) {
		alert('Please enter your store\'s web site URL, starting with http://.');
		$('#ShopPath').focus();
		$('#ShopPath').select();
		return false;
	}

	if ($("#StoreCountryLocationId").val() < 1) {
		alert('Please select a country for your store.');
		$('#StoreCountryLocationId').focus();
		$('#StoreCountryLocationId').select();
		return false;
	}

	if (!(/^[a-z]{3}$/i).test($("#StoreCurrencyCode").val())) {
		alert('Please enter a valid currency code. The currency code consists of 3 letters only.');
		$('#StoreCurrencyCode').focus();
		$('#StoreCurrencyCode').select();
		return false;
	}

	if(is_trial == 1) {
		if($('#FullName').val() == '') {
			alert('Please enter your full name.');
			$('#FullName').focus();
			return false;
		}

		if($('#PhoneNumber').val() == '') {
			alert('Please enter your phone number.');
			$('#PhoneNumber').focus();
			return false;
		}
	}

	if($('#UserEmail').val().indexOf('@') == -1 || $('#UserEmail').val().indexOf('.') == -1 || $('#UserEmail').val().length <= 3) {
		alert('Please enter a valid email address.');
		$('#UserEmail').focus();
		$('#UserEmail').select();
		return false;
	}

	// client side password validation (install screen)
	var res = pmeter.validate($('#UserPass').val());
	if (res.valid == false) {
		alert(res.msg);
		$('#UserPass').focus();
		$('#UserPass').select();
		return false;
	}

	if($('#UserPass').val() != $('#UserPass1').val()) {
		alert('Your passwords do not match.');
		$('#UserPass1').focus();
		$('#UserPass1').select();
		return false;
	}

	$('#dbChoice1').click();

	if($('#dbUser').val() == '') {
		alert('Please enter your MySQL database username.');
		$('#dbUser').focus();
		return false;
	}

	if($('#dbServer').val() == '') {
		alert('Please enter your MySQL database hostname.');
		$('#dbServer').focus();
		return false;
	}

	if($('#dbDatabase').val() == '') {
		alert('Please enter your MySQL database name.');
		$('#dbDatabase').focus();
		return false;
	}

	return true;
}

$(document).ready(function() {
	sizeBox();
	$('.DBDetails').hide();
	$('.DBHelp').hide();

	$('#dbChoice1').click(function() {
		$('.DBDetails').show();
		$('.DBHelp').hide();
	});

	$('#dbChoice2').click(function() {
		$('.DBDetails').hide();
		$('.DBHelp').show();
	});

	$('#frmInstall').submit(function() {
		return checkInstallForm();
	});

	// Are there any permissions problems?
	if(typeof(critical_errors) != 'undefined' && critical_errors == '1') {
		tb_show('', '#TB_inline?height=300&width=450&inlineId=permissionsBox&modal=true', '');
	}
});

$(window).resize(function() {
	sizeBox(true);
});


