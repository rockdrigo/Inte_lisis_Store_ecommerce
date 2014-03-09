
	<form action="index.php" id="frmSearch" method="get" onsubmit="return ValidateForm(CheckViewForm)">
	<input type="hidden" name="ToDo" value="searchProductsRedirect" />
	<div class="BodyContainer">
	<table class="OuterPanel">
	  <tr>
		<td class="Heading1" id="tdHeading">{% lang 'CreateNewProductView' %}</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{% lang 'ProductViewIntro' %}</p>
			{{ Message|safe }}
			<p><input type="submit" name="SubmitButton1" value="{% lang 'Save' %}" class="FormButton">&nbsp; <input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()"></p>
		</td>
	  </tr>
		<tr>
			<td>
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'ViewDetails' %}</td>
				</tr>
				<tr><td class="Gap"></td></tr>
				<tr>
					<td class="FieldLabel">
						<span class="Required">*</span>&nbsp;{% lang 'NameThisView' %}:
					</td>
					<td>
						<input type="text" id="viewName" name="viewName" class="Field250">
						<img onmouseout="HideHelp('d2');" onmouseover="ShowHelp('d2', '{% lang 'NameThisView' %}', '{% lang 'NameThisProductViewHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d2"></div>
					</td>
				</tr>
				<tr><td class="Gap" colspan="2"></td></tr>
			 </table>
			</td>
		</tr>
		<tr>
			<td>
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'AdvancedSearch' %}</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'SearchKeywords' %}:
					</td>
					<td>
						<input type="text" id="searchQuery" name="searchQuery" class="Field250">
						<img onmouseout="HideHelp('d1');" onmouseover="ShowHelp('d1', '{% lang 'SearchKeywords' %}', '{% lang 'SearchKeywordsProductHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d1"></div>
					</td>
				</tr>

				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'BrandName' %}:
					</td>
					<td>
						<select name="brand" id="brand" class="Field250">
							<option value="" selected="selected">{% lang 'AllBrandNames' %}</option>
							{{ BrandNameOptions|safe }}
						</select>
					</td>
				</tr>

				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'Categories' %}:
					</td>
					<td>
						<select size="5" id="category" name="category[]" class="Field250 ISSelectReplacement" style="height:115" multiple>
							<option value="0" selected="selected">{% lang 'AllCategories' %}</option>
							{{ CategoryOptions|safe }}
						</select>
						<br />
						<div style="clear: left;"><label><input type="checkbox" name="subCats" value="1" checked="checked" /> {% lang 'AutoSearchSubCategories' %}</label></div>
					</td>
				</tr>

				<tr><td class="Gap" colspan="2"></td></tr>
			 </table>
			</td>
		</tr>
		<tr>
			<td>
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'SearchByRange' %}</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'PriceRange' %}:
					</td>
					<td>
						{% lang 'SearchFrom' %} {{ CurrencyTokenLeft|safe }}<input type="text" id="priceFrom" name="priceFrom" class="Field50"> {{ CurrencyTokenRight|safe }} {% lang 'SearchTo' %}
						{{ CurrencyTokenLeft|safe }}<input type="text" id="priceTo" name="priceTo" class="Field50"> {{ CurrencyTokenRight|safe }}
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'ProductSoldCount' %}:
					</td>
					<td>
						{% lang 'SearchFrom' %} &nbsp;&nbsp;<input type="text" id="soldFrom" name="soldFrom" class="Field50"> {% lang 'SearchTo' %}
						&nbsp;&nbsp;<input type="text" id="soldTo" name="soldTo" class="Field50">
					</td>
				</tr>
				<tr style="display: {{ HideInventoryOptions|safe }}">
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'InventoryLevel' %}:
					</td>
					<td>
						{% lang 'SearchFrom' %} &nbsp;&nbsp;<input type="text" id="inventoryFrom" name="inventoryFrom" class="Field50"> {% lang 'SearchTo' %}
						&nbsp;&nbsp;<input type="text" id="inventoryTo" name="inventoryTo" class="Field50">
						<br />
						<label><input type="checkbox" name="inventoryLow" value="1" /> {% lang 'SearchLowInventory' %}</label>
					</td>
				</tr>
				<tr><td class="Gap" colspan="2"></td></tr>
			 </table>
			</td>
		</tr>
		<tr>
			<td>
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'SearchBySetting' %}</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'ProductVisibility' %}:
					</td>
					<td>
						<select name="visibility" id="visibility" class="Field250">
							<option value="">{% lang 'NoPreference' %}</option>
							<option value="1">{% lang 'VisibleOnly' %}</option>
							<option value="0">{% lang 'InvisibleOnly' %}</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'FeaturedProduct' %}:
					</td>
					<td>
						<select name="featured" id="featured" class="Field250">
							<option value="">{% lang 'NoPreference' %}</option>
							<option value="1">{% lang 'FeaturedOnly' %}</option>
							<option value="0">{% lang 'NotFeaturedOnly' %}</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'FreeShipping' %}:
					</td>
					<td>
						<select name="freeShipping" id="freeShipping" class="Field250">
							<option value="">{% lang 'NoPreference' %}</option>
							<option value="1">{% lang 'FreeShippingOnly' %}</option>
							<option value="0">{% lang 'NonFreeShippingOnly' %}</option>
						</select>
					</td>
				</tr>
				<tr><td class="Gap" colspan="2"></td></tr>
			 </table>
			</td>
		</tr>
		<tr>
			<td>
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'SortOrder' %}</td>
				</tr>
				<tr><td class="Gap"></td></tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'SortOrder' %}:
					</td>
					<td>
						<select name="sortField" class="Field120">
							<option value="productid">{% lang 'ProductID' %}</option>
							<option value="prodcode">{% lang 'ProductSKU' %}</option>
							<option value="prodcurrentinv">{% lang 'ProductInStock' %}</option>
							<option value="prodname">{% lang 'ProductName' %}</option>
							<option value="prodcalculatedprice">{% lang 'ProductPrice' %}</option>
							<option value="prodvisble">{% lang 'ProductVisible' %}</option>
						</select>
						in&nbsp;
						<select name="sortOrder" class="Field110">
						<option value="asc">{% lang 'AscendingOrder' %}</option>
						<option value="desc">{% lang 'DescendingOrder' %}</option>
					</td>
				</tr>
				<tr>
					<td class="Gap">&nbsp;</td>
					<td class="Gap"><input type="submit" name="SubmitButton1" value="{% lang 'Save' %}" class="FormButton">&nbsp; <input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()">
					</td>
				</tr>
				<tr><td class="Gap" colspan="2"></td></tr>
			 </table>
			</td>
		</tr>
	</table>
	</div>
	</form>

	<script type="text/javascript">
		function ConfirmCancel() {
			if(confirm("{% lang 'ConfirmCancelSearch' %}"))
				document.location.href = "index.php?ToDo=viewProducts";
		}

		function CheckViewForm() {
			var viewName = g("viewName");
			var priceFrom = g("priceFrom");
			var priceTo = g("priceTo");
			var inventoryFrom = g("inventoryFrom");
			var inventoryTo = g("inventoryTo");
			var soldFrom = g("soldFrom");
			var soldTo = g("soldTo");

			if(viewName.value == "") {
				alert("{% lang 'EnterViewName' %}");
				viewName.focus();
				return false;
			}

			if(priceFrom.value != "" && isNaN(priceFormat(priceFrom.value))) {
				alert("{% lang 'SearchEnterValidPrice' %}");
				priceFrom.focus();
				priceFrom.select();
				return false;
			}

			if(priceTo.value != "" && isNaN(priceFormat(priceTo.value))) {
				alert("{% lang 'SearchEnterValidPrice' %}");
				priceTo.focus();
				priceTo.select();
				return false;
			}

			if(inventoryFrom.value != "" && isNaN(inventoryFrom.value)) {
				alert("{% lang 'SearchEnterValidInventory' %}");
				inventoryFrom.focus();
				inventoryFrom.select();
				return false;
			}

			if(inventoryTo.value != "" && isNaN(inventoryTo.value)) {
				alert("{% lang 'SearchEnterValidInventoryLvl' %}");
				inventoryTo.focus();
				inventoryTo.select();
				return false;
			}

			if(soldFrom.value != "" && isNaN(soldFrom.value)) {
				alert("{% lang 'SearchEnterValidSold' %}");
				soldFrom.focus();
				soldFrom.select();
				return false;
			}

			if(soldTo.value != "" && isNaN(soldTo.value)) {
				alert("{% lang 'SearchEnterValidQtySold' %}");
				soldTo.focus();
				soldTo.select();
				return false;
			}

			return true;
		}

	</script>
