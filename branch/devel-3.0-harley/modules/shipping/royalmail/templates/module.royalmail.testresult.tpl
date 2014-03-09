<fieldset style="margin:10px">

<legend>{% lang 'RoyalMailShippingQuote' %}</legend>

<table width="100%" style="background-color:#fff" class="Panel">

	<tr>

		<td style="width:120px; padding-left:15px">

			&nbsp;

		</td>

		<td>

			<img style="margin-top:5px" src="../modules/shipping/royalmail/images/{{ Image|safe }}" />

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			{% lang 'QuoteStatus' %}:

		</td>

		<td style="color:{{ Color|safe }}">

			<strong>{{ Status|safe }}</strong>

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			{{ Label|safe }}:

		</td>

		<td style="color:{{ Color|safe }}">

			{{ Message|safe }}

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			&nbsp;

		</td>

		<td class="PanelBottom" style="color:{{ Color|safe }}">

			<input type="button" class="FormButton" style="width:120px" value="{% lang 'NewShippingQuote' %}" onclick="history.go(-1)" />

			<input type="button" class="FormButton" style="width:90px" value="{% lang 'CloseWindow' %}" onclick="window.close()" />

		</td>

	</tr>

</table>

</fieldset>

