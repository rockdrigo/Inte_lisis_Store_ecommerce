
	<div class="BodyContainer">
		<input type="hidden" id="enableSortable" value="{{ FormFieldsIsSortable|safe }}" />
		<table cellSpacing="0" cellPadding="0" width="100%">
		<tr>
			<td class="Heading1">{% lang 'FormFieldsHeading' %}</td>
		</tr>
		<tr>
			<td class="Intro">
				<p>{% lang 'FormFieldsIntro' %}</p>
				<div id="FormFieldStatus">
					{{ Message|safe }}
				</div>
			</td>
		</tr>
		<tr>
			<td>
				<input type="hidden" id="CurrentFormFieldsFormId" name="CurrentFormFieldsFormId" value="{{ FormFieldsAccountFormId|safe }}" />
				<ul id="FormFieldsSectionNav" class="tabnav">
					<li><a href="#" class="active" id="FormFieldsSection_{{ FormFieldsAccountFormId|safe }}" onclick="ChangeFormFieldsTab({{ FormFieldsAccountFormId|safe }});">{{ FormFieldsSectionAccount|safe }}</a></li>
					<li><a href="#" id="FormFieldsSection_{{ FormFieldsAddressFormId|safe }}" onclick="ChangeFormFieldsTab({{ FormFieldsAddressFormId|safe }});">{{ FormFieldsSectionAddress|safe }}</a></li>
				</ul>
			</td>
		</tr>
		<tr>
			<td style="{{ HideFormFieldsButtons|safe }}">
				<p class="Intro">
					<button id="ViewsMenuButton" class="PopDownMenu FormButton FormFieldsMenuButton" style="display:{{ FormFieldsHideAddButton|safe }}">{% lang 'FormFieldsAddField' %} <img src="./images/arrow_blue.gif" alt="" /></button>
					 &nbsp; <input type="button" value="{% lang 'FormFieldsDeleteSelected' %}" onclick="DeleteSelectedFormFields()" class="FormButton" style="width: auto; display:{{ FormFieldsHideDeleteButton|safe }}" />
				</p>
			</td>
		</tr>
		<tr>
			<td>
				<table class="GridPanel SortablePanel" cellspacing="0" cellpadding="0" border="0" style="width:100%;">
					<tr class="Heading3">
						<td align="center" style="width:18px;"><input type="checkbox" id="FormFieldDeleteCheckbox" onclick="TickFormFields(this.checked);"></td>
						<td>
							{% lang 'Name' %}
						</td>
						<td style="width:17%;">
							{% lang 'Data' %}
						</td>
						<td style="width:17%;">
							{% lang 'LastModified' %}
						</td>
						<td style="width:17%;">
							{% lang 'Type' %}
						</td>
						<td style="width:17%;">
							{% lang 'Action' %}
						</td>
					</tr>
				</table>
				<div id="FormFieldsGrid">
					{{ FormFieldsGrid|safe }}
				</div>
			</td>
		</tr>
		</table>
	</div>

	<div id="ViewsMenu" class="DropShadow DropDownMenu" style="display: none; width:150px">
		<div>
			{{ FormFieldsOptions|safe }}
		</div>
	</div>

	<script type="text/javascript">

		function TickFormFields(ticked)
		{
			$('.FormFieldsIdx').each(
				function()
				{
					if (!this.disabled) {
						this.checked = ticked;
					}
				}
			);
		}

		function ChangeFormFieldsTab(formId)
		{
			if (formId == '' || isNaN(formId)) {
				return;
			}

			$('#FormFieldsSectionNav li a').each(
				function()
				{
					if ($(this).attr('id') == 'FormFieldsSection_' + formId) {
						$(this).attr('class', 'active');
						$('#CurrentFormFieldsFormId').val(formId);
					} else {
						$(this).attr('class', '');
					}
				}
			);

			$.ajax({
				url: 'remote.php',
				type: 'post',
				data: 'remoteSection=formfields&w=getFormFieldGrid&formId='+formId,
				success: ChangeFormFieldsTabCallback
			});
		}

		function ChangeFormFieldsTabCallback(data)
		{
			if ($('status', data).text() == '0') {
				return;
			}

			$('#FormFieldsGrid').html($('grid', data).text());

			InitFormFieldSortable();
		}

		function AddFormField(fieldType)
		{
			var formId = $('#CurrentFormFieldsFormId').val();

			if (fieldType == '' || formId == '' || isNaN(formId)) {
				return;
			}

			$.iModal({
				type: 'ajax',
				url: 'remote.php?remoteSection=formfields&w=addFieldSetupPopup&fieldType='+fieldType+'&formId='+formId,
				width: 600,
				onOpen:
					function()
					{
						$('#ModalContainer').show();
						InitFormFieldPopup();
					},
				onBeforeClose:
					function()
					{
						ChangeFormFieldsTab($('#CurrentFormFieldsFormId').val());
					}
			});
		}

		function EditFormField(fieldId, formId)
		{
			if (isNaN(fieldId) || isNaN(formId)) {
				return;
			}

			$.iModal({
				type: 'ajax',
				url: 'remote.php?remoteSection=formfields&w=getFieldSetupPopup&fieldId='+fieldId+'&formId='+formId,
				width: 600,
				onOpen:
					function()
					{
						$('#ModalContainer').show();
						InitFormFieldPopup();
					},
				onBeforeClose:
					function()
					{
						ChangeFormFieldsTab($('#CurrentFormFieldsFormId').val());
					}
			});
		}

		function CopyFormField(fieldId, formId)
		{
			if (isNaN(fieldId) || isNaN(formId)) {
				return;
			}

			$.iModal({
				type: 'ajax',
				url: 'remote.php?remoteSection=formfields&w=copyFieldSetupPopup&fieldId='+fieldId+'&formId='+formId,
				width: 600,
				onOpen:
					function()
					{
						$('#ModalContainer').show();
						InitFormFieldPopup();
					},
				onBeforeClose:
					function()
					{
						ChangeFormFieldsTab($('#CurrentFormFieldsFormId').val());
					}
			});
		}

		function DeleteSelectedFormFields()
		{
			var selectedIdx = [];
			var formId = $('#CurrentFormFieldsFormId').val();

			$('.FormFieldsIdx').each(
				function()
				{
					if (!this.disabled && this.checked) {
						selectedIdx[selectedIdx.length] = this.value;
					}
				}
			);

			if (selectedIdx.length < 1) {
				alert("{% lang 'FormFieldDeleteSelectedInvalid' %}");
				return;
			}

			if (!confirm("{% lang 'FormFieldDeleteSelectedConfirm' %}")) {
				return;
			}

			$.ajax({
				url: 'remote.php',
				type: 'post',
				data: 'remoteSection=formfields&w=deleteMultiField&fieldIdx='+selectedIdx.join(',')+'&formId='+formId,
				success: DeleteSelectedFormFieldsCallback
			});
		}

		function DeleteSelectedFormFieldsCallback(data)
		{
			if ($('status', data).text() == '1') {
				ChangeFormFieldsTab($('#CurrentFormFieldsFormId').val());
				$('#FormFieldDeleteCheckbox').attr('checked', false);
				display_success('FormFieldStatus', $('msg', data).text());
			} else {
				display_error('FormFieldStatus', $('msg', data).text());
			}
		}

		function DeleteFormField(fieldId, formId)
		{
			if (isNaN(fieldId) || isNaN(formId)) {
				return;
			}

			if (!confirm("{% lang 'FormFieldDeleteConfirm' %}")) {
				return;
			}

			$.ajax({
				url: 'remote.php',
				type: 'post',
				data: 'remoteSection=formfields&w=deleteField&fieldId='+fieldId+'&formId='+formId,
				success: DeleteFormFieldCallback
			});
		}

		function DeleteFormFieldCallback(data)
		{
			var fieldId = $('fieldId', data).text();

			if ($('status', data).text() == '1') {
				ChangeFormFieldsTab($('#CurrentFormFieldsFormId').val());
				display_success('FormFieldStatus', $('msg', data).text());
			} else {
				display_error('FormFieldStatus', $('msg', data).text());
			}
		}

		function UpdateSortableFormField()
		{
			var idx = [];

			$('input.FormFieldsIdx').each(
				function()
				{
					idx[idx.length] = $(this).val();
				}
			);

			$.ajax({
				url: 'remote.php',
				type: 'post',
				data: {
						'remoteSection': 'formfields',
						'w': 'resortFormFieldGrid',
						'formId': $('#CurrentFormFieldsFormId').val(),
						'sortorder': idx.join(',')
					},
				success: function() { display_success('FormFieldStatus', "{% lang 'FormFieldReordered' %}"); }
			});
		}

		function InitFormFieldSortable()
		{
			if ($('#enableSortable').val() == '') {
				return;
			}

			$('#FormFieldsGrid ul.SortableList').each(
				function()
				{
					$(this).sortable(
					{
						accept: 'SortableRow',
						containment: 'parent',
						handle: '.sort-handle',
						opacity: .8,
						placeholder: 'SortableRowHelper',
						forcePlaceholderSize: true,
						items: 'li',
						tolerance: 'pointer',
						update: UpdateSortableFormField
					});
				}
			);
		}

		$(document).ready(
			function()
			{
				InitFormFieldSortable();
			}
		);

	</script>