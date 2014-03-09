<div class="ModalTitle">{{ lang.RebuildingProductPrices }}</div>
<div class="ModalContent">
	<div id="rebuildTaxPrices">
		<p>{{ lang.RebuildingProductPricesIntro }}</p>
		<p>{{ lang.SoFar }}</p>
		<ul>
			{% if isDeleting %}
				<li class="deletes"><span>0</span> {{ lang.RebuildingProductPricesNumRemoved }}</li>
			{% endif %}
			{% if isUpdating %}
				<li class="updates"><span>0</span> {{ lang.RebuildingProductPricesNumUpdated }}</li>
			{% endif %}
		</ul>
		<p style="text-align: center"><img src="images/loadingAnimation.gif" alt="" /></p>
	</div>
</div>
<script type="text/javascript" charset="utf-8">
	function rebuildTaxPrices(start)
	{
		$.ajax({
			url: 'index.php',
			type: 'post',
			data: {
				'ToDo': 'rebuildTaxZonePrices',
				'start': start,
				'run': true
			},
			dataType: 'json',
			success: function(response) {
				if(response.finished != undefined && response.finished == true) {
					window.location = 'index.php?ToDo=viewTaxSettings&rebuilt=1';
					$.iModal.close();
				}
				else if(response.action == undefined || response.changes == undefined) {
					alert('{{ lang.ErrorRebuildingTaxPrices }}')
					$.iModal.close();
					return;
				}
				
				action = response.action;
				if(action == 'rebuildPricing') {
					$('#rebuildTaxPrices li.updates span').html(number_format(
						parseInt($('#rebuildTaxPrices li.updates span').html().replace(',', ''), 10) +
						response.changes
					));
				}
				else if(action == 'deleteZone' || action == 'deleteClass') {
					$('#rebuildTaxPrices li.deletes span').html(number_format(
						parseInt($('#rebuildTaxPrices li.deletes span').html().replace(',', ''), 10) +
						response.changes
					));
				}
				
				rebuildTaxPrices(response.nextStart);
			},
			error: function()
			{
				alert('{{ lang.ErrorRebuildingTaxPrices }}')
				$.iModal.close();
			}
		});
	}
	
	rebuildTaxPrices(0);
</script>