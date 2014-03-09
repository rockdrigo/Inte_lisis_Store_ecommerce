<select id="accounting_quickbooks_orderoption" class="Field250" name="accounting_quickbooks[orderoption]">
	<option value="receipt" {{ OrderTypeReceiptSelected|safe }}>{% lang 'QuickBooksShowSalesOrderOptionSalesReceipt' %}</option>
	<option value="order" {{ OrderTypeOrderSelected|safe }}>{% lang 'QuickBooksShowSalesOrderOptionSalesOrder' %}</option>
</select>

<script type="text/javascript"><!--

	function SetAccountingOrderType()
	{
		var orderType = document.getElementById("accounting_quickbooks_orderoption");
		var originalVal = $(orderType).val();
		orderType.options.length = 0;
		
		if ($("#accounting_quickbooks_type").val().substr(0, 7) == "Premier") {
			orderType.options[0] = new Option("{% lang 'QuickBooksShowSalesOrderOptionSalesReceipt' %}", "receipt");
			orderType.options[1] = new Option("{% lang 'QuickBooksShowSalesOrderOptionSalesOrder' %}", "order");
			$(orderType).val(originalVal);
		} else {
			orderType.options[0] = new Option("{% lang 'QuickBooksShowSalesOrderOptionSalesReceipt' %}", "receipt");
			$(orderType).val("receipt");
		}
	}

	function SetAccountingOrderTypeOnLoad()
	{
		$("#accounting_quickbooks_type").change(SetAccountingOrderType);
		SetAccountingOrderType();
	}

	$(document).ready(
		function()
		{
			AdminAccountingSettings.addOnLoadFunc(SetAccountingOrderTypeOnLoad);
		}
	);

//--></script>