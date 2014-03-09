
<div id="" class="PayPalMultiAccountsDiv">
	<input type="text" name="checkout_paypal[accountname_{{ POS|safe }}]" value="{{ ACCOUNTNAME_VAL|safe }}" id="accountname_{{ POS|safe }}" class="Field50 PayPalAccounts PayPalAccountName">
	<select name="checkout_paypal[accountstore_{{ POS|safe }}]" id="accountstore_{{ POS|safe }}" class="Select PayPalAccounts PayPalAccountstore">
		{{ STOREOPTIONS|safe }}
	</select>

	<a href="#" onclick="AddTotalRange(this.parentNode); return false;" class="add">Add</a>
	<a href="#" onclick="RemoveTotalRange(this.parentNode); return false;" class="remove">Remove</a>
</div>
