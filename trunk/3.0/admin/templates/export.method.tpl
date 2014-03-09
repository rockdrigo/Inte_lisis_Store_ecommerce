<tr>
	<td>
		<label>
			<input type="radio" id="csv" name="format" value="{{ MethodName|safe }}" {{ MethodChecked|safe }}/>
			{{ MethodTitle|safe }}
		</label>
	</td>
	<td>
		<img onmouseout="HideHelp('d{{ MethodName|safe }}');" onmouseover="ShowHelp('d{{ MethodName|safe }}', '{{ MethodName|safe }}', '{{ MethodHelp|safe }}')" src="images/help.gif" width="24" height="16" border="0">
		<div style="display:none" id="d{{ MethodName|safe }}"></div>
	</td>
</tr>