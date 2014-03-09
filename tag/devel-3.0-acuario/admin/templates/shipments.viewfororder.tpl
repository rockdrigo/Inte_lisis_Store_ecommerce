<script type="text/javascript" src="script/shipments.js?{{ JSCacheToken }}"></script>
<div class="ModalTitle">
	{% lang 'ShipmentsForOrderX' with [
		'orderId': orderId
	] %}
</div>
<div class="ModalContent orderShipmentsModal">
	{% lang 'ShipmentsForOrderBelow' with  [
		'orderId': orderId
	] %}
	<div class="GridContainer" id="GridContainer">
		{{ shipmentsGrid|safe }}
	</div>
</div>
<div class="ModalButtonRow">
	<span style="float: left">
		<input type="button" value="{{ lang.ManageTheseShipments }}" onclick="$.iModal.close(); window.location='index.php?ToDo=viewShipments&orderId={{ orderId }}';" />
	</span>
    <input type="button" value="{{ lang.Close }}" class="Submit" onclick="$.iModal.close();" />
</div>
<script type="text/javascript">
	BindAjaxGridSorting();
	BindGridRowHover();
</script>
