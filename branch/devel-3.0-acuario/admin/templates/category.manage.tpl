<link rel="stylesheet" href="Styles/categories.css" type="text/css" />

<div class="BodyContainer">
<table class="OuterPanel">
<tr>
	<td class="Heading1">{% lang 'ViewCategories' %}</td>
</tr>
<tr>
	<td>
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td class="Intro">
				<p>
				{% lang 'ManageCatIntro' %}
				</p>
				{{ Message|safe }}
			</td>
		</tr>
		<tr><td><div id="CategoriesStatus" style="margin-bottom: 10px;"></div></td></tr>
		<tr>
			<td class="Intro" style="padding-bottom:10px">
				<input type="button" onclick="document.location.href='index.php?ToDo=createCategory'" name="createNewCategory" value="{% lang 'CreateCategory' %}..." class="Button">
				{% if ShoppingComparisonModules|length %}
				&nbsp;
				{% lang 'Or' %}
				<select name="bulk" {% if not CategoryList %}disabled="disabled"{% endif %}>
					<option value="">{% lang 'ChooseAnAction' %}</option>
					<option class="delete" {{ DisableDelete|safe }}>{% lang 'DeleteSelected' %}</option>
					{% for module in ShoppingComparisonModules %}
					<option class="mapcategories" value="{{module.getId()}}">{% lang 'BulkUpdateShoppingComparisonCategory' with ['name' : module.getName]%}</option>
					{% endfor %}
				</select>
				<button type="button" id="optionGo" {% if not CategoryList %}disabled="disabled"{% endif %}>{% lang 'Go' %}</button>
				{% else %}
				<input type="button" value="{% lang 'DeleteSelected' %}" id="indexDeleteButton" class="SmallButton" {{ DisableDelete|safe }} />
				{% endif %}
			</td>
		</tr>
		</table>
	</td>
</tr>
<tr>
	<td style="padding-top: 10px;">
		<form method="post" id="frmCategories" onSubmit="return false" action="index.php?ToDo=deleteCategory">
			<table class="GridPanel SortablePanel" cellspacing="0" cellpadding="0" border="0" style="width:100%; display: {{ DisplayGrid|safe }}">
				<tr class="Heading3">
					<td style="padding-left: 5px;" width="1"><input type="checkbox" id="deleteAllToggle"></td>
					<td class="NoWrap">{% lang 'CategoryName' %}</td>
					<td width="80" class="NoWrap">{% lang 'CategoryProducts' %}</td>
					<td width="120" class="NoWrap">{% lang 'ProductsInSubCategories' %}</td>
					<td width="120">
						<span onmouseover="ShowQuickHelp(this, '{% lang 'VisibleInMenuList' %}', '{% lang 'CategoryVisibilityHelp' %}');" onmouseout="HideQuickHelp(this);" class="HelpText"><span class="NoWrap">{% lang 'VisibleInMenuList' %}</span></span>
					</td>
					{% if ShoppingComparisonModules|length %}
					<td width="120" class="NoWrap">{% lang 'CategoryMappings' %}</td>
					{% endif %}
					<td width="185" class="NoWrap">
						{% lang 'Action' %}
					</td>
				</tr>
			</table>
			{{ CategoryList|safe }}
		</form>
	</td>
</tr>
</table>

{% include 'category.select.modal.tpl' %}

	<div id="reassignModal" style="display: none;">
		<div class="ModalTitle">{% lang 'ReassignModalTitle' %}</div>
		<div class="ModalContent">
			<table class="Panel" width="100%">
				<tr>
					<td id="reassignCategoryModalIntro">{% lang 'ReassignModalIntro' %}</td>
				</tr>
				<tr>
					<td>
						<input type="radio" name="reassignOption" value="reassign" id="reassignOption1" checked="checked"/>
						{% lang 'ReassignModalOption1' %}
						<select name="parentidSelect" size="8" id="parentidSelect" style="width: 98%;margin:5px" />
					</td>
				</tr>
				<tr>
					<td>
						<input type="radio" name="reassignOption" value="delete" id="reassignOption2" />
						{% lang 'ReassignModalOption2' %}
					</td>
				</tr>
			</table>
		</div>
		<div class="ModalButtonRow">
			<div class="FloatLeft">
				<img src="images/loading.gif" alt="" style="vertical-align: middle; display: none;" class="LoadingIndicator" />
				<input type="button" class="CloseButton FormButton" value="{% lang 'Cancel' %}" id="cancelStep1" />
			</div>
			<input type="button" class="saveButton" value="{% lang 'Continue' %} >>" id="continueStep1" />
		</div>
	</div>
	<div id="confirmModal" style="display: none;">
		<div class="ModalTitle">{% lang 'ConfirmModalTitle' %}</div>
		<div class="ModalContent">
			<table class="Panel" width="100%">
				<tr>
					<td id="confirmModalIntro"></td>
				</tr>
			</table>
		</div>
		<div class="ModalButtonRow">
			<div class="FloatLeft">
				<img src="images/loading.gif" alt="" style="vertical-align: middle; display: none;" class="LoadingIndicator" />
				<input type="button" class="CloseButton FormButton" value="{% lang 'Cancel' %}" id="cancelStep2" />
			</div>
			<input type="button" class="saveButton" value="{% lang 'ConfirmModalButton' %}" id="continueStep2" />
		</div>
	</div>

</div>
<script type="text/javascript">
	lang.categoryVisibleConfirmation = "{% jslang 'CategoryVisibleConfirmation' %}";
	lang.categoryInvisibleConfirmation = "{% jslang 'CategoryInvisibleConfirmation' %}";
	lang.visiblityNotChanged = "{% jslang 'ErrCategoryVisibilityNotChanged' %}";
	lang.clickToHideCategory = "{% jslang 'ClickToHideCategory' %}";
	lang.clickToShowCategory = "{% jslang 'ClickToShowCategory' %}";
	lang.chooseCategoryToDelete = "{% jslang 'ChooseCategoryToDelete' %}";
	lang.chooseCategoryToUpdate = "{% jslang 'ChooseCategoryToUpdate' %}";
	lang.confirmDeleteCategories = "{% jslang 'ConfirmDeleteCategories' %}";
	lang.reassignModalIntro = "{% jslang 'ReassignModalIntro' %}";
	lang.confirmModalIntro = "{% jslang 'ConfirmModalIntro1' %}" + "{% jslang 'ConfirmModalIntro2' %}" + "{% jslang 'ConfirmModalIntro3' %}";
	lang.confirmCancel = "{% lang 'ConfirmCancel' %}";
	lang.chooseThisCategory = "{% jslang 'ChooseThisCategory' %}";
	lang.categorySelectModalIntro = "{% jslang 'CategoryMappingModalIntro' %}";
	lang.categorySelectModalTitle = "{% jslang 'CategoryMappingModalTitle' %}";
	lang.categorySelectLeafCategorySelected = "{% jslang 'CategoryMappingLeafCategorySelected' %}";
	lang.categorySelectChooseLeafCategory = "{% jslang 'CategoryMappingChooseLeafCategory' %}";
</script>

<script type="text/javascript" src="../javascript/jquery/plugins/jquery.ui.nestedSortable.js?{{ JSCacheToken }}"></script>
<script type="text/javascript" src="script/category.manager.js?{{ JSCacheToken }}"></script>
<script type="text/javascript" src="script/category.selector.js?{{ JSCacheToken }}"></script>

<script language="javascript" type="text/javascript">//<![CDATA[
$(function(){
	CategoryManager.shoppingComparisonModules = {};

	{% for module in ShoppingComparisonModules %}
		CategoryManager.shoppingComparisonModules["{{ module.getId|js }}"] = {
			name: "{{ module.getName|js }}"
		};
	{% endfor %}
});
//]]></script>
