<script>
var MCSLearnMore = "{{ MCSLearnMore|safe }}";
var VSCLearnMore = "{{ VSCLearnMore|safe }}";
</script>
<div style="margin-left: 170px; width: 500px;">
<small>
{% lang 'PreAuthenticationMessage' %}
</small>
</div>
<div style="margin-left: 170px;">
<a href="#" target="view" onClick="ShowPopupHelp(MCSLearnMore, '{{ ShopPathSSL|safe }}', true); return false;"> <img src="{{ ModuleImagePath|safe }}/mcsc_learn_more.gif" alt="MasterCard SecureCode" border="0" /></a> &nbsp;<a href="#" target="view" onClick="ShowPopupHelp(VSCLearnMore, '{{ ShopPathSSL|safe }}', true); return false;"><img src="{{ ModuleImagePath|safe }}/vbv_learn_more.gif" alt="Verified by Visa" border="0"></a>
</div>
