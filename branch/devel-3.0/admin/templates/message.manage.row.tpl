

	<tr id="tr{{ MessageId|safe }}" class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">

		<td width="25" align="center"><input type="checkbox" name="messages[]" value="{{ MessageId|safe }}"></td>

		<td width="25" align="center">

			<img src="images/message.gif">

		</td>

		<td width="25" align="center">

			<img src="images/messageflag.gif" style="display:{{ HideFlag|safe }}">&nbsp;

		</td>

		<td>

			<div style="margin-left:20px">{{ Subject|safe }}</div>

		</td>

		<td>

			{{ OrderMessage|safe }}

		</td>

		<td>

			{{ OrderFrom|safe }}

		</td>

		<td>

			{{ MessageDate|safe }}

		</td>

		<td nowrap>

			{{ MessageStatus|safe }}

		</td>

		<td>

			<a title="{% lang 'EditMessage' %}" href="{{ ShopPath|safe }}/admin/index.php?ToDo=editOrderMessage&amp;orderId={{ OrderId|safe }}&amp;messageId={{ MessageId|safe }}">{% lang 'Edit' %}</a>&nbsp;&nbsp;&nbsp;

			<a title="{% lang 'FlagMessage' %}" href="{{ ShopPath|safe }}/admin/index.php?ToDo=flagOrderMessage&amp;flagState={{ FlagState|safe }}&amp;orderId={{ OrderId|safe }}&amp;messageId={{ MessageId|safe }}">{{ FlagText|safe }}</a>

		</td>

	</tr>

