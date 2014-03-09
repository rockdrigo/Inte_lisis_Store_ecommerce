{% lang 'XOFFFORREPEATCUSTOMERSIf' %}
{{ Qty0|safe }}
{% lang 'XOFFFORREPEATCUSTOMERSThen' %}
{{ CurrencyLeft|safe }}<input type="text" name="varn_amount" class="Field20" id="amount" size="3" value="{{ varn_amount|safe }}" />{{ CurrencyRight|safe }}
{% lang 'XOFFFORREPEATCUSTOMERSOff' %}

<script type="text/javascript">

$('#orders').val({{ var_orders|safe }});

</script>