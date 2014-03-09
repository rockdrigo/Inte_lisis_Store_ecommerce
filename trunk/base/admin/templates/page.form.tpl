<script type="text/javascript" src="script/page.js?{{ JSCacheToken }}"></script>

	<form enctype="multipart/form-data" action="index.php?ToDo={{ FormAction|safe }}" onSubmit="return ValidateForm(CheckPageForm);" id="frmNews" method="post">
	<input type="hidden" name="pageId" id="pageId" value="{{ PageId|safe }}">
	<div class="BodyContainer">
	<table class="OuterPanel">
		<tr>
			<td class="Heading1" id="tdHeading">{{ Title|safe }}</td>
		</tr>
		<tr>
			<td class="Intro">
				<p>{% lang 'PageIntro' %}</p>
				{{ Message|safe }}
			</td>
		</tr>
		<tr>
			<td style="padding-bottom:8px">
				<input type="submit" value="{% lang 'SaveAndExit' %}" class="FormButton" />
				<input type="submit" name="addAnother2" value="{{ SaveAndAddAnother|safe }}" class="FormButton" style="width:130px" />
				<input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel();" />
				<input id="currentTab" name="currentTab" value="details" type="hidden">
			</td>
		</tr>
		<tr>
			<td>
				<ul id="tabnav">
					<li><a href="#" class="active" id="tab0" onclick="ShowTab(0)">{% lang 'Details' %}</a></li>
					<li><a href="#" id="tab1" onclick="ShowTab(1)">{% lang 'GoogleWebsiteOptimizer' %}</a></li>
				</ul>
			</td>
		</tr>

		<tr>
			<td>
			<div id="div0" style="padding-top: 10px;">

			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'PageType1' %}</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'PageType' %}:
					</td>
					<td>
						<input onclick="SwitchType(0)" type="radio" id="pagetype_0" name="pagetype" value="0" {{ SelType0|safe }}> <label for="pagetype_0">{% lang 'NormalPage' %}</label><br />
						<input onclick="SwitchType(1)" type="radio" id="pagetype_1" name="pagetype" value="1" {{ SelType1|safe }}> <label for="pagetype_1">{% lang 'ExternalLink' %}</label><br />
						<input onclick="SwitchType(2)" type="radio" id="pagetype_2" name="pagetype" value="2" {{ SelType2|safe }}> <label for="pagetype_2">{% lang 'RSSPage' %}</label><br />
						<input onclick="SwitchType(3)" type="radio" id="pagetype_3" name="pagetype" value="3" {{ SelType3|safe }}> <label for="pagetype_3">{% lang 'ContactPage' %}</label>
					</td>
				</tr>
				<tr>
				  <td colspan="2" class="Gap"></td>
				</tr>
				<tr>
				  <td colspan="2" class="Gap"></td>
				</tr>
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'NewPagesDetails' %}</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						<span class="Required">*</span>&nbsp;{% lang 'PageTitle' %}:
					</td>
					<td>
						<input type="text" id="pagetitle" name="pagetitle" class="Field400" value="{{ PageTitle|safe }}">
						<img onmouseout="HideHelp('d1');" onmouseover="ShowHelp('d1', '{% lang 'PageTitle' %}', '{% lang 'PageTitleHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d1"></div><br />
					</td>
				</tr>
				<tr style="{{ HideVendorOption|safe }}">
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'Vendor' %}:
					</td>
					<td>
						<span style="{{ HideVendorLabel|safe }}">{{ CurrentVendor|safe }}</span>
						<select name="vendor" id="vendor" class="Field400" style="{{ HideVendorSelect|safe }}" onchange="ToggleVendor($(this).val());">
							{{ VendorList|safe }}
						</select>
						<img style="{{ HideVendorSelect|safe }}" onmouseout="HideHelp('vendorhelp');" onmouseover="ShowHelp('vendorhelp', '{% lang 'Vendor' %}', '{% lang 'PageVendorHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="vendorhelp"></div>
					</td>
				</tr>
				<tr class="HideIfNotPage PageContent">
					<td class="FieldLabel">
						<span class="Required">*</span>&nbsp;{% lang 'PageContent' %}:
					</td>
					<td>
						{{ WYSIWYG|safe }}
					</td>
				</tr>
				<tr class="HideIfPage">
					<td class="FieldLabel">
						<span class="Required">*</span>&nbsp;{% lang 'Link' %}:
					</td>
					<td>
						<input type="text" id="pagelink" name="pagelink" class="Field400" value="{{ PageLink|safe }}">
						<img onmouseout="HideHelp('d7');" onmouseover="ShowHelp('d7', '{% lang 'Link' %}', '{% lang 'PageLinkHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d7"></div><br />
					</td>
				</tr>
				<tr class="HideIfRSS">
					<td class="FieldLabel">
						<span class="Required">*</span>&nbsp;{% lang 'RSSFeed' %}:
					</td>
					<td>
						<input type="text" id="pagefeed" name="pagefeed" class="Field400" value="{{ PageFeed|safe }}">
						<img onmouseout="HideHelp('d8');" onmouseover="ShowHelp('d8', '{% lang 'RSSFeed' %}', '{% lang 'RSSFeedHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d8"></div><br />
					</td>
				</tr>
				<tr class="HideIfContact">
					<td class="FieldLabel">
						<span class="Required">*</span>&nbsp;{% lang 'EmailQuestionsTo' %}:
					</td>
					<td>
						<input type="text" id="pageemail" name="pageemail" class="Field200" value="{{ PageEmail|safe }}">
						<img onmouseout="HideHelp('d10');" onmouseover="ShowHelp('d10', '{% lang 'EmailQuestionsTo' %}', '{% lang 'EmailQuestionsToHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d10"></div><br />
					</td>
				</tr>
				<tr class="HideIfContact">
					<td class="FieldLabel">
						<span class="Required">*</span>&nbsp;{% lang 'ShowTheseFields' %}:
					</td>
					<td>
						<input type="checkbox" id="contactfield1" name="contactfields[]" value="ON" checked="checked" disabled="disabled"> <label for="contactfield1">{% lang 'ContactEmail' %}</label><br />
						<input type="checkbox" id="contactfield2" name="contactfields[]" value="ON" checked="checked" disabled="disabled"> <label for="contactfield2">{% lang 'ContactQuestion' %}</label><br />
						<input type="checkbox" id="contactfield3" name="contactfields[fullname]" value="fullname" {{ IsContactFullName|safe }}> <label for="contactfield3">{% lang 'ContactName' %}</label><br />
						<input type="checkbox" id="contactfield4" name="contactfields[companyname]" value="companyname" {{ IsContactCompanyName|safe }}> <label for="contactfield4">{% lang 'ContactCompanyName' %}</label><br />
						<input type="checkbox" id="contactfield5" name="contactfields[phone]" value="phone" {{ IsContactPhone|safe }}> <label for="contactfield5">{% lang 'ContactPhone' %}</label><br />
						<input type="checkbox" id="contactfield6" name="contactfields[orderno]" value="orderno" {{ IsContactOrderNo|safe }}> <label for="contactfield6">{% lang 'ContactOrderNo' %}</label><br />
						<input type="checkbox" id="contactfield7" name="contactfields[rma]" value="rma" {{ IsContactRMA|safe }}> <label for="contactfield7">{% lang 'ContactRMANo' %}</label>
						<img onmouseout="HideHelp('d9');" onmouseover="ShowHelp('d9', '{% lang 'ShowTheseFields' %}', '{% lang 'ShowTheseFieldsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d9"></div><br />
					</td>
				</tr>
				<tr>
				  <td colspan="2" class="Gap"></td>
				</tr>
				<tr>
				  <td colspan="2" class="Gap"></td>
				</tr>
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'NavigationMenuOptions' %}</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'NavigationMenu' %}:
					</td>
					<td>
						<input type="checkbox" id="pagestatus" name="pagestatus" value="ON" {{ Visible|safe }}> <label for="pagestatus">{% lang 'YesPageVisible' %}</label>
						<img onmouseout="HideHelp('d6');" onmouseover="ShowHelp('d6', '{% lang 'NavigationMenu' %}', '{% lang 'PageNavigationMenuHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d6"></div><br />
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'ParentPage' %}:
					</td>
					<td>
						<select id="pageparentid" name="pageparentid" class="Field400" size="5">
							<option SELECTED value='0'>-- {% lang 'NoParentPage' %} --</option>
							{{ ParentPageOptions|safe }}
						</select>
						<img onmouseout="HideHelp('d2');" onmouseover="ShowHelp('d2', '{% lang 'ParentPage' %}', '{% lang 'ParentPageHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d2"></div><br />
					</td>
				</tr>
				<tr>
				  <td colspan="2" class="Gap"></td>
				</tr>
				<tr>
				  <td colspan="2" class="Gap"></td>
				</tr>
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'AdvancedPageOptions' %}</td>
				</tr>
				<tr class="HideIfLink">
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'MetaTitle' %}:
					</td>
					<td>
						<input type="text" id="pagemetatitle" name="pagemetatitle" class="Field400" value="{{ PageMetaTitle|safe }}">
						<img onmouseout="HideHelp('help_metatitle');" onmouseover="ShowHelp('help_metatitle', '{% lang 'MetaTitle' %}', '{% lang 'MetaTitleHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="help_metatitle"></div><br />
					</td>
				</tr>
				<tr class="HideIfLink">
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'MetaKeywords' %}:
					</td>
					<td>
						<input type="text" id="pagekeywords" name="pagekeywords" class="Field400" value="{{ PageKeywords|safe }}">
						<img onmouseout="HideHelp('d3');" onmouseover="ShowHelp('d3', '{% lang 'MetaKeywords' %}', '{% lang 'MetaKeywordsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d3"></div><br />
					</td>
				</tr>
				<tr class="HideIfLink">
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'MetaDescription' %}:
					</td>
					<td>
						<input type="text" id="pagedesc" name="pagedesc" class="Field400" value="{{ PageDesc|safe }}">
						<img onmouseout="HideHelp('d4');" onmouseover="ShowHelp('d4', '{% lang 'MetaDescription' %}', '{% lang 'MetaDescriptionHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d4"></div><br />
					</td>
				</tr>
				<tr class="HideIfLink">
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'SearchKeywords' %}:
					</td>
					<td>
						<input type="text" id="pagesearchkeywords" name="pagesearchkeywords" class="Field400" value="{{ PageSearchKeywords|safe }}">
						<img onmouseout="HideHelp('searchkeywords');" onmouseover="ShowHelp('searchkeywords', '{% lang 'SearchKeywords' %}', '{% lang 'SearchKeywordsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="searchkeywords"></div>
					</td>
				</tr>
				<tr class="HideIfLink PageContent">
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'TemplateLayoutFile' %}:
					</td>
					<td>
						<select name="pagelayoutfile" id="pagelayoutfile" class="Field400">
							{{ LayoutFiles|safe }}
						</select>
						<img onmouseout="HideHelp('templatelayout');" onmouseover="ShowHelp('templatelayout', '{% lang 'TemplateLayoutFile' %}', '{% lang 'PageTemplateLayoutFileHelp1' %}{{ template|safe }}{% lang 'PageTemplateLayoutFileHelp2' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="templatelayout"></div>
					</td>
				</tr>
				<tr class="HideIfLinkVendor" style="{{ HideVendorSelect|safe }}">
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'DisplayAsHomePage' %}?
					</td>
					<td>
						<input type="checkbox" id="pageishomepage" name="pageishomepage" value="ON" {{ IsHomePage|safe }}> <label for="pageishomepage">{% lang 'YesDisplayAsHomePage' %}</label>
						<img onmouseout="HideHelp('d11');" onmouseover="ShowHelp('d11', '{% lang 'DisplayAsHomePage' %}', '{% lang 'DisplayAsHomePageHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d11"></div><br />
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'PageCustomersOnly' %}?
					</td>
					<td>
						<input type="checkbox" id="pagecustomersonly" name="pagecustomersonly" value="1" {{ IsCustomersOnly|safe }}> <label for="pagecustomersonly">{% lang 'YesRestrictToCustomersOnly' %}</label>
						<img onmouseout="HideHelp('d14');" onmouseover="ShowHelp('d14', '{% lang 'PageCustomersOnly' %}', '{% lang 'PageCustomersOnlyHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d14"></div><br />
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'SortOrder' %}:
					</td>
					<td>
						<input type="text" id="pagesort" name="pagesort" class="Field" size="5" value="{{ PageSort|safe }}">
						<img onmouseout="HideHelp('d5');" onmouseover="ShowHelp('d5', '{% lang 'SortOrder' %}', '{% lang 'SortOrderHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d5"></div><br />
					</td>
				</tr>
			</table>
			</div>
			 <div id="div1" style="padding-top: 10px; display:none;">
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
							<input {{ DisableOptimizerCheckbox|safe }} type="checkbox" name="pageEnableOptimizer" id="pageEnableOptimizer" {{ CheckEnableOptimizer|safe }} onclick = "ToggleOptimizerConfigForm({{ SkipOptimizerConfirmMsg|safe }});" />
							<label for="pageEnableOptimizer">{% lang 'YesEnableGoogleWebsiteOptimizer' %}</label>
						</td>
					</tr>
				</table>
				{{ OptimizerConfigForm|safe }}
			</div>

			<table>
				<tr>
					<td class="Gap">&nbsp;</td>
					<td class="Gap">
						<input type="submit" value="{% lang 'SaveAndExit' %}" class="FormButton" />
						<input type="submit" name="addAnother" value="{{ SaveAndAddAnother|safe }}" class="FormButton" style="width:130px" />
						<input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()">
					</td>
				</tr>
				<tr><td class="Gap"></td></tr>
				<tr><td class="Gap"></td></tr>
				<tr><td class="Sep" colspan="2"></td></tr>
			 </table>
			</td>
		</tr>
	</table>

	</div>
	</form>

	<script type="text/javascript">
		parentOptions = new Array();
		function ConfirmCancel()
		{
			if(confirm("{% lang 'ConfirmCancelPage' %}"))
				document.location.href = "index.php?ToDo=viewPages";
		}

		function ToggleVendor(vendorId)
		{
			if(typeof(parentOptions[vendorId]) != 'undefined') {
				$('#pageparentid').find('option:gt(0)').remove();
				$('#pageparentid').append(parentOptions[vendorId]);
				return;
			}
			$('#pageparentid').attr('disabled', true);
			$.ajax({
				url: 'remote.php?w=getPageParentOptions',
				data: {
					pageId: $('#pageId').val(),
					vendorId: vendorId,
					parentId: $('#pageparentid').val()
				},
				success: function(data) {
					parentOptions[vendorId] = data;
					$('#pageparentid').attr('disabled', false);
					$('#pageparentid').find('option:gt(0)').remove();
					$('#pageparentid').append(parentOptions[vendorId]);
				}
			});
		}

		function CheckPageForm()
		{
			var pt0 = g("pagetype_0");
			var pt1 = g("pagetype_1");
			var pt2 = g("pagetype_2");
			var pt3 = g("pagetype_3");
			var pagetitle = g("pagetitle");
			var pagelink = g("pagelink");
			var pagefeed = g("pagefeed");
			var pageemail = g("pageemail");

			if(pagetitle.value == "") {
				alert("{% lang 'EnterPageTitle' %}");
				pagetitle.focus();
				return false;
			}

			if(pt1.checked) {
				if(pagelink.value == "" || pagelink.value == "http://") {
					alert("{% lang 'EnterPageLink' %}");
					pagelink.focus();
					pagelink.select();
					return false;
				}
			}
			else if(pt2.checked) {
				if(pagefeed.value == "" || pagefeed.value == "http://") {
					alert("{% lang 'EnterPageFeed' %}");
					pagefeed.focus();
					pagefeed.select();
					return false;
				}
			}
			else if(pt3.checked) {
				if(IsWysiwygEditorEmpty(content.value)) {
					alert("{% lang 'EnterPageContent' %}");
					return false;
				}

				if(pageemail.value && pageemail.value.indexOf("@") == -1 || pageemail.value.indexOf(".") == -1) {
					alert("{% lang 'EnterPageEmail' %}");
					pageemail.focus();
					pageemail.select();
					return false;
				}
			}
			//validate google optimzer form
			else if ($('#pageEnableOptimizer').attr('checked')) {
				if(!Optimizer.ValidateConfigForm(ShowTab, 'optimizer')) {
					return false;
				}
			}

			// Everything is OK
			return true;
		}

		function SwitchType(PageType)
		{
			if(PageType == 0) { // Content page
				$('.HideIfPage').hide();
				$('.HideIfNotPage').show();
				$('.HideIfLink').show();
				$('.HideIfNotLink').show();
				$('.HideIfRSS').hide();
				$('.HideIfContact').hide();
				$('#pagetype_0').attr('checked', 'true');
				if (!{{ IsVendor|safe }}) {
					$('.HideIfLinkVendor').show();
				}
			}
			else if(PageType == 1) { // Link page
				$('.HideIfPage').show();
				$('.HideIfNotPage').hide();
				$('.HideIfLink').hide();
				$('.HideIfNotLink').hide();
				$('.HideIfRSS').hide();
				$('.HideIfContact').hide();
				$('.HideIfLinkVendor').hide();
				$('#pagetype_1').attr('checked', 'true');
			}
			else if(PageType == 2) { // RSS page
				$('.HideIfPage').hide();
				$('.HideIfNotPage').hide();
				$('.HideIfLink').show();
				$('.HideIfNotLink').show();
				$('.HideIfRSS').show();
				$('.HideIfContact').hide();
				if (!{{ IsVendor|safe }}) {
					$('.HideIfLinkVendor').show();
				}
				$('#pagetype_2').attr('checked', 'true');
			}
			else if(PageType == 3) { // Contact page
				$('.HideIfPage').hide();
				$('.HideIfNotPage').hide();
				$('.HideIfLink').show();
				$('.HideIfNotLink').show();
				$('.HideIfRSS').hide();
				$('.HideIfContact').show();
				$('.PageContent').show();
				if (!{{ IsVendor|safe }}) {
					$('.HideIfLinkVendor').show();
				}
				$('#pagetype_3').attr('checked', 'true');
			}
		}

		function ShowTab(T) {
			i = 0;
			while (document.getElementById("tab" + i) != null) {
				document.getElementById("div" + i).style.display = "none";
				document.getElementById("tab" + i).className = "";
				i++;
			}

			document.getElementById("div" + T).style.display = "";
			document.getElementById("tab" + T).className = "active";

			document.getElementById("currentTab").value = T;
		}

		$(document).ready(function() {
			ShowTab('{{ CurrentTab|safe }}');
			{{ SetupType|safe }}
		});

	</script>
