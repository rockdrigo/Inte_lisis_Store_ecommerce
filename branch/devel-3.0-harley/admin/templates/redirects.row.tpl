<tr id="RedirectRow_{{ RedirectId|safe }}" class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
	<td align="center" style="width:25px">
		<input type="checkbox" name="redirects[]" value="{{ RedirectId|safe }}" id="RedirectCheckbox_{{ RedirectId|safe }}" class="RedirectCheckbox">
	</td>
	<td align="center" style="width:20px">
		<img src="images/redirects.png" alt="product" height="16" width="16" />
	</td>
	<td>
		<div style="width:100%;"><input id="oldUrl_{{ RedirectId|safe }}" name="oldUrl[{{ RedirectId|safe }}]" class='RedirectCurrentUrl inPlaceFieldDefault' value="{{ OldURL|safe }}" /></div>
	</td>

	<td style="width:80px;">
		<select id="RedirectType_{{ RedirectId|safe }}" class="RedirectType">
			<option value="auto" {{ RedirectTypeAutoSelected|safe }}>{% lang 'RedirectTypeAuto' %}</option>
			<option value="manual" {{ RedirectTypeManualSelected|safe }}>{% lang 'RedirectTypeManual' %}</option>
		</select>
	</td>
	<td>
		<div class="RedirectAutoURL" id="RedirectAutoURL_{{ RedirectId|safe }}" style="{{ RedirectTypeAutoDisplay|safe }}">
			<span id="RedirectAutoURL_Link_{{ RedirectId|safe }}" class="RedirectAutoURL_Link"><a href="{{ NewURL|safe }}" target="_blank">{{ NewURLTitle|safe }}</a></span>
			<a href="javascript:void(0);" class="linkerButton" id="linkerButton_{{ RedirectId|safe }}">{{ LinkerTitle|safe }}</a>
		</div>

		<div class="RedirectManualURL" id="RedirectManualURL_{{ RedirectId|safe }}"  style="width:100%; {{ RedirectTypeManualDisplay|safe }}"><input id="newUrl_{{ RedirectId|safe }}" name="newUrl[{{ RedirectId|safe }}]" class='inPlaceFieldDefault RedirectNewUrl' value="{{ NewURL|safe }}" /></div>
	</td>

	<td style="width:30px;">
		<div id="RedirectActions_{{ RedirectId|safe }}" style="{{ RedirectActionsDisplay|safe }}">
			<a title='{% lang 'Test' %}' class="Action TestLink" href='{{ RedirectTestLink|safe }}' target="_blank" id="TestLink_{{ RedirectId|safe }}" >{% lang 'Test' %}</a>&nbsp;<a title='{% lang 'Copy' %}' class="Action CopyLink" href='#' id="CopyLink_{{ RedirectId|safe }}" >{% lang 'Copy' %}</a>&nbsp;<a title='{% lang 'Delete' %}' class="Action DeleteLink" href='#' id="DeleteLink_{{ RedirectId|safe }}" >{% lang 'Delete' %}</a>
		</div>
	</td>
</tr>
