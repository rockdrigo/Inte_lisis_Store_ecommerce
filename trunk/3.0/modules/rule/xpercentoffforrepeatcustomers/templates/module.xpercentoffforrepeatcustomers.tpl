{% lang 'XPERCENTOFFFORREPEATCUSTOMERSIf' %}
{{ Qty0|safe }}
{% lang 'XPERCENTOFFFORREPEATCUSTOMERSThen' %}
<input name="var_amount" class="Field20" id="amount" size="3" maxlength="6" value="{{ var_amount|safe }}"></input>%
{% lang 'XPERCENTOFFFORREPEATCUSTOMERSOff' %}

<script type="text/javascript">

$('#orders').val({{ var_orders|safe }});

</script>