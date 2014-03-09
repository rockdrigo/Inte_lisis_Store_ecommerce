<link rel="stylesheet" href="Styles/categories.css" type="text/css" />
			<form enctype="multipart/form-data" action="index.php?ToDo={{ FormAction|safe }}" name="frmAddCategory" id="frmAddCategory" method="post">
			{{ hiddenFields|safe }}
			<div class="BodyContainer">
			<table class="OuterPanel">
			  <tr>
				<td class="Heading1">{{ CatTitle|safe }}</td>
				</tr>
				<tr>
				<td class="Intro">
					<p>{{ CatIntro|safe }}</p>
					{{ Message|safe }}
				</td>
			  </tr>
			  <tr>
				<td>
					<div>
						<input type="submit" value="{% lang 'SaveAndExit' %}" class="FormButton" />
						<input type="submit" value="{{ SaveAndAddAnother|safe }}" name="AddAnother" class="FormButton" style="width:130px" />						<input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="CategoryCancelButton FormButton">
						<input id="currentTab" name="currentTab" value="details" type="hidden">

						<br /><img src="images/blank.gif" width="1" height="10" />
					</div>
				</td>
			  </tr>
				<tr>
					<td>
						<ul id="tabnav">
							<li><a href="#" class="CategoryFormTab active" id="tab_details">{% lang 'Details' %}</a></li>
							<li><a href="#" class="CategoryFormTab" id="tab_optimizer">{% lang 'GoogleWebsiteOptimizer' %}</a></li>
						</ul>
					</td>
				</tr>

				<tr>
					<td>
					<div id="div_details" style="padding-top: 10px;">
					  <table class="Panel">
						<tr>
						  <td class="Heading2" colspan=2>{% lang 'CatDetails' %}</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span>&nbsp;{% lang 'CatName' %}:
							</td>
							<td>
								<input type="text" name="catname" id="catname" class="Field650" value="{{ CategoryName|safe }}">
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								&nbsp;&nbsp;&nbsp;{% lang 'CatDesc' %}:
							</td>
							<td>
								{{ WYSIWYG|safe }}
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span>&nbsp;{% lang 'CatParentCategory' %}:
								<br/>&nbsp;&nbsp;&nbsp;<small><a href="#" id="expandCategoryList">{% lang 'ExpandCategory' %}</a></small>
							</td>
							<td>
								<select size="10" name="catparentid" id="catparentid" class="Field650">
								{{ CategoryOptions|safe }}
								</select>
								<img onmouseout="HideHelp('d1');" onmouseover="ShowHelp('d1', '{% lang 'CatParentCategory' %}', '{% lang 'CatParentCategoryHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="d1"></div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								&nbsp;&nbsp;&nbsp;{% lang 'TemplateLayoutFile' %}:
							</td>
							<td>
								<select name="catlayoutfile" id="catlayoutfile" class="Field250">
									{{ LayoutFiles|safe }}
								</select>
								<img onmouseout="HideHelp('templatelayout');" onmouseover="ShowHelp('templatelayout', '{% lang 'TemplateLayoutFile' %}', '{% lang 'CategoryTemplateLayoutFileHelp1' %}{{ template|safe }}{% lang 'CategoryTemplateLayoutFileHelp2' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="templatelayout"></div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span>&nbsp;{% lang 'CatSort' %}:
							</td>
							<td>
								<input type="text" name="catsort" id="catsort" class="Field" size="5" value="{{ CategorySort|safe }}">
								<img onmouseout="HideHelp('d2');" onmouseover="ShowHelp('d2', '{% lang 'CatSort' %}', '{% lang 'CatSortHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="d2"></div>
							</td>
						</tr>
						<tr id='CategoryImageRow' style="display:none">
							<td class="FieldLabel">
								&nbsp;&nbsp;&nbsp;{% lang 'CatImage' %}:
							</td>
							<td>
								<input type="file" id="catimagefile" name="catimagefile" class="Field" {{ DisableFileUpload|safe }} />
								<img onmouseout="HideHelp('d3');" onmouseover="ShowHelp('d3', '{% lang 'CatImage' %}', '{% lang 'CatImageHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="d3"></div>
								<div id="YesUseImageRow" style="display:{{ ShowYesUseImageRow|safe }}">
									<label>
										<input type="checkbox" id="YesUseImage" name="YesUseImage" value="1" checked />
										<span>{{ CatImageFile|safe }}</span>
									</label>
									<span id="PreviewCatImage"> - <a title="{% lang 'PreviewCatImage' %}" href="{{ CatImageLink|safe }}" target="_blank">{% lang 'Preview' %}</a></span>
									<img onmouseout="HideHelp('d100');" onmouseover="ShowHelp('d100', '{% lang 'RemoveCatImage' %}', '{% lang 'RemoveCatImageHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
									<div style="display:none" id="d100"></div>
								</div>
							</td>
						</tr>
					</table>
					<table width="100%" class="Panel">
						<tr>
						  <td class="Heading2" colspan=2>{% lang 'SearchEngineOptimization' %}</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								&nbsp;&nbsp;&nbsp;{% lang 'PageTitle' %}:
							</td>
							<td>
								<input type="text" id="catpagetitle" name="catpagetitle" class="Field650" value="{{ CategoryPageTitle|safe }}" />
								<img onmouseout="HideHelp('pagetitlehelp');" onmouseover="ShowHelp('pagetitlehelp', '{% lang 'PageTitle' %}', '{% lang 'CategoryPageTitleHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="pagetitlehelp"></div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								&nbsp;&nbsp;&nbsp;{% lang 'MetaKeywords' %}:
							</td>
							<td>
								<input type="text" id="catmetakeywords" name="catmetakeywords" class="Field650" value="{{ CategoryMetaKeywords|safe }}" />
								<img onmouseout="HideHelp('metataghelp');" onmouseover="ShowHelp('metataghelp', '{% lang 'MetaKeywords' %}', '{% lang 'MetaKeywordsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="metataghelp"></div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								&nbsp;&nbsp;&nbsp;{% lang 'MetaDescription' %}:
							</td>
							<td>
								<input type="text" id="catmetadesc" name="catmetadesc" class="Field650" value="{{ CategoryMetaDesc|safe }}" />
								<img onmouseout="HideHelp('metadeschelp');" onmouseover="ShowHelp('metadeschelp', '{% lang 'MetaDescription' %}', '{% lang 'MetaDescriptionHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="metadeschelp"></div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								&nbsp;&nbsp;&nbsp;{% lang 'SearchKeywords' %}:
							</td>
							<td>
								<input type="text" id="catsearchkeywords" name="catsearchkeywords" class="Field650" value="{{ CategorySearchKeywords|safe }}">
								<img onmouseout="HideHelp('searchkeywords');" onmouseover="ShowHelp('searchkeywords', '{% lang 'SearchKeywords' %}', '{% lang 'SearchKeywordsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="searchkeywords"></div>
							</td>
						</tr>
					 </table>
					{% if  ShoppingComparisonModules|length %}
					<table width="100%" class="Panel" id='comparisons'>
						<tr>
							<td class="Heading2" colspan=2>
								<span class="HelpText" id="ShoppingComparisonCategoryMappingsHeading">
								{% lang 'ShoppingComparisonCategoryMappings' %}
								</span>
							</td>
						</tr>
						{% for module in ShoppingComparisonModules %}
						<tr>
							<td class="FieldLabel">
								&nbsp;&nbsp;&nbsp;{% lang 'ShoppingComparisonFieldLabel' with ['name' : module.getName() | mTruncateSplice(16)] %}:
							</td>
							<td>
								<input type="hidden" class="comparisoncategory" name="{{ module.getId() }}_categoryid" id="{{ module.getId() }}" value="{{ AlternateCategoriesCache[module.getId()].categoryid }}"/>
								<input type="hidden" class="comparisoncategory_path" name="{{ module.getId() }}_categorypath" value=""/>
								<input type="text" class="Field500 comparisoncategory_readonly categoryselect" readonly='readonly' value="{{ AlternateCategoriesCache[module.getId()].path|safe }}" />
								<a class='categoryselect' title="Browse Shopping Comparison Categories" href='#'>
									<img width="16" height="16" alt="Choose Category" src="images/folder.gif" border='0'>
								</a>
								<a class="categoryclear" href='#' title='Clear Shopping Comparison Category'>{% lang 'Clear' %}</a>
							</td>
						</tr>
						{% endfor %}
					</table>
					{% endif %}
					</div>
					<div id="div_optimizer" style="padding-top: 10px; display:none;">
						<p class="InfoTip">{{ GoogleWebsiteOptimizerIntro|safe }}</p>

						<table width="100%" class="Panel" style="{{ ShowEnableGoogleWebsiteOptimzer|safe }}">
							<tr>
								<td class="Heading2" colspan="2">{% lang 'GoogleWebsiteOptimizer' %}</td>
							</tr>
							<tr>
								<td class="FieldLabel">
									{% lang 'EnableGoogleWebsiteOptimizer' %}?
								</td>
								<td>
									<input {{ DisableOptimizerCheckbox|safe }} type="checkbox" name="catenableoptimizer" id="catenableoptimizer" {{ CheckEnableOptimizer|safe }} />
									<label for="catenableoptimizer">{% lang 'YesEnableGoogleWebsiteOptimizer' %}</label>
								</td>
							</tr>
						</table>
						{{ OptimizerConfigForm|safe }}
					</div>
					<table width="100%" cellspacing="0" cellpadding="2" border="0" id="SaveButtons" class="PanelPlain">
						<tr>
							<td colspan="2">
								<input type="submit" value="{% lang 'SaveAndExit' %}" class="FormButton" />
								<input type="submit" value="{{ SaveAndAddAnother|safe }}" name="AddAnother" class="FormButton" style="width:130px" />
								<input type="button" name="CancelButton2" value="{% lang 'Cancel' %}" class="CategoryCancelButton FormButton" />
							</td>
						</tr>
						<tr><td class="Gap"></td></tr>
					 </table>
				</td>
			</tr>
		</table>
	</div>
</form>

{% include 'category.select.modal.tpl' %}

<script type="text/javascript" src="script/category.selector.js?{{ JSCacheToken }}"></script>
<script type="text/javascript" src="script/categories.js?{{ JSCacheToken }}"></script>

<script type="text/javascript">
	$.extend(lang, {
		CancelMessage		: "{{ CancelMessage|safe }}",
		NoCategoryName		: "{% jslang 'NoCategoryName' %}",
		NoParentCategory	: "{% jslang 'NoParentCategory' %}",
		NoCatSortOrder		: "{% jslang 'NoCatSortOrder' %}",
		ChooseValidImage	: "{% jslang 'ChooseValidImage' %}",
		CollapseCategory	: "{% jslang 'CollapseCategory' %}",
		ExpandCategory		: "{% jslang 'ExpandCategory' %}",
		CategorySelectModalIntro			: "{% jslang 'CategoryMappingModalIntro' %}",
		CategorySelectModalTitle			: "{% jslang 'CategoryMappingModalTitle' %}",
		CategorySelectLeafCategorySelected	: "{% jslang 'CategoryMappingLeafCategorySelected' %}",
		CategorySelectChooseLeafCategory	: "{% jslang 'CategoryMappingChooseLeafCategory' %}",
		ShoppingComparisonCategoryMappings	: "{% jslang 'ShoppingComparisonCategoryMappings' %}",
		ShoppingComparisonCategoryMappingsDesc : "{% jslang 'ShoppingComparisonCategoryMappingsDesc' %}"
	});

	CategoryForm.skipOptimizerConfirmMsg = {{SkipOptimizerConfirmMsg|default('false')|safe}};
	CategoryForm.currentTab = '{{ CurrentTab|safe }}';

	CategoryForm.shoppingComparisonModules = {};

	{% for module in ShoppingComparisonModules %}
		CategoryForm.shoppingComparisonModules["{{ module.getId|js }}"] = {
			name: "{{ module.getName|js }}"
		};
	{% endfor %}
	$(document).ready(function() {
		$('#catimagefile').live('change', function() {
			if ($(this).val()) {
				$("#YesUseImageRow").show();
				$("#YesUseImage").attr('checked', true);
				$("#YesUseImage").next().text($(this).val());
			}
			$("#PreviewCatImage").hide();
		});
	});
</script>

