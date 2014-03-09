<div class="toolbar">
	<h1 id="pageTitle"><span class="ordersViewSelectClick">{% lang 'Orders' %}</span></h1>
	<a style="position:absolute; left:5px; top:8px; width:59px" class="button" href="{{ ShopPath|safe }}/admin/index.php?ToDo=viewOrders" type="submit">{% lang 'AllOrders' %}</a>
	<a class="button" href="#searchForm">{% lang 'Search' %}</a>
</div>
<ul selected="true">
	{{ Message|safe }}
	{{ OrderGrid|safe }}
	<li style="text-align:center">{{ SmallNav|safe }}&nbsp;</li>

	<select id="orderViewList" class="hiddenSelect">
		<option value="0">{% lang 'AllOrders' %}</option>
		{% for customSearch in customSearchList %}
			<option value="{{ customSearch.searchid }}" {% if CustomSearchId == customSearch.searchid %}selected="selected"{% endif %}>{{ customSearch.searchname }}</option>
		{% endfor %}
	</select>
</ul>
<form id="searchForm" class="dialog" action="index.php?ToDo=searchOrdersRedirect" method="get" onsubmit="alert(5)">
	<input type="hidden" name="paymentMethod" value="" />
	<input type="hidden" name="shippingMethod" value="" />
	<input type="hidden" name="couponCode" value="" />
	<input type="hidden" name="orderFrom" value="" />
	<input type="hidden" name="orderTo" value="" />
	<input type="hidden" name="totalFrom" value="" />
	<input type="hidden" name="totalTo" value="" />
	<input type="hidden" name="dateRange" value="" />
	<input type="hidden" name="fromDate" value="" />
	<input type="hidden" name="toDate" value="" />
	<input type="hidden" name="sortField" value="orderid" />
	<input type="hidden" name="sortOrder" value="asc" />
	<fieldset>
	    <h1>{% lang 'OrderSearch' %}</h1>
	    <a class="button leftButton" type="cancel">{% lang 'Cancel' %}</a>
	    <input type="text" id="searchQuery" value="{% lang 'SearchOrdersPlaceholder' %}" onclick="if(this.value=='{% lang 'SearchOrdersPlaceholder' %}') { this.value=''; this.style.color='#000'; }" style="color:#CACACA; padding-left:3px; font-size:15px" />
	    <select id="orderStatus" style="font-size:16px; width:100%">
		<option value="">{% lang 'AllOrderStatuses' %}</option>
		{{ OrderStatusOptions|safe }}
	    </select>
	    <input type="button" value="{% lang 'Search' %}" onclick="searchRedir()" />
	</fieldset>
</form>

<script type="text/javascript">

	function searchRedir() {
		var q = document.getElementById("searchQuery").value;
		var s = document.getElementById("orderStatus").value;

		if(q == "{% lang 'SearchOrdersPlaceholder' %}") {
			q = "";
		}

		document.location.href = "{{ ShopPath|safe }}/admin/index.php?ToDo=searchOrdersRedirect&SubmitButton1=Search&searchQuery="+q+"&orderStatus="+s+"&paymentMethod=&shippingMethod=&couponCode=&orderFrom=&orderTo=&totalFrom=&totalTo=&dateRange=&fromDate=&toDate=&sortField=orderid&sortOrder=desc";
		return false;
	}

	$(function(){
		$('.ordersViewSelectClick').click(function(event){
			$('#orderViewList').focus();
		});

		$('#orderViewList').change(function(event){
			location.href = 'index.php?ToDo=viewOrders&searchId=' + $(this).val();
		});
	});

</script>
