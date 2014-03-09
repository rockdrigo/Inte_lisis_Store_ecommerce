<div class="ModalTitle">
	{% lang 'OrderNotesPopupHeading' %}
</div>
<div class="ModalContent">
	<p class="MessageBox MessageBoxInfo">
		{% lang 'OrderNotesPopupIntro' %}
	</p>

	<form action="" id="notesForm">
		<input type="hidden" id="orderId" name="orderId" value="{{ OrderID|safe }}" />

		<table class="GridPanel">
			<tr class="Heading3">
				<td>{% lang 'OrderComments' %}</td>
			</tr>
			<tr>
				<td>
					<textarea id="ordcustmessage" name="ordcustmessage" rows="8" style="width:98%;">{{ OrderCustomerMessage|safe }}</textarea>
				</tr>
			</tr>
			<tr class="Heading3">
				<td>{% lang 'StaffNotes' %}</td>
			</tr>
			<tr>
				<td>
					<textarea id="ordnotes" name="ordnotes" rows="8" style="width:98%;">{{ OrderNotes|safe }}</textarea>
				</td>
			</tr>
		</table>
	</form>
</div>
<div class="ModalButtonRow">
	<div class="FloatLeft">
		<img src="images/loading.gif" alt="" style="vertical-align: middle; display: none;" class="LoadingIndicator" />
		<input type="button" class="CloseButton FormButton" value="{% lang 'Cancel' %}" onclick="$.modal.close();" />
	</div>
	<input type="button" name="SaveNotesButton" class="Submit" value="{% lang 'Save' %}" onclick="{% if order.deleted %}Order.showOrderDeletedGeneralNotice();{% else %}Order.SaveNotes('{{ ThankYouID|safe }}'){% endif %}" />
</div>

<script type="text/javascript">
	lang.OrderCommentsDefault = '{% lang 'OrderCommentsDefault' %}';
	lang.OrderNotesDefault = '{% lang 'OrderNotesDefault' %}';

	function ShowOrderCommentsDefault()
		{
			$('#ordcustmessage')
				.val(lang.OrderCommentsDefault)
				.data('usingDefault', 1)
				.addClass('OrderDefaultField')
				.attr('name', 'ordcustmessage_default')
			;
		}

		function ShowOrderNotesDefault()
		{
			$('#ordnotes')
				.val(lang.OrderNotesDefault)
				.data('usingDefault', 1)
				.addClass('OrderDefaultField')
				.attr('name', 'ordnotes_default')
			;
		}

		if(!$('#ordcustmessage').val()) {
			ShowOrderCommentsDefault();
			$('#ordcustmessage')
				.focus(function() {
					if($(this).data('usingDefault') != 1) {
						return;
					}
					$(this)
						.val('')
						.attr('name', 'ordcustmessage')
						.removeClass('OrderDefaultField')
					;
				})
				.blur(function() {
					if(!$(this).val()) {
						ShowOrderCommentsDefault();
					}
					else {
						$(this)
							.data('usingDefault', 0)
						;
					}
				})
			;
		}

		if(!$('#ordnotes').val()) {
			ShowOrderNotesDefault();
			$('#ordnotes')
				.focus(function() {
					if($(this).data('usingDefault') != 1) {
						return;
					}
					$(this)
						.val('')
						.attr('name', 'ordnotes')
						.removeClass('OrderDefaultField')
					;
				})
				.blur(function() {
					if(!$(this).val()) {
						ShowOrderNotesDefault();
					}
					else {
						$(this)
							.data('usingDefault', 0)
						;
					}
				})
			;
		}
</script>
