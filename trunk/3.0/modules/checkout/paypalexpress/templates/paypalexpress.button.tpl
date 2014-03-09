<div class="FloatRight PayPalExpressCheckout">

	<p>{% lang 'PayPalExpressOrUse' %}</p>
	<p>
		<form method="post" action="{{ CheckoutLink|safe }}">
			<input type="image" name="submit" alt="{% lang 'CheckoutWithPayPal' %}" src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif" />
			<input type="hidden" name="provider" value="paypalexpress" />
			<input type="hidden" name="action" value="set_external_checkout" />
		</form>
	</p>
</div>