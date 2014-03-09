var Optimizer = {
	LoadConfigForm: function(MoudleId)
	{
		if (!MoudleId) {
			return;
		}

		$.iModal({
			type: 'ajax',
			url: 'remote.php?remoteSection=optimizer&w=showConfigForm&moduleId='+MoudleId,
			width: 610

		});
	},

	SaveConfigForm: function(ModuleId)
	{
		if(!Optimizer.ValidateConfigForm()) {
			return false;
		}


		$.ajax({
			url: 'remote.php?remoteSection=optimizer&w=saveConfigForm&moduleId='+ModuleId,
			type: 'post',
			data: $('#OptimizerConfigForm').serialize(),
			dataType: 'xml',
			success: function(xml) {

				if($('status', xml).text() == '1') {
					$.modal.close();
					var msg = $('msg', xml).text();
					if(msg) {
						$("#MainMessage").html(msg);
					}
					
					$('.OptimizerTable .ConfiguredIcon_'+ModuleId+' img').attr('src', 'images/tick.gif');
					$('.OptimizerTable #OptimizerReset_'+ModuleId).attr('class', 'active');

					var ConfigDate = $('configDate', xml).text();
					if(ConfigDate) {
						$('.OptimizerTable #ConfigDate_'+ModuleId).html(ConfigDate);
					}

				} else {
					if($('msg', xml).text()) {
						alert($('msg', xml).text());
					}
				}
			},
			error: function(data) {
				alert(lang.ProblemRetrivingScript);
			}
		});

	},

	ValidateConfigForm: function(callBack, callBackValue)
	{
		var SelectedConversion = $("#ConversionPage").val();
		if(SelectedConversion == 'Custom') {
			var conversionUrl = $("#ConversionPageUrl").val();
			if(!conversionUrl.match('http://') && !conversionUrl.match('https://')) {
				if(callBack) {
					callBack(callBackValue);
				}
				alert(lang.EnterValidConversionUrl);
				$("#ConversionPageUrl").focus();
				$("#ConversionPageUrl").select();
				return false;
			}
		}

		if($("#ConversionPage").val() == '') {
			if(callBack) {
				callBack(callBackValue);
			}
			alert(lang.ChooseConvertionpage);
			$("#ConversionPage").focus();
			$("#ConversionPage").select();
			return false;
		}

		if($("#ControlScript").val() == '') {
			if(callBack) {
				callBack(callBackValue);
			}
			alert(lang.EnterControlScript);
			$("#ControlScript").focus();
			return false;
		}

		if($("#TrackingScript").val() == '') {
			if(callBack) {
				callBack(callBackValue);
			}
			alert(lang.EnterTrackingScript);
			$("#TrackingScript").focus();
			return false;
		}
		
		if($("#ConversionScript").val() == '') {
			if(callBack) {
				callBack(callBackValue);
			}
			alert(lang.EnterConversionScript);
			$("#ConversionScript").focus();
			return false;
		}


		return true;
	},

	ValidateAutoConfigForm: function()
	{
		if($("input[name='InstallUrl']").val() == '') {
			alert(lang.EnterInstallUrl);
			return false;
		}
		return true;
	},

	InstallAutoScripts: function()
	{
		
		if(!Optimizer.ValidateAutoConfigForm()) {
			return false;
		}
		
		var data = {
			'InstallUrl': $("input[name='InstallUrl']").val(),
			'w': 'installAutoScripts',
			'remoteSection': 'optimizer'
		};

		$.ajax({
			url: 'remote.php',
			type: 'post',
			data: data,
			dataType: 'xml',

			success: function(xml) {
				if($('status', xml).text() == '1') {
					var conversionScript = $('ConversionScript', xml).text();
					if(conversionScript) {
						$("#ConversionScript").val(conversionScript);
					}

					var controlScript = $('ControlScript', xml).text();
					if(controlScript) {
						$("#ControlScript").val(controlScript);
					}

					var trackingScript = $('TrackingScript', xml).text();
					if(trackingScript) {
						$("#TrackingScript").val(trackingScript);
					}
				} else {
					if($('msg', xml).text()) {
						alert($('msg', xml).text());
					}
				}
			},
			error: function(data) {
				alert(lang.ProblemRetrivingScript);
			}
		});
	},

	ChangeConversionPage: function(ModuleId)
	{
		var SelectedPage = $("#ConversionPage").val();
		if(SelectedPage == 'Custom') {

			$('#ConversionPageUrl').removeAttr("READONLY"); 
			$('#CustomConversionHelp').show();

		} else if(SelectedPage == '') {
			
			$("#ConversionPageUrl").attr("value", '');
			$('#ConversionPageUrl').removeAttr("READONLY");

		} else {
			$('#CustomConversionHelp').hide();
		
			/*var remoteData = {
				'conversionPage': SelectedPage,
				'w': 'getConversionPageUrl',
				'moduleId': ModuleId,
				'remoteSection': 'optimizer',
			}

			$.ajax({
				url: 'remote.php',
				type: 'post',
				data: remoteData,
				dataType: 'xml',

				success: function(xml) {
					if($('status', xml).text() == '1') {
						$("#ConversionPageUrl").attr("value", $('ConversionUrl', xml).text()); 
						$("#ConversionPageUrl").attr("READONLY", true);
					} else {
						if($('msg', xml).text()) {
							alert($('msg', xml).text());
						}
					}
				},
				error: function() {
					alert(lang.ProblemDuringRequest);
				}
			});
			*/
		}
	},



	ResetModule: function(ModuleId)
	{
		if(!confirm(lang.ConfirmResetOptimizer)) {
			return false;
		}
		if($('.OptimizerTable #OptimizerReset_'+ModuleId).attr('class')!='active') {
			return false;
		}
		$.ajax({

			url: 'remote.php?remoteSection=optimizer&w=resetModule&moduleId='+ModuleId,
			type: 'post',
			dataType: 'xml',
			data: '',
			success: function(xml) {
				if($('status', xml).text() == '1') {
					var msg = $('msg', xml).text();
					if(msg) {
						$("#MainMessage").html(msg);
					}
					
					$('.OptimizerTable .ConfiguredIcon_'+ModuleId+' img').attr('src', 'images/cross.gif');
					$('.OptimizerTable #OptimizerReset_'+ModuleId).attr('class', 'inactive');
					$('.OptimizerTable #ConfigDate_'+ModuleId).html('N/A');

				} else {

					if($('msg', xml).text()) {
						alert($('msg', xml).text());
					}
				}
			},
			error: function() {
				alert(lang.ResetModuleFail);
			}
		});
	}
};