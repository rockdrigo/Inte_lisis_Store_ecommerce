<div class="ModalTitle">
	{% lang 'CustomerNotesPopupHeading' %}
</div>
<div class="ModalContent">
	<p class="MessageBox MessageBoxInfo">
		{% lang 'CustomerNotesPopupIntro' %}
	</p>

	<form action="" id="notesForm">
		<input type="hidden" id="customerId" name="customerId" value="{{ CustomerId|safe }}" />
		<textarea id="custnotes" name="custnotes" rows="8" style="width:98%;">{{ CustomerNotes|safe }}</textarea>
	</form>
</div>
<div class="ModalButtonRow">
	<div class="FloatLeft">
		<img src="images/loading.gif" alt="" style="vertical-align: middle; display: none;" class="LoadingIndicator" />
		<input type="button" class="CloseButton FormButton" value="{% lang 'Cancel' %}" onclick="$.modal.close();" />
	</div>
	<input type="button" class="Submit" value="{% lang 'Save' %}" name="SaveNotesButton" onclick="Customers.SaveNotes()" />
</div>