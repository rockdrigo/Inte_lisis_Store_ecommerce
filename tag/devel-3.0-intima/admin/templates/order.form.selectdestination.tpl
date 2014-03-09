{% import "macros/util.tpl" as util %}
{% import "macros/forms.tpl" as formBuilder %}
{% import "order.form.customizeitem.tpl" as self %}

<div class="ModalTitle">
	Select Shipping Method &amp; Destination
</div>
<form id="orderCustomizeItemForm" action="#">
<div class="ModalContent">
	<div id="selectDestinationAddressContainer">

	</div>
</div>
<div class="ModalButtonRow">
	<div class="FloatLeft">
		<input class="CloseButton" type="button" value="{% lang 'Close' %}" onclick="$.modal.close();" />
	</div>
	<input type="submit" class="SubmitButton" value="Set Destination &amp; Method" />
</div>
</form>
<script language="javascript" type="text/javascript">//<![CDATA[
	$(function(){
		Order_Form.selectDestinationModalLoaded();
	});
//]]></script>
