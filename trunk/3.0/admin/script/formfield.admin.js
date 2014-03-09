
var FormFieldAdmin = {
	'GetExtraInfoData':
		function()
		{
			var data = {};

			$('.extraInfo').each(
				function()
				{
					var name = $(this).attr('name');
					var val = $(this).val();
					data[name] = val;
				}
			);

			switch ($('#FormFieldType').val().toLowerCase()) {
				case 'singleselect':
				case 'radioselect':
				case 'checkboxselect':
					data['options'] = FormFieldAdmin.GetOptions();
					break;

				case 'datechooser':
					var defaultValue = FormFieldAdmin.GetDate('DefaultValue');
					var limitFrom = FormFieldAdmin.GetDate('LimitFrom');
					var limitTo = FormFieldAdmin.GetDate('LimitTo');

					if (defaultValue) {
						data['defaultvalue'] = FormFieldAdmin.ParseDate(defaultValue);
					}

					if (limitFrom) {
						data['limitfrom'] = FormFieldAdmin.ParseDate(limitFrom);
					}

					if (limitTo) {
						data['limitto'] = FormFieldAdmin.ParseDate(limitTo);
					}

					break;
			}

			return data;
		},

	'GetSaveData':
		function()
		{
			var data = {
				'fieldId': $('#FormFieldID').val(),
				'formId': $('#FormFieldFormID').val(),
				'fieldType': $('#FormFieldType').val(),
				'name': $('#FormFieldName').val()
			}

			for (var i in data) {
				if (typeof(data[i]) == 'undefined') {
					data[i] = '';
				}
			}

			if ($('#FormFieldIsRequired').attr('checked')) {
				data['isRequired'] = '1';
			} else {
				data['isRequired'] = '0';
			}

			/**
			 * Apply the extra info as an array
			 */
			var extraInfo = FormFieldAdmin.GetExtraInfoData();

			for (var i in extraInfo) {
				if (typeof(extraInfo[i]) == 'object') {
					delete data['extraInfo['+i+']'];

					for (var j=0; j<extraInfo[i].length; j++) {
						data['extraInfo['+i+']['+j+']'] = extraInfo[i][j];
					}
				} else {
					data['extraInfo['+i+']'] = extraInfo[i];
				}
			}

			return data;
		},

	'Save':
		function()
		{
			if (!FormFieldAdmin.Validate()) {
				return false;
			}

			$('.ModalButtonRow .CloseButton').hide();
			$('.ModalButtonRow .LoadingIndicator').show();
			$('.ModalButtonRow .Submit')
				.data('oldValue', $('.ModalButtonRow .Submit').val())
				.attr('disabled', true)
				.val(lang.FormFieldSetupSaving)
			;
			$.ajax({
				'type': 'post',
				'url': 'remote.php?remoteSection=formfields&w=saveFieldSetup',
				'data': FormFieldAdmin.GetSaveData(),

				'success':
					function(xml)
					{
						$.modal.close();
						if($('msg', xml).text()) {
							if($('status', xml).text() == '1') {
								display_success('FormFieldStatus', $('msg', xml).text());
							} else {
								display_error('FormFieldStatus', $('msg', xml).text());
							}
						}
					},

				'error':
					function()
					{
						$('.ModalButtonRow .CloseButton').show();
						$('.ModalButtonRow .LoadingIndicator').hide();
						$('.ModalButtonRow .Submit')
							.attr('disabled', false)
							.val($('.ModalButtonRow .Submit').val())
						;
						alert($('msg', xml).text());
					}
			});
		},

	'Validate':
		function()
		{
			if ($('#FormFieldName').val() == '') {
				FormFieldAdmin.SetError('FormFieldName', lang.FormFieldSetupInvalidName);
				return false;
			}

			switch ($('#FormFieldType').val().toLowerCase()) {
				case 'selectortext':
				case 'singleselect':
					if ($('#FormFieldChoosePrefix').val() == '') {
						FormFieldAdmin.SetError('FormFieldChoosePrefix', lang.FormFieldSetupInvalidChoosePrefix);
						return false;
					}

					if ($('#FormFieldType').val().toLowerCase() == 'selectortext' || $('#FormFieldOptions').attr('readonly')) {
						return true;
					}

				case 'radioselect':
				case 'checkboxselect':
					var options = FormFieldAdmin.GetOptions();
					if (!options || options.length == 0) {
						FormFieldAdmin.SetError('FormFieldOptions', lang.FormFieldSetupInvalidSelectOptions)
						return false;
					}

					break;

				case 'datechooser':
					var defaultValue = FormFieldAdmin.GetDate('DefaultValue');
					var limitFrom = FormFieldAdmin.GetDate('LimitFrom');
					var limitTo = FormFieldAdmin.GetDate('LimitTo');

					if (limitFrom !== false || limitTo !== false) {
						if (!limitFrom || !FormFieldAdmin.CheckDate(limitFrom)) {
							FormFieldAdmin.SetError('FormFieldLimitFromMonth', lang.FormFieldSetupInvalidDateLimitFrom);
							return false;
						}

						if (!limitTo || !FormFieldAdmin.CheckDate(limitTo)) {
							FormFieldAdmin.SetError('FormFieldLimitToMonth', lang.FormFieldSetupInvalidDateLimitTo);
							return false;
						}

						var limitFromDate = new Date(limitFrom.year, limitFrom.month, limitFrom.day);
						var limitToDate = new Date(limitTo.year, limitTo.month, limitTo.day);

						if (limitFromDate.getTime() > limitToDate.getTime()) {
							FormFieldAdmin.SetError('FormFieldLimitFromMonth', lang.FormFieldSetupInvalidDateLimitRange);
							return false;
						}
					}

					if (defaultValue !== false) {
						if (!FormFieldAdmin.CheckDate(defaultValue)) {
							FormFieldAdmin.SetError('FormFieldDefaultValueMonth', lang.FormFieldSetupInvalidDateDefaultValue);
							return false;
						}

						if (limitFrom !== false || limitTo !== false) {
							var defaultValueDate = new Date(defaultValue.year, defaultValue.month, defaultValue.day);

							if (defaultValueDate.getTime() < limitFromDate.getTime() || defaultValueDate.getTime() > limitToDate.getTime()) {
								FormFieldAdmin.SetError('FormFieldDefaultValueMonth', lang.FormFieldSetupInvalidDateDefaultRange);
								return false;
							}
						}
					}
					break;

				case 'multiline':
					if ($('#FormFieldCols').val() !== '' && (isNaN($('#FormFieldCols').val()) || $('#FormFieldCols').val() < 0)) {
						FormFieldAdmin.SetError('FormFieldCols', lang.FormFieldSetupInvalidSize, 1);
						return false;
					}

					if ($('#FormFieldRows').val() == '' || isNaN($('#FormFieldRows').val()) || $('#FormFieldRows').val() <= 0) {
						FormFieldAdmin.SetError('FormFieldRows', lang.FormFieldSetupInvalidRows);
						return false;
					}

					break;

				case 'numberonly':
					if ($('#FormFieldDefaultValue').val() !== '' && isNaN($('#FormFieldDefaultValue').val())) {
						FormFieldAdmin.SetError('FormFieldDefaultValue', lang.FormFieldSetupInvalidNumberDefaultValue);
						return false;
					}

					if ($('#FormFieldLimitFrom').val() !== '' && isNaN($('#FormFieldLimitFrom').val())) {
						FormFieldAdmin.SetError('FormFieldLimitFrom', lang.FormFieldSetupInvalidLimitForm);
						return false;
					}

					if ($('#FormFieldLimitTo').val() !== '' && isNaN($('#FormFieldLimitTo').val())) {
						FormFieldAdmin.SetError('FormFieldLimitTo', lang.FormFieldSetupInvalidLimitTo);
						return false;
					}

					if ($('#FormFieldLimitFrom').val() !== '' && $('#FormFieldLimitTo').val() !== '') {
						if (parseInt($('#FormFieldLimitFrom').val()) >= parseInt($('#FormFieldLimitTo').val())) {
							FormFieldAdmin.SetError('FormFieldLimitFrom', lang.FormFieldSetupInvalidLimitRange);
							return false;
						}

						if ($('#FormFieldDefaultValue').val() !== '') {
							if (parseInt($('#FormFieldDefaultValue').val()) < parseInt($('#FormFieldLimitFrom').val()) || parseInt($('#FormFieldDefaultValue').val()) > parseInt($('#FormFieldLimitTo').val())) {
								FormFieldAdmin.SetError('FormFieldDefaultValue', lang.FormFieldSetupInvalidLimitDefault);
								return false;
							}
						}
					}

				default:
					if ($('#FormFieldSize').val() !== '' && (isNaN($('#FormFieldSize').val()) || $('#FormFieldSize').val() < 0)) {
						FormFieldAdmin.SetError('FormFieldSize', lang.FormFieldSetupInvalidSize, 1);
						return false;
					}

					if ($('#FormFieldMaxLength').val() !== '' && (isNaN($('#FormFieldMaxLength').val()) || $('#FormFieldMaxLength').val() < 0)) {
						FormFieldAdmin.SetError('FormFieldMaxLength', lang.FormFieldSetupInvalidMaxLength, 1);
						return false;
					}
			}

			return true;
		},

	'SetError':
		function(id, msg, tab)
		{
			if (id == '' || msg == '') {
				return;
			}

			if (typeof(tab) == 'undefined') {
				tab = 0;
			}

			alert(msg);
			ShowTab(tab);
			$('#' + id).focus().select();
		},

	'GetOptions':
		function()
		{
			var options = $('#FormFieldOptions').val().split('\n');
			var filtered = [];

			for (var i=0; i<options.length; i++) {
				if (options[i].replace(/\ /, '') == '') {
					continue;
				}

				filtered[filtered.length] = options[i];
			}

			return filtered;
		},

	'SortOptions':
		function()
		{
			var options = FormFieldAdmin.GetOptions();

			if (options.length < 2) {
				FormFieldAdmin.SetError('FormFieldOptions', lang.FormFieldSetupInvalidSelectOptionsSort);
				return;
			}

			options.sort();

			$('#FormFieldOptions').val(options.join('\n'));
			return;
		},

	'GetDate':
		function(type)
		{
			if (type == '') {
				return false;
			}

			var day = $('#FormField' + type + 'Day').val();
			var month = $('#FormField' + type + 'Month').val();
			var year = $('#FormField' + type + 'Year').val();

			if (day == '' && month == '' && year == '') {
				return false;
			}

			return {'year': year, 'month': month, 'day': day};
		},

	'SetDate':
		function(type, date)
		{
			if (type == '' || typeof(date) !== 'object') {
				return false;
			}

			if (typeof(date.day) == undefined || typeof(date.month) == undefined || typeof(date.year) == undefined) {
				return false;
			}

			$('#FormField' + type + 'Day').val(date.day);
			$('#FormField' + type + 'Month').val(date.month);
			$('#FormField' + type + 'Year').val(date.year);

			return true;
		},

	'SetToday':
		function(type)
		{
			if (type == '') {
				return false;
			}

			var date = new Date();
			var today = {
					'day': date.getUTCDate(),
					'month': date.getUTCMonth()+1,
					'year': date.getUTCFullYear()
			};

			return FormFieldAdmin.SetDate(type, today);
		},

	'CheckDate':
		function(checkObj)
		{
			if (typeof(checkObj.day) == 'undefined' || checkObj.day == '') {
				return false;
			}

			if (typeof(checkObj.month) == 'undefined' || checkObj.month == '') {
				return false;
			}

			if (typeof(checkObj.year) == 'undefined' || checkObj.year == '') {
				return false;
			}

			var dateObj = new Date();
			dateObj.setFullYear(checkObj.year, checkObj.month-1, checkObj.day);

			if (dateObj.getFullYear() != checkObj.year || dateObj.getMonth() != (checkObj.month-1) || dateObj.getDate() != checkObj.day) {
				return false;
			}

			return true;
		},

	'ParseDate':
		function(date)
		{
			var dateStr = date.year;

			if (date.month.length == 1) {
				dateStr += '-0' + date.month;
			} else {
				dateStr += '-' + date.month;
			}

			if (date.day.length == 1) {
				dateStr += '-0' + date.day;
			} else {
				dateStr += '-' + date.day;
			}

			return dateStr;
		}
};