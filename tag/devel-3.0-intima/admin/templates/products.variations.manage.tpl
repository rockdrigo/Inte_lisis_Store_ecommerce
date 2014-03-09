
	<div class="BodyContainer">
	<table id="Table13" cellSpacing="0" cellPadding="0" width="100%">
		<tr>
			<td class="Heading1">{% lang 'ViewProductVariations' %}</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{% lang 'ViewVariationsIntro' %}</p>
			{{ Message|safe }}
			<table id="IntroTable" cellspacing="0" cellpadding="0" width="100%">
			<tr>
				<td class="Intro" valign="top">
					<input type="button" name="IndexAddButton" value="{% lang 'AddProductVariation' %}..." id="IndexCreateButton" class="SmallButton" onclick="document.location.href='index.php?ToDo=addProductVariation'" style="width:160px" /> &nbsp;
					<input type="button" name="IndexDeleteButton" value="{% lang 'DeleteSelected' %}" id="IndexDeleteButton" class="SmallButton" onclick="ConfirmDeleteSelected()" {{ DisableDelete|safe }} /> &nbsp;
					<input type="button" name="IndexImportButton" value="{% lang 'ImportProductVariations' %}" id="IndexImportButton" class="SmallButton" onclick="window.location='index.php?ToDo=importProductVariations';" {{ DisableImport|safe }} />
				</td>
			</tr>
			</table>
		</td>
		</tr>
	</table>
	<table cellspacing="0" cellpadding="0" width="100%">
		<tr>
		<td style="display:{{ DisplayGrid|safe }}">
			<form name="frmVariations" id="frmVariations" method="post" action="index.php?ToDo=deleteProductVariations">
				<div class="GridContainer">
					{{ VariationDataGrid|safe }}
				</div>
			</form>
		</td></tr>
	</table>
	</div>

	<script type="text/javascript">

		var variationForm = document.getElementById('frmVariations');

		function ConfirmDeleteSelected() {
			var fp = variationForm.elements;
			var selected = [];

			for(i = 0; i < fp.length; i++) {
				if(fp[i].type == "checkbox" && fp[i].checked)
					selected[selected.length] = fp[i].value;
			}

			if(selected.length == 0) {
				alert("{% jslang 'ChooseVariations' %}");
				return;
			}

			if (!confirm("{% jslang 'ConfirmDeleteVariations' %}")) {
				return;
			}

			$.post('remote.php?remoteSection=product_variations&w=getAffectedVariations&actionType=delete&variationIdx=' + selected.join(), null,
				function(result) {
					if (result == '0') {
						// submit immediately if nothing is affected
						window.parent.variationForm.submit();
					} else if (result == '1') {
						// display a thickbox with all the affected products in it
						// from there they can decide whether to proceed or cancel
						$.iModal({
							type: 'ajax',
							url: 'remote.php?remoteSection=product_variations&w=viewAffectedVariations&actionType=delete&variationIdx=' + selected.join(),
							width: 600
						});
					}
				}
			);
		}

		function ToggleDeleteBoxes(Status) {
			var fp = document.getElementById("frmVariations").elements;

			for(i = 0; i < fp.length; i++) {
				fp[i].checked = Status;
			}
		}

		{% if updateSessionId %}
			$.iModal({
				type: 'ajax',
				method: 'post',
				url: 'remote.php',
				urlData: {
					remoteSection: 'product_variations',
					w: 'initRebuildVariations',
					session: '{{ updateSessionId }}'
				},
				close: false,
				width: 400
			});
		{% endif %}
	</script>
