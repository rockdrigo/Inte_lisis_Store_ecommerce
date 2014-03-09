<form enctype="multipart/form-data" action="index.php?ToDo=saveBulkEditProducts" onsubmit="return ValidateForm(CheckBulkEditProductForm)" id="frmProduct" method="post">
<input type="hidden" name="product_ids" value="{{ ProductIds|safe }}" />
<div class="BodyContainer">
	<table cellSpacing="0" cellPadding="0" width="100%" style="margin-left: 4px; margin-top: 8px;">
	<tr>
		<td class="Heading1">{% lang 'BulkEditProducts1' %}</td>
	</tr>
	<tr>
		<td class="Intro">
			<p>{% lang 'BulkEditIntro' %}</div>
			{{ Message|safe }}
			<p>
				<input type="submit" value="{% lang 'SaveAndExit' %}" class="FormButton" />
				<input type="submit" value="{% lang 'SaveAndContinueEditing' %}" onclick="SaveAndKeepEditing()" class="FormButton" style="width:130px" />
				<input type="reset" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
			</p>
		</td>
	</tr>
	<tr>
		<td>
			<table class="GridPanel SortableGrid AutoExpand" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%;">
				<tr class="Heading3">
					<td>&nbsp;</td>
					<td style="width:20%"><span class="Required">*</span> {% lang 'ProductName' %}</td>
					<td style="width:80px"><span class="Required">*</span> {% lang 'Price' %}</td>
					<td style="width:210px"><span class="Required">*</span> {% lang 'Categories' %}</td>
					<td style="width:80px">{% lang 'Brand' %}</td>
					<td style="width:80px">{% lang 'Visible' %}</td>
					<td style="width:80px">{% lang 'Featured' %}</td>
					<td style="width:80px">{% lang 'FreeShipping' %}</td>
				</tr>
				<tr class="GridRow">
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td><a href="#" onclick="ChangeAllPrices(); return false;">{% lang 'ChangeAll' %}</a></td>
					<td><a href="#" onclick="ExpandAllCategories(); return false;">{% lang 'ExpandAllCategories' %}</a> / <a href="#" onclick="CollapseAllCategories(); return false;">{% lang 'CollapseAllCategories' %}</a></td>
					<td><a href="#" onclick="ChangeAllBrands(); return false;">{% lang 'ChangeAll' %}</a></td>
					<td><input type="checkbox" id="change_all_visible" /></td>
					<td><input type="checkbox" id="change_all_featured" /></td>
					<td><input type="checkbox" id="change_all_freeshipping" /></td>
				</tr>
				{{ ProductList|safe }}
			</table>
			<table border="0" cellspacing="0" cellpadding="2" width="100%" class="PanelPlain">
				<tr>
					<td>
						<input type="submit" value="{% lang 'SaveAndExit' %}" class="FormButton" />
						<input type="submit" value="{% lang 'SaveAndContinueEditing' %}" onclick="SaveAndKeepEditing();" class="FormButton" style="width:130px" />
						<input type="reset" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
					</td>
				</tr>
			</table>
		</td>
	</tr>
	</table>
</div>
</form>

<script type="text/javascript">

	function ExpandAllCategories() {
		$('#IndexGrid .ExpandCategoryLink').each(function() {
			if(!$(this).hasClass('Expanded')) {
				ExpandCategories($(this).attr('id').replace('ExpandCategoryLink-', ''));
			}
		});
	}

	function CollapseAllCategories() {
		$('#IndexGrid .ExpandCategoryLink').each(function() {
			if($(this).hasClass('Expanded')) {
				ExpandCategories($(this).attr('id').replace('ExpandCategoryLink-', ''));
			}
		});
	}

	function ExpandCategories(ProductId, Img) {
		if(!$('#ExpandCategoryLink-' + ProductId).hasClass('Expanded')) {
			$('#category_'+ProductId).css('height', '250px');
			$('#catdrop_'+ProductId).attr('src', 'images/collapsearrow.gif');
			$('#ExpandCategoryLink-' + ProductId).addClass('Expanded');
			return;
		}

		$('#category_'+ProductId).css('height', '23px');
		$('#catdrop_'+ProductId).attr('src', 'images/droparrow.gif');
		$('#ExpandCategoryLink-' + ProductId).removeClass('Expanded');
	}

	function ForceExpandCategories(ProductId, Img) {
		$('#category_'+ProductId).css('height', '250px');
		$('#catdrop_'+ProductId).attr('src', 'images/collapsearrow.gif');
		$('#ExpandCategoryLink-' + ProductId).addClass('Expanded');
	}

	function ConfirmCancel() {
		if(confirm("{% lang 'ConfirmCancelBulkEditProduct' %}"))
			document.location.href = "index.php?ToDo=viewProducts";
	}

	function SaveAndKeepEditing() {
		var f = g('frmProduct');
		var d = document.createElement('input');
		d.type = 'hidden';
		d.name = 'keepediting';
		d.value = '1';
		f.appendChild(d);
	}

	function CheckBulkEditProductForm() {
		// Make sure all required fields are completed
		var f = g("frmProduct").elements;
		for(i = 0; i < f.length; i++) {
			if(f[i].id.indexOf("prodname_") == 0 && f[i].value == "") {
				alert("{% lang 'BulkEditEnterProductName' %}");
				f[i].focus();
				return false;
			}

			if(f[i].id.indexOf("prodprice_") == 0 && (isNaN(priceFormat(f[i].value)) || f[i].value == "")) {
				alert("{% lang 'BulkEditEnterProductPrice' %}");
				f[i].focus();
				f[i].select();
				return false;
			}

			if(f[i].id.indexOf("category_") == 0 && f[i].selectedIndex == -1) {
				alert("{% lang 'BulkEditNoCats' %}");
				cid = f[i].id;
				cid = cid.replace("category_", "");
				cid = cid.replace("_old", "");
				ForceExpandCategories(cid, g("catdrop_"+cid));
				return false;
			}
		}

		return true;
	}

	function ChangeAllPrices() {
		var f = g("frmProduct").elements;
		var price = prompt("{% lang 'BulkEditNewPrice' %}:");

		if(price != null) {
			if(isNaN(priceFormat(price)) || price == "") {
				alert("{% lang 'BulkEditEnterProductPrice' %}");
				ChangeAllPrices();
			}
			else {
				for(i = 0; i < f.length; i++) {
					if(f[i].id.indexOf("prodprice_") == 0) {
						f[i].value = price;
					}
				}
			}
		}
	}

	function ChangeAllBrands() {
		var f = g("frmProduct").elements;
		var brand = prompt("{% lang 'BulkEditNewBrand' %}:");

		if(brand != null) {
			for(i = 0; i < f.length; i++) {
				if(f[i].id.indexOf("prodbrand_") == 0) {
					f[i].value = brand;
				}
			}
		}
	}

	$('#change_all_visible').click(function() {
		var f = g("frmProduct").elements;
		var visible = $(this).attr('checked');

		for(i = 0; i < f.length; i++) {
			if(f[i].id.indexOf("prodvisible_") == 0) {
				f[i].checked = visible;
			}
		}
	});

	$('#change_all_featured').click(function() {
		var f = g("frmProduct").elements;
		var featured = $(this).attr('checked');

		for(i = 0; i < f.length; i++) {
			if(f[i].id.indexOf("prodfeatured_") == 0) {
				f[i].checked = featured;
			}
		}
	});

	$('#change_all_freeshipping').click(function() {
		var f = g("frmProduct").elements;
		var freeshipping = $(this).attr('checked');

		for(i = 0; i < f.length; i++) {
			if(f[i].id.indexOf("prodfreeshipping_") == 0) {
				f[i].checked = freeshipping;
			}
		}
	});

</script>
