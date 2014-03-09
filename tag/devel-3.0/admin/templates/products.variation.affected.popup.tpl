<div class="ModalTitle">
	{% lang 'ProductVariationPopupHeading' %}
</div>
<div class="ModalContent">
	<p class="MessageBox MessageBoxInfo">
		{{ ProductVariationPopupIntro|safe }}
	</p>

	<div id="VariationAffectedProductList">
		<dl>
			{{ AffectedProducts|safe }}
		</dl>
	</div>
</div>
<div class="ModalButtonRow">
	<div class="FloatLeft">
		<img src="images/loading.gif" alt="" style="vertical-align: middle; display: none;" class="LoadingIndicator" />
		<input type="button" class="CloseButton FormButton" value="{% lang 'Cancel' %}" onclick="$.modal.close();" />
	</div>
	<input type="button" class="Submit" value="{% lang 'Save' %}" onclick="SaveVariation()" />
</div>
<script type="text/javascript"><!--

	function SaveVariation()
	{
		$.modal.close();
		window.parent.variationForm.submit();
	}

//-->
</script>