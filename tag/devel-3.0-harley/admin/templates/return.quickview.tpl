<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td valign="top" width="50%" class="QuickViewPanel" style="padding-right: 10px;">
			<h5>{% lang 'ReturnDetails' %}</h5>
			<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td class="text" width="120" nowrap="nowrap" style="padding-right: 4px;">{% lang 'ReturnReason' %}</td>
					<td class="text"><input type="text" value="{{ ReturnReason|safe }}" class="Field200" style="width: 95%;" readonly="readonly" /></td>
				</tr>

				<tr>
					<td class="text" width="120" nowrap="nowrap" style="padding-right: 4px;">{% lang 'ReturnAction' %}</td>
					<td class="text"><input type="text" value="{{ ReturnAction|safe }}" class="Field200" style="width: 95%;" readonly="readonly" /></td>
				</tr>

				<tr>
					<td class="text" width="120" nowrap="nowrap" valign="top" style="padding-right: 4px;">{% lang 'CustomerComments' %}</td>
					<td class="text"><textarea cols="10" rows="4" readonly="readonly" class="Field200" style="width: 95%;" >{{ CustomerComments|safe }}</textarea></td>
				</tr>
			</table>
		</td>
		<td valign="top" style="padding-left: 15px;">
			<h5>{% lang 'StaffNotes' %}</h5>
			<div id="ReturnNotes{{ ReturnId|safe }}">
				<input type="hidden" name="returnId" value="{{ ReturnId|safe }}" />
				<textarea cols="50" rows="6" class="Field250" style="width: 95%;" name="returnNotes">{{ StaffNotes|safe }}</textarea>
				<div>
					<input type="button" value="{% lang 'UpdateNotes' %}" onclick="UpdateReturnNotes({{ ReturnId|safe }});" />
				</div>
			</div>
		</td>
	</tr>
</table>
