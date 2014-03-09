var Ebay = {
	// validation when saving eBay configuration
	CheckManageEbayForm: function()
	{
		if ($('#ebaydevid').val() == "" &&
			$('#ebayappid').val() == "" &&
			$('#ebaycertid').val() == "" &&
			$('#ebayusertoken').val() == ""
			) {
			return true;
		}

		if ($('#ebaydevid').val() == "") {
			alert(lang.EbayEnterDevId);
			$('#ebaydevid').focus();
			return false;
		}
		if ($('#ebayappid').val() == "") {
			alert(lang.EbayEnterAppID);
			$('#ebayappid').focus();
			return false;
		}
		if ($('#ebaycertid').val() == "") {
			alert(lang.EbayEnterCertID);
			$('#ebaycertid').focus();
			return false;
		}
		if ($('#ebayusertoken').val() == "") {
			alert(lang.EbayEnterUserToken);
			$('#ebayusertoken').focus();
			return false;
		}
		if ($('#ebaydefaultsite').val() == "") {
			alert(lang.EbayEnterDefaultSiteId);
			$('#ebaydefaultsite').focus();
			return false;
		}
		return true;
	},

	// remote call for getting user eBay's store
	GetEbayStore: function()
	{
		$.ajax({
			url: 'remote.php',
			type: 'post',
			dataType: 'json',
			data: {
				remoteSection: 'ebay',
				w: 'GetEbayStore'
			},
			success: function(response) {
				if (response && response.success) {
					$('#ebaystorevalue').html(response.storeName);
					$('#ebaystore').val(response.storeName);
				}
				else {
					if (response.noStore) {
						if (confirm(response.message)) {
							window.open('http://pages.ebay.com/storefronts/start.html');
						}
					}
					else {
						alert(response.message);
					}
				}
			}
		});
	},

	// remote call for updating eBay cache
	StartAjaxEbayUpdate: function() {
		$.iModal({
			type: 'ajax',
			method: 'post',
			url: 'remote.php',
			urlData: {
				remoteSection: 'ebay',
				w: 'initEbayCacheUpdate'
			},
			close: false,
			width: 400
		});
	},

	EventsInit: function() {
		$("#deleteebaylivelisting").submit( function() {
			if (!EbayListing.ValidateForm()) {
				return false;
			}
			if ($("#ListingActionSelect").val() == "endlistingfromebay") {
				var itemIds = [];
				$('.ItemCheckBox:checked').each(function() {
					itemIds.push($(this).val());
				});
				Interspire_Ebay_EndListingMachine.start({selectedItemIds:itemIds});
				return false;
			} else if ($("#ListingActionSelect").val() == "removelistingref") {
				if ($('.ItemCheckBox:checked').length > 1) {
					var message =  lang.ConfirmRemoveListings;
				}
				else {
					var message =  lang.ConfirmRemoveListing;
				}

				if (!confirm(message)) {
					return false;
				}
			}
			return true;
		});
		$("#ebayLiveListingActionButton").click(function() {
			$("#deleteebaylivelisting").trigger('submit');
		});
		$(".removeListing").live('click', function() {
			if (confirm(lang.ConfirmRemoveListing)) {
				return true;
			}
			return false;
		});
		$(".cancelListing").live('click', function() {
			var itemIds = [];
			itemIds.push($(this).parents("tr").find(".ItemCheckBox").val());
			Interspire_Ebay_EndListingMachine.start({selectedItemIds:itemIds});
			return false;
		});
		$("#checkalllisting").click(function() {
			$('.ItemCheckBox').attr('checked', $(this).is(':checked'));
		});
		$("[name=CancelButton1]").click(function() {
			if(confirm(lang.ConfirmCancelEbaySettings)) {
				window.location = 'index.php?ToDo=viewEbay';
			}
		});
		$("#EbayTestMode").live('click', function() {
			if ($("#productionSite").is(":disabled")) {
				$("#EbayTestMode").val("sandbox");
				alert (lang.EbaySandboxOnly);
			}
		});
	}
}




var EbayListing = {
	ValidateForm: function()
	{
		if ($('#ListingActionSelect').val() == "") {
			alert (lang.ChooseActionFirst);
			$('#ListingActionSelect').focus();
			return false;
		}
		if($('.ItemCheckBox:checked').length < 1) {
			alert (lang.ChooseListing);
			return false;
		}
		return true;
	}
}

// determine which is the correct tab to be shown
function ShowTab(T)
{
	i = 0;
	while (document.getElementById("tab" + i) != null) {
		$('#div'+i).hide();
		$('#tab'+i).removeClass('active');
		++i;
	}

	$('#div'+T).show();
	$('#tab'+T).addClass('active');
	$('#currentTab').val(T);
	document.getElementById("currentTab").value = T;
}