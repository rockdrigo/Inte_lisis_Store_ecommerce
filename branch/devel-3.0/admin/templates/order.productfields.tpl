<div id="exportBox">
	<div class="ModalTitle">
		{% lang 'ConfigurableFields' %}
	</div>
	<div class="ModalContent">
		{{ OrderProducts|safe }}
	</div>
	<div class="ModalButtonRow">
		<div class="FloatRight">
			<input type="button" class="Button" value="{% lang 'Cancel' %}" onclick="$.modal.close();" />
		</div>
	</div>
</div>