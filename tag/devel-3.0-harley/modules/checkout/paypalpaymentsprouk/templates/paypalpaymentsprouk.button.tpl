<div class="FloatRight PayPalExpressCheckout">

	<p>{% lang 'PayPalPaymentsProOrUse' %}</p>
	<p>
		<form method="post" action="{{ CheckoutLink|safe }}">
			<input type="image" name="submit" alt="{% lang 'CheckoutWithPayPal' %}" src="https://www.paypal.com/en_GB/i/btn/btn_xpressCheckout.gif" />
			<input type="hidden" name="provider" value="paypalpaymentsprouk" />
			<input type="hidden" name="action" value="set_external_checkout" />
		</form>
	</p>
</div>