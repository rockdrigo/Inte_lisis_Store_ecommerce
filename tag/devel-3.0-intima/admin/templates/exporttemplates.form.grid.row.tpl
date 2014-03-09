<li id="ele-{{ FieldID|safe }}" class="SortableRow{{ TypeName|safe }} SortableRow" style="margin-left: 0px;">
	<table class="GridPanel" cellspacing="0" cellpadding="0" border="0" style="width:100%;">
		<tr class="GridRow" id="row_{{ FieldID|safe }}" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
			<td align="center" style="width: {{ CheckColWidth|safe }}px; {{ CheckAlign|safe }}">
				{{ NodeJoin|safe }}
				<input type="checkbox" name="{{ FieldType|safe }}Field[{{ FieldID|safe }}]" id="{{ FieldID|safe }}" {{ FieldChecked|safe }} value="1" />
			</td>
			<td class="DragMouseDown sort-handle {{ FieldLabelClass|safe }}" style="width: 155px; {{ CheckAlign|safe }}" id="label_{{ FieldID|safe }}">
				{{ FieldLabel|safe }}:
			</td>
			<td id="headercol_{{ FieldID|safe }}">
				<input type="text" class="Field200 {{ FieldClass|safe }}" id="header_{{ FieldID|safe }}" name="{{ FieldType|safe }}Header[{{ FieldID|safe }}]" value="{{ FieldHeader|safe }}" maxlength="63" {{ FieldReadOnly|safe }}/>
				{{ FieldHelp|safe }}
				{{ SubFields|safe }}
			</td>
		</tr>
	</table>
</li>