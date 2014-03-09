
	<form action="index.php?ToDo={{ FormAction|safe }}" id="frmCustomerGroup" onsubmit="return ValidateForm(CheckCustomerGroupForm)" method="post">
	<input type="hidden" name="groupId" value="{{ GroupId|safe }}">

	<div id="hiddenDiscountFields" style="display:none;">
		{{ HiddenDiscounts|safe }}
	</div>

	<div class="BodyContainer">
	<table class="OuterPanel">
	  <tr>
		<td class="Heading1" id="tdHeading">{{ Title|safe }}</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{% lang 'CustomerGroupsIntro' %}</p>
			{{ Message|safe }}
			<p><input type="submit" name="SubmitButton1" value="{% lang 'Save' %}" class="FormButton">&nbsp; <input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()"></p>
		</td>
	  </tr>
		<tr>
			<td>
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'CustomerGroupDetails' %}</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						<span class="Required">*</span>&nbsp;{% lang 'CustomerGroupName' %}:
					</td>
					<td>
						<input type="text" id="groupname" name="groupname" class="Field400" value="{{ GroupName|safe }}">
						<img onmouseout="HideHelp('d1');" onmouseover="ShowHelp('d1', '{% lang 'CustomerGroupName' %}', '{% lang 'GroupNameHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d1"></div>
						<div style="padding-top:5px; font-style:italic; color:gray">{% lang 'CustomerGroupNameSuggestion' %}</div>
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						<span class="Required">*</span>&nbsp;{% lang 'CustomerGroupAccess' %}:
					</td>
					<td>
						<input type="checkbox" name="accesscategories" id="accesscategories" {{ AccessAllCategories|safe }} /> <label for="accesscategories">{% lang 'CustomerGroupAllAccess' %}</label>
						<span id="accesscatssel" style="display:{{ HideAccessCatLinks|safe }}">(<a href="#" id="selectAllCats">{% lang 'SelectAll' %}</a> / <a href="#" id="unselectAllCats">{% lang 'UnselectAll' %}</a>)</span>
						<img onmouseout="HideHelp('d4');" onmouseover="ShowHelp('d4', '{% lang 'CustomerGroupAccess' %}', '{% lang 'CustomerGroupAccessHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d4"></div><br />
						<div style="padding-top:5px; display:{{ HideAccessCategories|safe }}" id="accesscategorylist">
							<img src="images/nodejoin.gif" width="20" height="20" style="float:left; margin-right: 5px"/>
							<select size="5" id="accesscategorieslist" name="accesscategorieslist[]" class="Field400 ISSelectReplacement" multiple="multiple" style="height: 140px">
							{{ AccessCategoryOptions|safe }}
							</select>
						</div>
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;
					</td>
					<td>
						<input type="checkbox" id="isdefault" name="isdefault" value="ON" {{ IsDefault|safe }}> <label for="isdefault">{% lang 'YesMakeCustomerGroupDefault' %}</label>
						<img onmouseout="HideHelp('d3');" onmouseover="ShowHelp('d3', '{% lang 'CustomerGroupDefault' %}', '{% lang 'CustomerGroupDefaultHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d3"></div>
					</td>
				</tr>
			 </table>
			 <br />
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'CategoryLevelDiscounts' %}</td>
				</tr>
				<tr>
					<td style="padding-left:10px">
						<div class="GridContainer" id="categoryGridContainer" style="display:{{ HideCategoryGridContainer|safe }}">
							{{ CategoryDataGrid|safe }}
						</div>
						<div id="nocategoryrules" style="padding-top:5px; font-style:italic; color:gray; display:{{ HideNoCatgeory|safe }}">{% lang 'NoCategoryLevelDiscounts' %} <a href="#" onclick="addDiscountRule('category'); return false;">{% lang 'CreateOneNow' %}</a></div>
					</td>
				</tr>
			 </table>
			 <br />
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'ProductLevelDiscounts' %}</td>
				</tr>
				<tr>
					<td style="padding-left:10px">
						<div class="GridContainer" id="productGridContainer" style="display:{{ HideProductGridContainer|safe }}">
							{{ ProductDataGrid|safe }}
						</div>
						<div id="noproductrules" style="padding-top:5px; font-style:italic; color:gray; display:{{ HideNoProduct|safe }}">{% lang 'NoProductLevelDiscounts' %} <a href="#" onclick="addDiscountRule('product'); return false;">{% lang 'CreateOneNow' %}</a></div>
					</td>
				</tr>
			 </table>
			 <br />
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'StorewideDiscount' %}</td>
				</tr>
				<tr>
					<td style="padding-left:10px">
						<div style="padding-top:5px">
							{% lang 'CustomerGroupsOtherProductsDiscount' %}


							<span id="storeDiscountRulesAmountPrefix">$</span>
							<input type="text" id="discount" name="discount" class="Field50" style="width:30px" value="{{ Discount|safe }}">
							<span id="storeDiscountRulesAmountPostfix"></span>

							<select id="storeDiscountMethod" name="storeDiscountMethod" class="Field120" onchange="ToggleDiscountRateValueType('', 'store');">
								<option value="price" {{ StoreDiscountMethodPrice|safe }}>{% lang 'PriceDiscount' %}</option>
								<option value="percent" {{ StoreDiscountMethodPercent|safe }}>{% lang 'PercentageDiscount' %}</option>
								<option value="fixed" {{ StoreDiscountMethodFixed|safe }}>{% lang 'FixedPrice' %}</option>
							</select>

							<img onmouseout="HideHelp('d2');" onmouseover="ShowHelp('d2', '{% lang 'StorewideDiscount' %}', '{% lang 'StorewideDiscountHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display:none" id="d2"></div>
						</div>
					</td>
				</tr>
			 </table>
			</td>
		</tr>
	</table>
	<p><input type="submit" name="SubmitButton1" value="{% lang 'Save' %}" class="FormButton">&nbsp; <input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()"></p>
	</div>
	</form>

	<script type="text/javascript">

	var discountsPerPage = {{ DiscountsPerPage|safe }};
	var currentPage = 1;
	var amountPrefix = "{{ AmountPrefix|safe }}";
	var amountPostfix = "{{ AmountPostfix|safe }}";

	lang.CustomerGroupsInvalid_category = "{% lang 'CustomerGroupsInvalidCatgeory' %}";
	lang.CustomerGroupsInvalid_product = "{% lang 'CustomerGroupsInvalidProduct' %}";
	lang.CustomerGroupMultiDiscount_category = "{% lang 'CustomerGroupMultiCatDiscount' %}";
	lang.CustomerGroupMultiDiscount_product = "{% lang 'CustomerGroupMultiProdDiscount' %}";
	lang.CustomerGroupsInvalidAmount_category = "{% lang 'CustomerGroupsInvalidCategoryAmount' %}";
	lang.CustomerGroupsInvalidAmount_product = "{% lang 'CustomerGroupsInvalidProductAmount' %}";

	function getGroupDiscountDataBlock(page, url)
	{
		if (typeof(page) == 'undefined' || page < 1) {
			page = 1;
		}

		currentPage = page;

		var type, typeMatch = url.match(/[\?|\&]type=([^&$]+)/);

		if (typeMatch !== null && typeof(typeMatch[1]) !== 'undefined') {
			type = typeMatch[1];
		} else {
			return '';
		}

		var startId = ((page - 1) * discountsPerPage) + 1;
		var endId = page * discountsPerPage;
		var items = getHiddenDiscounts(type, true);
		var i = 0;
		var data = {
			'type': type,
			'total': items.length
		}

		for (var i=0; i<items.length; i++) {
			if (items[i].discountid >= startId && items[i].discountid <= endId) {
				data['items[' + i + '][discountid]'] = items[i].discountid;
				data['items[' + i + '][discounttype]'] = items[i].discounttype;
				data['items[' + i + '][catorprodid]'] = items[i].catorprodid;
				data['items[' + i + '][discountpercent]'] = items[i].discountpercent;
				data['items[' + i + '][appliesto]'] = items[i].appliesto;
				data['items[' + i + '][discountmethod]'] = items[i].discountmethod;
			}
		}

		return data;
	}

	function remapHiddenDiscounts(type, id)
	{
		if (type !== 'product' && type !== 'category') {
			return;
		}

		var search;

		if (typeof(id) !== 'undefined') {
			search = '#' + type + '_discountid_' + id;
		} else {
			search = '.hidden' + type + 'RecordId';
		}

		$(search).each(
			function()
			{
				var id = $(this).val();

				$('#' + type + '_catorprodid_' + id).val($('#' + type + 'RuleValue' + id).val());
				$('#' + type + '_discountpercent_' + id).val($('#' + type + 'RuleDiscount' + id).val());
				$('#' + type + '_appliesto_' + id).val($('#' + type + 'RuleAppliesTo' + id).val());
				$('#' + type + '_discountmethod_' + id).val($('#' + type + 'DiscountMethod' + id).val());
			}
		);
	}

	function getHiddenDiscounts(type, reload)
	{
		if (type !== 'product' && type !== 'category') {
			return false;
		}

		if (typeof(reload) !== 'undefined' && reload) {
			remapHiddenDiscounts(type);
		}

		var data = [];

		$('.hidden' + type + 'RecordId').each(
			function()
			{
				var id = $(this).val();

				data[data.length] = {
					'discountid': id,
					'discounttype': type,
					'catorprodid': $('#' + type + '_catorprodid_' + id).val(),
					'discountpercent': $('#' + type + '_discountpercent_' + id).val(),
					'appliesto': $('#' + type + '_appliesto_' + id).val(),
					'discountmethod': $('#' + type + '_discountmethod_' + id).val()
				}
			}
		);

		return data;
	}

	function ShowProductSelector(id, hiddenId, qtyInput) {
		openProductSelect('group', id, hiddenId, 1, qtyInput);
	}

	function ToggleDiscountRateValueType(id, ruleType)
	{
		if ($('#'+ruleType+'DiscountMethod' + id).val() == 'percent') {
			$('#'+ruleType+'DiscountRulesAmountPrefix' + id).html('');
			$('#'+ruleType+'DiscountRulesAmountPostfix' + id).html('%');
		} else {
			$('#'+ruleType+'DiscountRulesAmountPrefix' + id).html(amountPrefix);
			$('#'+ruleType+'DiscountRulesAmountPostfix' + id).html(amountPostfix);
		}

		if(ruleType=='category') {
			if ($('#'+ruleType+'DiscountMethod' + id).val() == 'fixed') {
				$('#'+ruleType+'DiscountRulesLineEnding' + id).html(' {% lang 'For' %} ');
			} else {
				$('#'+ruleType+'DiscountRulesLineEnding' + id).html(' {% lang 'OffSmall' %} ');
			}
		}

		if (ruleType !== 'store') {
			remapHiddenDiscounts(ruleType, id);
		}
	}

	function addDiscountRule(type)
	{
		if (type !== 'product' && type !== 'category') {
			return;
		}

		var total = $('.hidden' + type + 'RecordId').size();

		$.ajax({
			'url': 'remote.php',
			'type': 'post',
			'data': {
				'w': 'adddiscountrule',
				'remoteSection': 'customers',
				'discountId': total+1,
				'type': type
				},
			'success': addDiscountRuleCallback
		});
	}

	function addDiscountRuleCallback(data)
	{
		var type = $('type', data).text();
		var hidden = $('hidden', data).text();
		var newPage = currentPage;

		if ($('status', data).text() == '0') {
			return;
		}

		$('#hiddenDiscountFields').append(hidden);
		remapHiddenDiscounts(type);

		var total = $('.hidden' + type + 'RecordId').size();

		/**
		 * Are we in a new page now?
		 */
		if (total > (discountsPerPage * currentPage)) {
			newPage = Math.ceil(total / discountsPerPage);
		}

		if (total == 1) {
			$('#no' + type + 'rules').hide('slow');
			$('#' + type + 'GridContainer').show('slow', function() {navToDiscountPage(type, newPage)});
		} else {
			navToDiscountPage(type, newPage)
		};
	}

	function removeDiscountRule(type, id)
	{
		if ((type !== 'product' && type !== 'category') || id == '') {
			alert(type + ' ' + id);
			return;
		}

		$('#' + type + '_discountid_' + id).remove();
		$('#' + type + '_discounttype_' + id).remove();
		$('#' + type + '_catorprodid_' + id).remove();
		$('#' + type + '_discountpercent_' + id).remove();
		$('#' + type + '_appliesto_' + id).remove();
		$('#' + type + '_discountmethod_' + id).remove();
		$('#' + type + 'Rule' + id).remove();

		var total = $('.hidden' + type + 'RecordId').size();
		var newPage = currentPage;
		var hiddenNames = ['discountid', 'discounttype', 'catorprodid', 'discountpercent', 'appliesto', 'discountmethod'];
		var frontNames = ['Rule','RuleValue','RuleDiscount','DiscountMethod','RuleAppliesTo'];

		/**
		 * Decrement the IDs
		 */
		$('.hidden' + type + 'RecordId').each(
			function()
			{
				var thisId = $(this).val();

				if (thisId > id) {
					for (var i=0; i<hiddenNames.length; i++) {
						$('#' + type + '_' + hiddenNames[i] + '_' + thisId).attr('name', 'discountlist[' + type + '][' + (thisId-1) + '][' + hiddenNames[i] + ']');
						$('#' + type + '_' + hiddenNames[i] + '_' + thisId).attr('id', type + '_' + hiddenNames[i] + '_' + (thisId-1));
					}

					$('#' + type + '_discountid_' + (thisId-1)).val(thisId-1);

					for (var i=0; i<frontNames.length; i++) {
						$('#' + type + frontNames[i] + thisId).attr('id', type + frontNames[i] + (thisId-1));
					}
				}
			}
		);

		remapHiddenDiscounts(type);

		/**
		 * Have we deleted all the records for that page?
		 */
		if (total < (discountsPerPage * currentPage)) {
			newPage = currentPage-1;
		}

		if (total == 0) {
			$('#' + type + 'GridContainer').hide('slow', function() {navToDiscountPage(type, newPage)});
			$('#no' + type + 'rules').show('slow');
		} else {
			navToDiscountPage(type, newPage);
		}
	}

	function navToDiscountPage(type, navToPage)
	{
		if ($('#discountHiddenNavLink').size() == 0) {
			$('#' + type + 'GridContainer td.PagingNav:first').append('<a href="#" id="discountHiddenNavLink" style="display:none"></a>');
			$('#discountHiddenNavLink').click(AjaxSortClick);
		}

		$('#discountHiddenNavLink').attr('href', 'index.php?ToDo=viewCustomerGroupDiscounts&page=' + navToPage + '&type=' + type + '&precall=getGroupDiscountDataBlock');
		$('#discountHiddenNavLink').click();
	}

	function ConfirmCancel() {
		if(confirm("{% lang 'ConfirmCancelCustomerGroup' %}")) {
			document.location.href = "index.php?ToDo=viewCustomerGroups";
		}
	}

	function getGroupAccessCategories()
	{
		var access = $('#accesscategorieslist input:checked');
		var cats = [];

		for (var i=0; i<access.length; i++) {
			cats[cats.length] = access[i].value;
		}

		return cats;
	}

	function CheckCustomerGroupForm() {
		if($('#groupname').val() == '') {
			alert("{% lang 'CustomerGroupEnterName' %}");
			$('#groupname').select();
			return false;
		}

		if (!$('#accesscategories').attr('checked') && getGroupAccessCategories().length < 1) {
			alert("{% lang 'CustomerGroupsEmptyCategoryList' %}");
			$('#accesscategories').select();
			return false;
		}

		if (isNaN(priceFormat($('#discount').val())) || $('#discount').val() == '') {
			$('#discount').focus().select();
			alert('{% lang 'CustomerGroupEnterDiscount' %}');
			return false;
		} else if ( $('#storeDiscountMethod').val() == 'percent' && (parseInt($('#discount').val()) < 0 || parseInt($('#discount').val()) > 100)) {
			$('#discount').focus().select();
			alert("{% lang 'CustomerGroupEnterValidDiscount' %}");
			return false;
		}

		var discounts = {
			'category': getHiddenDiscounts('category', true),
			'product': getHiddenDiscounts('product', true)
		};

		for (var type in discounts) {
			if (type !== 'category' && type !== 'product') {
				continue;
			}

			var newPage, cache = [];
			var errStatus = false;
			var errMsg = '';

			for (var i=0; i<discounts[type].length; i++) {
				if (discounts[type][i].catorprodid == '' || isNaN(discounts[type][i].catorprodid)) {
					errStatus = true;
					errMsg = lang['CustomerGroupsInvalid_' + type];
				}

				if (!errStatus && cache.in_array(discounts[type][i].catorprodid)) {
					errStatus = true;
					errMsg = lang['CustomerGroupMultiDiscount_' + type];
				}

				cache[cache.length] = discounts[type][i].catorprodid;

				if (!errStatus && parseInt(discounts[type][i].discountpercent) < 0 || isNaN(discounts[type][i].discountpercent)) {
					errStatus = true;
					errMsg = lang['CustomerGroupsInvalidAmount_' + type];
				}

				if (!errStatus && discounts[type][i].discountmethod == 'percent' && (parseInt(discounts[type][i].discountpercent) < 0 || parseInt(discounts[type][i].discountpercent) > 100)) {
					errStatus = true;
					errMsg = lang['CustomerGroupsInvalidAmount_' + type];
				}

				if (errStatus) {
					newPage = Math.ceil(discounts[type][i].discountid / discountsPerPage)
					navToDiscountPage(type, newPage);
					alert(errMsg.replace(/\%d/, discounts[type][i].discountid));
					return false;
				}
			}
		}

		return true;
	}

	// Show or hide the access categories list as required
	$('#accesscategories').click(function() {
		if((this).checked) {
			$('#accesscategorylist').hide();
			$('#accesscatssel').hide();
		}
		else {
			$('#accesscategorylist').show();
			$('#accesscatssel').show();
		}
	});

	// Select all access categories
	$('#selectAllCats').click(function() {
		if(g('accesscategorieslist_old')) {
			if(g('accesscategorieslist_old').disabled != true) {
				$('#accesscategorieslist input').attr('checked', false);
				$('#accesscategorieslist li').trigger('click');
			}
		}
		else {
			$('#accesscategorieslist option').attr('selected', true);
		}
		return false;
	});

	$('#unselectAllCats').click(function() {
		if(g('accesscategorieslist_old')) {
			if(g('accesscategorieslist_old').disabled != true) {
				$('#accesscategorieslist input').attr('checked', true);
				$('#accesscategorieslist li').trigger('click');
			}
		}
		else {
			$('#accesscategorieslist option').attr('selected', false);
		}
		return false;
	});

	$(document).ready(function() {
		$('#groupname').focus();

		var StoreDiscountMethod = '{{ StoreDiscountMethod|safe }}';
		$("#storeDiscountMethod_ option[value='"+StoreDiscountMethod+"']").attr("selected","selected");;
		ToggleDiscountRateValueType('', 'store');
	});

	</script>
