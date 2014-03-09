<div id="ModalTitle">
	{{ title }}
</div>
<div id="ModalContent" style="{{ style }}">
	<p>{{ message }}</p>
</div>
<div id="ModalButtonRow">
	<div class="FloatLeft"><input class="CloseButton" type="button" value="{% lang 'Close' %}" onclick="$.modal.close();" /></div>
	<div class="Clear"></div>
</div>
