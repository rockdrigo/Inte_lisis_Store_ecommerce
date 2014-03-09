<link rel="stylesheet" type="text/css" href="../javascript/jquery/themes/cupertino/ui.all.css" />
{% import "macros/forms.tpl" as forms %}

	<div class="BodyContainer">
	<table id="Table13" cellSpacing="0" cellPadding="0" width="100%">
		<tr>
			<td class="Heading1">
				{% lang 'View' %}: <a href="#" style="color:#005FA3" id="ViewsMenuButton" class="PopDownMenu">{{ ViewName|safe }} <img width="8" height="5" src="images/arrow_blue.gif" border="0" /></a>
			</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{{ ProductIntro|safe }}</p>
			<div id="ProductsStatus">{{ Message|safe }}</div>
			<table id="IntroTable" cellspacing="0" cellpadding="0" width="100%">
			<tr>
			<td class="Intro" valign="top" style="padding-top:10px">
				<input type="button" name="IndexAddButton" value="{% lang 'AddProduct' %}..." id="IndexCreateButton" class="SmallButton" onclick="document.location.href='index.php?ToDo=addProduct'" />
				{% lang 'Or' %}
				<select name="bulk">
					<option value="">{% lang 'ChooseAnAction' %}</option>
					<option value="delete" id="IndexDeleteButton" {{ DisableDelete|safe }}>{% lang 'DeleteSelected' %}</option>
					<option value="edit" id="IndexBulkButton" style="display: {{ HideBulkExportButton|safe }}" {{ DisableBulkEdit|safe }}>{% lang 'BulkEditProducts' %}</option>
					<option value="export" id="IndexExportButton" style="display: {{ HideExport|safe }}" {{ DisableExport|safe }}>{% lang 'ExportProducts' %}</option>
					{% if ShowListOnEbay %}
						<option value="ebay" id="IndexEbayButton">{% lang 'ListOnEbay' %}
					{% endif %}
					{% if shoppingComparisonModules | length %}
					<option value="shoppingComparison" {{ DisableExport|safe }}>{% lang 'ShoppingComparisonBulkSelectTitle' %}</option>
					{% endif %}
				</select>
				<button type="button" id="optionGo">{% lang 'Go' %}</button>
			</td>

			<td class="SmallSearch" align="right">
				<table id="Table16" style="display:{{ DisplaySearch|safe }}">
				<tr>

					<td nowrap>
						<form action="index.php?ToDo=viewProducts{{ SortURL|safe }}" method="get" onsubmit="return ValidateForm(CheckSearchForm)" style="margin: 0; padding: 0">
							{{ forms.hiddenInputs(['ToDo':'viewProducts'] + queryParams, ['searchQuery']) }}
							<input name="searchQuery" id="searchQuery" type="text" value="{{ EscapedQuery|safe }}" class="Button" size="20" />&nbsp;
							<input type="image" name="SearchButton" style="padding-left: 10px; vertical-align: top;" id="SearchButton" src="images/searchicon.gif" border="0" />
						</form>
					</td>

				</tr>
				<tr>
					<td nowrap="nowrap">
						<a href="index.php?ToDo=searchProducts">{% lang 'AdvancedSearch' %}</a>
						<span style="display:{{ HideClearResults|safe }}">| <a id="SearchClearButton" href="index.php?ToDo=viewProducts">{% lang 'ClearResults' %}</a></span>
					</td>
				</tr>
				<tr>
					<td></td>
				</tr>
				</table>
			</td>
			</tr>
			</table>
		</td>
		</tr>
		<tr>
		<td style="display: {{ DisplayGrid|safe }}">
			<form name="frmProducts" id="frmProducts" method="post" action="index.php?ToDo=deleteProducts">
				<div class="GridContainer">
					{{ ProductDataGrid|safe }}
				</div>
			</form>
		</td></tr>
	</table>
</div>

<div id="ViewsMenu" class="DropShadow DropDownMenu" style="display: none; width:200px">
	<ul>
		{{ CustomSearchOptions|safe }}
	</ul>
	<hr />
	<ul>
		<li><a href="index.php?ToDo=createProductView" style="background-image:url('images/view_add.gif'); background-repeat:no-repeat; background-position:5px 5px; padding-left:28px">{% lang 'CreateANewView' %}</a></li>
		<li style="display:{{ HideDeleteViewLink|safe }}"><a onclick="$('#ViewsMenu').hide(); confirm_delete_custom_search('{{ CustomSearchId|safe }}')" href="javascript:void(0)" style="background-image:url('images/view_del.gif'); background-repeat:no-repeat; background-position:5px 5px; padding-left:28px">{% lang 'DeleteThisView' %}</a></li>
	</ul>
</div>

<div id="invDiv" class="StockList" style="display:none"></div>

<div id="shoppingComparisonModal" title="{% lang 'ShoppingComparisonProductModalTitle' %}" style="display: none;">
	<form action="index.php?ToDo=bulkSaveProductShoppingComparisonFeeds" method="post">
		<p class="description"></p>

		<div style=" margin-bottom: 10px;">
			<select name="comparisons[]" class="Field250 ISSelectReplacement" multiple="multiple" style="height: 108px;width:350px;">
				{% for module in shoppingComparisonModules %}
				<option value="{{ module.getId() }}">{{ module.getName() }}</option>
				{% endfor %}
			</select>
			<div style="clear: both;"></div>
		</div>
	</form>
</div>

<script type="text/javascript" src="../javascript/jquery/plugins/jquery.form.js"></script>
<script type="text/javascript">

	var tok = "{{ AuthToken|safe }}";
	var inventory_product_id = 0;
	var action = "";
	var total_stock_units = 0;
		var Interspire_Ebay_ListProductsMachine = null;
	function ExportProducts()
	{
		document.getElementById("frmProducts").action = "{{ ExportAction|safe }}";
		document.getElementById("frmProducts").submit();
	}
		var searchId = {{ CustomSearchId }};
		var searchQuery = "{{ EscapedQuery|safe }}";

	function CheckSearchForm()
	{
		var query = document.getElementById('searchQuery');

		if (query.value == '') {
			alert("{% lang 'ChooseFilterOrEnterSearchTerm' %}");
			return false;
		}

		return true;
	}

	function ListProductsOnEbay()
	{
		var productOptions = {};
		productOptions.productIds = $("#frmProducts").serialize();
		productOptions.searchId = searchId;
		productOptions.searchQuery = searchQuery;

		Interspire_Ebay_ListProductsMachine.start({productOptions: productOptions});
	}

	function ConfirmDeleteSelected()
	{
		var fp = document.getElementById('frmProducts').elements;
		var c  = 0;

		for (i = 0; i < fp.length; i++) {
			if(fp[i].type == 'checkbox' && fp[i].checked)
				c++;
		}

		if (c > 0){
			if (confirm("{% lang 'ConfirmDeleteProducts' %}")) {
				document.getElementById('frmProducts').submit();
			}
		}
		else {
			alert("{% lang 'ChooseProduct' %}");
		}
	}

	function ToggleDeleteBoxes(Status)
	{
		var fp = document.getElementById('frmProducts').elements;

		for (i = 0; i < fp.length; i++) {
			fp[i].checked = Status;
		}
	}

	function ShowStock(id, InventoryType, VariationId)
	{
		var tr  = document.getElementById('tr' + id);
		var trQ = document.getElementById('trQ' + id);
		var tdQ = document.getElementById('tdQ' + id);
		var img = document.getElementById('expand' + id);

		if (img.src.indexOf('plus.gif') > -1) {
			img.src = 'images/minus.gif';

			for (i = 0; i < tr.childNodes.length; i++) {
				if (tr.childNodes[i].style != null) {
					tr.childNodes[i].style.backgroundColor = "#dbf3d1";
				}
			}

			$(trQ).find('.QuickView').load(
				'remote.php?w=inventoryLevels&p='
				+ id
				+ '&i='
				+ InventoryType
				+ '&v='
				+ VariationId
				+ '&t='
				+ tok
				, {
					'cache' : false
				}
				, function() {
					trQ.style.display = '';
				}
			);
		}
		else
		{
			img.src = "images/plus.gif";

			for (i = 0; i < tr.childNodes.length; i++) {
				if (tr.childNodes[i].style != null) {
					tr.childNodes[i].style.backgroundColor = '';
				}
			}

			trQ.style.display = 'none';
		}
	}

	function UpdateStockLevel(ProductId, InventoryType)
	{
		var loading = document.getElementById('loading' + ProductId);
		inventory_product_id = ProductId;

		// Update the stock levels via AJAX
		if (InventoryType == 0) {
			// Per-product stock levels
			var stock_level = document.getElementById('stock_level_' + ProductId);
			var stock_level_notify = document.getElementById('stock_level_notify_' + ProductId);

			if (isNaN(stock_level.value) || stock_level.value == '') {
				alert("{% lang 'EnterValidStockLevel' %}");
				stock_level.focus();
				stock_level.select();
			}
			else if(isNaN(stock_level_notify.value) || stock_level_notify.value == '') {
				alert("{% lang 'EnterValidStockLevel' %}");
				stock_level_notify.focus();
				stock_level_notify.select();
			}
			else {
				// Update the loading image
				loading.src = 'images/ajax-loader.gif';

				// Valid stock level numbers, save them using AJAX
				total_stock_units = stock_level.value;
				action = 'update_inventory_levels';

				DoCallback('w=updatePerProductInventoryLevels&p=' + ProductId + '&c=' + stock_level.value + '&l=' + stock_level_notify.value + '&t=' + tok);
			}
		}
		else if(InventoryType == 1) {
			// Per option stock levels
			var fp = document.getElementById('frmProducts').elements;
			var c = 0;
			var is_error = false;
			var update_data = '';

			total_stock_units = 0;

			for (i = 0; i < fp.length; i++) {
				if (fp[i].id.indexOf('stock_level_' + ProductId) > -1 || fp[i].id.indexOf('stock_level_notify_' + ProductId) > -1) {
					if (isNaN(fp[i].value) || fp[i].value == '') {
						alert("{% lang 'EnterValidStockLevel' %}");
						fp[i].focus();
						fp[i].select();
						is_error = true;

						break;
					}
					else {
						// It's a valid inventory related value
						update_data = update_data + fp[i].id + '=' + fp[i].value + '&';

						// Add the number of current units in stock so we can update the "In Stock" field
						if (fp[i].id.indexOf('stock_level_notify') == -1) {
							total_stock_units = total_stock_units + parseInt(fp[i].value);
						}
					}
				}
			}

			// All inventory-related fields are valid, run the AJAX query
			if (!is_error) {
				// Update the loading image
				loading.src = 'images/ajax-loader.gif';

				// Valid stock level numbers, save them using AJAX
				action = 'update_inventory_levels';

				DoCallback('w=updatePerOptionInventoryLevels&i=' + escape(update_data) + '&t=' + tok);
			}
		}
	}

	function show_inventory_levels(result)
	{
		var inventory_info = document.getElementById('StockLevelInfo' + inventory_product_id);
		inventory_info.innerHTML = result;
	}

	function update_inventory_levels(result)
	{
		// Update the loading image
		var loading = document.getElementById('loading' + inventory_product_id);
		var instock_cell = document.getElementById('InStock' + inventory_product_id);
		loading.src = 'images/ajax-blank.gif';

		if (result == '1') {
			//instock_cell.innerHTML = total_stock_units;
			display_success('ProductsStatus', "{% lang 'InventoryLevelsUpdated' %}".replace('%d', inventory_product_id));
		}
		else {
			display_error('ProductsStatus', "{% lang 'InventoryLevelsUpdateFailed' %}");
		}
	}

	function ProcessData(html)
	{
		ret = html;

		if(action == 'get_inventory_levels') {
			show_inventory_levels(ret);
		}
		else if(action == 'update_inventory_levels') {
			update_inventory_levels(ret);
		}
	}

	function confirm_delete_custom_search(search_id)
	{
		if (confirm("{% lang 'ConfirmDeleteCustomSearch' %}")) {
			document.location.href = 'index.php?ToDo=deleteCustomProductSearch&searchId=' + search_id;
		}
	}

	function quickToggle(element, what)
	{
		$.ajax({
			url: element.href + '&ajax=1',
			success: function(response) {
				if(response) {
					if(element.childNodes.length == 1 && element.childNodes[0].tagName == "IMG") {
						var image = element.childNodes[0];

						// Element was ticked, now should not be
						if(image.src.indexOf('tick') != -1) {
							element.href = element.href.replace(what+'=0', what+'=1');
							image.src = image.src.replace('tick', 'cross');
						}
						else {
							element.href = element.href.replace(what+'=1', what+'=0');
							image.src = image.src.replace('cross', 'tick');
						}
					}
				}
			}
		});
	}

	function BulkEditSelected()
	{
		var count = $('#frmProducts input:checked').length;
		if (count > 0) {
			document.getElementById('frmProducts').action = 'index.php?ToDo=bulkEditProducts';
			document.getElementById('frmProducts').submit();
		} else {
			alert("{% lang 'ChooseProductToBulkEdit' %}");
		}
	}

	/**
	 * Returns the checkboxes that are selected for the given products on the page.
	 *
	 * @return jQuery
	 */
	function getSelectedProducts()
	{
		return getAllProducts().filter(':checked');
	}

	/**
	 * Returns all products checkboxes.
	 *
	 * @return jQuery
	 */
	function getAllProducts()
	{
		return $('#frmProducts .GridRow > td:first-child > :checkbox');
	}

	/**
	 * Returns the selected products or all products if no products are selected.
	 *
	 * @return jQuery
	 */
	function getSelectedOrAllProducts()
	{
		var products = getSelectedProducts();

		if (!products.length) {
			products = getAllProducts();
		}

		return products;
	}

	/**
	 * Shows the modal window for bulk editing shopping comparison feeds.
	 *
	 * @return void
	 */
	function toggleShoppingComparisonFeeds()
	{
		var comparison = $('#shoppingComparisonModal');
		var buttons    = '<button class="FormButton" type="button" onclick="jQuery.iModal.close();" style="float: left;">{% lang 'ShoppingComparisonProductModalCancel' %}</button>';
			buttons   += '<button class="FormButton" type="submit" onclick="return toggleShoppingComparisonSubmit();" style="font-weight:bold;">{% lang 'ShoppingComparisonProductModalSave' %}</button>';

		comparison.find('.description').html("{% lang 'ShoppingComparisonProductModalDescription' %}".replace(':numberOfProducts', getSelectedOrAllProducts().length));

		$.iModal({
			title   : comparison.attr('title'),
			data    : comparison.html(),
			buttons : buttons,
			width	: 385
		});
	}

	/**
	 * Gets called when the modal form is submitted.
	 *
	 * @return false
	 */
	function toggleShoppingComparisonSubmit()
	{
		var products = getSelectedProducts();
		var uncheck  = false;

		// if there are no products selected
		if (!products.length) {
			uncheck = true;

			// get all checkboxes
			products = getAllProducts();

			// check all because jQuery only serializes checkboxes if they're checked
			products.attr('checked', 'checked');
		}

		// add in regular fields
		var fields = $('.ModalContent form select').add(products);
		var serial = fields.serialize();

		if (uncheck) {
			products.removeAttr('checked');
		}

		$.post('index.php?ToDo=bulkSaveProductShoppingComparisonFeeds', serial, function(result) {
			if (result == 1) {
				$.iModal.close();

				display_success('ProductsStatus', "{% lang 'ShoppingComparisonProductMessageSuccess' %}".replace(':numberOfProducts', products.length));
			}
			else {
				display_error('ProductsStatus', "{% lang 'ShoppingComparisonProductMessageError' %}".replace(':numberOfProducts', products.length));
			}
		});

		return false;
	}

	$(document).ready(function() {
		// Hide the product thumbnail row if required
		if ("{{ HideThumbnailField|safe }}" == '1') {
			$('td.ImageField').css('display', 'none');
		}

		// when the bulk options are changed, perform an action
		$('#optionGo').click(function() {
			var sel = $('select[name="bulk"]');
			var raw = sel.get(0);
			var val = sel.val();

			switch (val) {
				case 'delete':
					ConfirmDeleteSelected.apply(raw);
					break;
				case 'edit':
					BulkEditSelected.apply(raw);
					break;
				case 'export':
					ExportProducts.apply(raw);
					break;
				case 'ebay':
					ListProductsOnEbay.apply(raw);
					break;
				case 'shoppingComparison':
					toggleShoppingComparisonFeeds.apply(raw);
					break;
			}

			// reset the select box back to default option
			sel.val('');
		});
	});

</script>
<script type="text/javascript" src="../javascript/jquery/plugins/disabled/jquery.disabled.js"></script>
<script type="text/javascript" src="../javascript/fsm.js"></script>
<script type="text/javascript" src="script/ebay.listproducts.js"></script>
