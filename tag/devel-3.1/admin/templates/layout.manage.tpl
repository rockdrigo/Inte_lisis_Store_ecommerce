{% import "macros/util.tpl" as util %}
{% import "macros/forms.tpl" as formBuilder %}

<style type="text/css" media="screen">
	.mobileTemplateDevices div.value,
	.tabletTemplateDevices div.value {
		padding-left: 25px;
		display: block !important;
		background: url('images/nodejoin.gif') no-repeat;
	}
</style>

<script type="text/javascript">

// load language variables for the header image javascript
lang['HeaderImageConfirmDelete']   = "{% lang 'HeaderImageConfirmDelete' %}";
lang['LayoutHeaderNoCurrentImage'] = "{% lang 'LayoutHeaderNoCurrentImage' %}";
lang['LayoutHeaderImageNoImage']   = "{% lang 'LayoutHeaderImageNoImage' %}";

var disableLoadingIndicator;
var CurrentVersion = '{{ TemplateVersion|safe }}';

function ShowTab(T){
	i = 0;
	if('{{ HideMessageBox|safe }}' == 'none'){
		$('#TemplateMsgBox').hide('normal');
	}

	while(document.getElementById("tab" + i) != null){
		document.getElementById("div" + i).style.display = "none";
		document.getElementById("tab" + i).className = "";
		i++;
	}

	document.getElementById("div" + T).style.display = "";
	document.getElementById("tab" + T).className = "active";
	document.getElementById("currentTab").value = T;
	SetCookie('templatesCurrentTab', T, 365);

	$(document).trigger('tabSelect' + T);
}

function launchDesignMode()
{
	window.open('{{ ShopPathNormal|safe }}/?designModeToken={{ DesignModeToken|safe }}');
}

function get_random()
{
	var ranNum= Math.floor(Math.random()*105205);
	return ranNum;
}

function ChangeTemplateColor(link, preview, previewFull) {
	$(link).parents('div.TemplateBox').find('.previewImage').attr('src', preview);
	$(link).parents('div.TemplateBox').find('.previewImage').parents('a').attr('href', previewFull);
}

function DownloadTemplate(id, width, height) {
	tb_show('', 'index.php?ToDo=templateDownload&template='+id+'&height='+height+'&width='+width);
}


function LaunchEditor(){
	var win = window.open("designmode.php?ToDo=editFile&File=default.html&f=a");
	win.focus();
}

function CheckTemplateVersion(){
	// do the ajax request
	document.getElementById('TemplateVersionCheck').innerHTML = '<em>Checking Version...</em>';
	jQuery.ajax({ url: 'remote.php', type: 'POST', dataType: 'xml',
		data: {'w': 'checktemplateversion'},
		success: function(xml) {
			CheckTemplateVersionReturn(xml);
		}
	});
}

function CheckTemplateVersionReturn(xml){
	var  CurrentVersion = '{{ TemplateVersion|safe }}';

	if($('status', xml).text() == 1){
		if($('version', xml).text() > CurrentVersion){
			document.getElementById('TemplateVersionCheck').innerHTML = '<img src="images/success.gif" align="absmiddle"> {% lang 'NewVersionAvailable' %}'.replace('%%VERSION%%', $('version', xml).text());

			if ($.browser.msie){
				$('#TemplateVersionCheck').css("background-color","#99FF66");
			} else {
				$('#TemplateVersionCheck').show(0);
				$('#TemplateVersionCheck').css("background-color","#99FF66");
				$('#TemplateVersionCheck').animate({ backgroundColor: '#F9F9F9' }, { queue: true, duration: 1000 });
			}

			document.getElementById('TemplateVersionCheckButton').style.display = "none";
			document.getElementById('DownloadNewVersionButton').style.display = "";
		}else{
			document.getElementById('TemplateVersionCheck').innerHTML = '{% lang 'CurrentTemplateLatest' %}';
		}
	}else {
		display_error('An Error has Occurred: ' + $('message', xml).text());
	}
}

function DownloadNewVersion(){
	if(confirm('Important Note: By downloading this new template you will completely override your current template files which will *not* be recoverable. If you have made any modifcations to your current template then you should backup your current template before continuing.\n\nTo download this template, click \'OK\'. To keep the current version, click the \'Cancel\' button.')){
		if($.browser.msie){
			tb_show('', "index.php?ToDo=templatedownload&template={{ CurrentTemplateName|safe }}&color={{ CurrentTemplateColor|safe }}&height=80&width=280&PreviewImage={{ CurrentTemplateImage|safe }}");
		}else{
			tb_show('', "index.php?ToDo=templatedownload&template={{ CurrentTemplateName|safe }}&color={{ CurrentTemplateColor|safe }}&height=58&width=240&PreviewImage={{ CurrentTemplateImage|safe }}");
		}
		document.getElementById('TemplateVersionCheckButton').style.display = "";
		document.getElementById('DownloadNewVersionButton').style.display = "none";
	}
}

function display_message(text,type){
	if(type=='error'){
		display_error('TemplateMsgBox', text);
	} else {
		display_success('TemplateMsgBox', text);
	}
}


lang.TemplateDownloadColorsConfirm = "{% lang 'TemplateDownloadColorsConfirm' %}";
$(window).resize(function() {
	// Remove the return statement to have the template list automatically
	// centered in the middle of the page. Apparently we don't want to do this at the moment.
	return;
	templateBoxWidth = $('.TemplateList .TemplateBox').width() + 20;
	$('.TemplateList').css({
		width: '100%'
	});
	width = $('.TemplateListContainer').width();
	numBoxes = Math.floor(width / templateBoxWidth);
	visibleBoxes = $('.TemplateBox:visible').length;
	if(visibleBoxes < numBoxes) {
		numBoxes = visibleBoxes;
	}
	left = (width - (numBoxes * templateBoxWidth)) / 2;
	$('.TemplateList').css({
		width: (templateBoxWidth * numBoxes) + 'px'
	});
});

$(document).ready(function() {
	$(window).trigger('resize');
	$('a.TplPreviewImage').fancybox({
		'zoomSpeedIn': 200,
		'zoomSpeedOut': 200,
		'overlayShow': false,
		'notitle': true
	});

	$('.TemplateBox:not(.TemplateBoxOn)').hover(function() {
		$(this).addClass('TemplateBoxOver');
	}, function() {
		$(this).removeClass('TemplateBoxOver');
	});

	$('.TemplateBox a.ActivateLink').click(function() {
		templateBox = $(this).parents('.TemplateBox');
		templateId = templateBox.attr('class').match('TemplateId_([^ $]+)')[1];
		templateName = $('span.TemplateName', templateBox).html();
		templateColor = $('span.TemplateColor', templateBox).html();
		if(templateBox.hasClass('Installable')) {
			if($('.TemplateList .TemplateId_'+templateId).length > 1) {
				colorSchemes = '';
				$('.TemplateList .TemplateId_'+templateId).each(function() {
					templateColor = $('span.TemplateColor', this).html();
					colorSchemes += '- '+templateColor+"\n";
				});
				message = lang.TemplateDownloadColorsConfirm;
				message = message.replace(':templateName', templateName);
				message = message.replace(':templateColor', templateColor);
				message = message.replace(':colorList', colorSchemes);
				if(!confirm(message)) {
					return false;
				}
			}
			tb_show('', 'index.php?ToDo=templateDownload&template='+templateId+'&height=58&width=300&color='+templateColor);
		}
		else {
			window.location = 'index.php?ToDo=changeTemplate&template='+templateId+'&color='+templateColor;
		}
		return false;
	});

	$('.ShowTemplateTypes').change(function() {
		$('.NoTemplateMessage').hide();
		switch($(this).val()) {
			case 'installed':
				$('.TemplateBox').show();
				$('.TemplateBox.Installable').hide();
				break;
			case 'downloadable':
				$('.TemplateBox').hide();
				$('.TemplateBox.Installable').show();
				break;
			default:
				$('.TemplateBox').show();
		}
		$(window).trigger('resize');
		if($('.TemplateBox:visible').length == 0) {
			alert('{% jslang 'NoTemplatesAvailableFilter' %}');
			$('.ShowTemplateTypes').val('all').trigger('change');
		}
	});

	// Scroll to the active template
	offsetTop = $('.TemplateBoxOn').offset().top;
	listTop = $('.TemplateList').offset().top;
	scrollTop = offsetTop - listTop - 20;
	if(scrollTop > 0) {
		$('.TemplateListContainer').scrollTop(scrollTop);
	}
});

</script>

<script type="text/javascript" src="../javascript/jquery/plugins/ajax.file.upload.js?{{ JSCacheToken }}"></script>
<script type="text/javascript" src="../javascript/jquery/plugins/fancybox/fancybox.js?{{ JSCacheToken }}"></script>
<link rel="stylesheet" href="../javascript/jquery/plugins/fancybox/fancybox.css?{{ JSCacheToken }}" type="text/css" media="screen">

<script type="text/javascript" src="script/layout.js?{{ JSCacheToken }}"></script>
<script type="text/javascript" src="script/layout.headerimage.js?{{ JSCacheToken }}"></script>

	<div class="BodyContainer">
	<table class="OuterPanel">
		<tr>
			<td class="Heading1">{% lang 'ManageTemplates' %}</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{{ LayoutIntro|safe }}</p>
			<p id="TemplateMsgBox">{{ Message|safe }}</p>
		</td>
		</tr>
		<tr>
		<td class="Intro"><br />
			<form action="index.php" method="get">
			<input type="hidden" name="ToDo" value="viewTemplates">
		<ul id="tabnav">
				<li><a href="javascript:ShowTab(0)" class="active" id="tab0">{% lang 'LayoutTabStoreDesign' %}</a></li>
				<li><a href="javascript:ShowTab(1)" id="tab1">{% lang 'LayoutTabLogoSettings' %}</a></li>
				<li><a href="javascript:ShowTab(2)" id="tab2">{% lang 'LayoutTabDesignMode' %}</a></li>
				<li><a href="javascript:ShowTab(6)" id="tab6">{% lang 'LayoutTabMobile' %}</a></li>
				<li><a href="javascript:ShowTab(3)" id="tab3">{% lang 'LayoutTabEmails' %}</a></li>
				{% if GiftCertificateThemes %}
				<li><a href="javascript:ShowTab(7)" id="tab7">{% lang 'LayoutTabGiftCertificates' %}</a></li>
				{% endif %}
				<li><a href="javascript:ShowTab(4)" id="tab4">{% lang 'LayoutTabFavicon' %}</a></li>
				<li><a href="javascript:ShowTab(5)" id="tab5">{% lang 'LayoutTabHeader' %}</a></li>
		</ul>
			<input id="currentTab" name="currentTab" value="{{ ShowTab|safe }}" type="hidden">
			</form>

		</td>
		</tr>

	</table>
	<div id="div0">
		<p class="intro">
			{% lang 'TemplateChoiceIntro' %}
		</p>

		<p class="MessageBox MessageBoxInfo" style="{{ HideSafeModeMessage|safe }}; margin-top: 10px;">{% lang 'TemplateDownloadingSafeModeEnabled' %}</p>

		<table class="Panel">
			<tr>
			  <td class="Heading2" colspan='2'>{% lang 'CurrentTemplate' %}</td>
			</tr>
			<tr>
				<td align="left" width="200" style="padding:5px 5px 5px 10px;">
					<a href='{{ ShopPath|safe }}/templates/{{ CurrentTemplateName|safe }}/Previews/{{ CurrentTemplateImage|safe }}' class="thickbox"><img src="thumb.php?tpl={{ CurrentTemplateName|safe }}&color={{ CurrentTemplateImage|safe }}" border="0" id="CurrentTemplateImage"></a>
				</td>
				<td align="left" valign="top"  style="padding:5px 5px 5px 10px;">
					<div class="TemplateHeading" id="CurrentTemplateHeading">{{ CurrentTemplateNameProper|safe }} ({{ CurrentTemplateColor|safe }}) - Version {{ TemplateVersion|safe }}</div>
					<div id="TemplateFilesLocated">{% lang 'TemplateFilesLocated' %}{{ CurrentTemplateName|safe }}</div><br />

					<input type="button" value="{% lang 'BrowseTemplateFiles' %}" class="SmallButton" class="Button" onclick="LaunchEditor();">	<input type="Button" class="SmallButton" onclick="CheckTemplateVersion();" value="{% lang 'CheckNewVersion' %}"  id="TemplateVersionCheckButton"> <input type="Button" class="SmallButton" onclick="DownloadNewVersion();" value="{% lang 'DownloadNewVersion' %}"  id="DownloadNewVersionButton" style="display:none; font-weight: bold;"><br /><br />
					<div id="TemplateVersionCheck"></div>
				</td>
			</tr>
	 </table><br />

	<table class="Panel" style="margin:0px;">
		<tr>
		  <td class="Heading2" colspan='2'>
			<span class="FloatRight">
				<strong>{% lang 'Filter' %}</strong>
					<select name="templateType" class="ShowTemplateTypes">
					<option value="all">{% lang 'ShowAllTemplates' %}</option>
					<option value="installed">{% lang 'ShowInstalledTemplates' %}</option>
					<option value="downloadable">{% lang 'ShowNewTemplates' %}</option>
				</select>
			</span>
			{% lang 'ChooseTemplate' %}
		  </td>
		</tr>
		<tr>
			<td>
				<div class="TemplateListContainer">
					<div class="TemplateList">
						{{ TemplateListMap|safe }}
					</div>
				</div>
			</td>
		</tr>
	</table>
</div>

		<div id="div1" style="display:none">
		<!-- Start Logo Editor Tab -->
			{{ LogoTab|safe }}
		<!-- End Logo Editor Tab -->
		</div>
		<div id="div2" style="display:none">
			<p class="intro">
				{% lang 'DesignModeIntro' %}
			</p>
			<ul>
				<li>{% lang 'DesignModeIntro2' %}</li>
				<li>{% lang 'DesignModeIntro3' %}</li>
				<li>{% lang 'DesignModeIntro4' %}</li>
				<!--<li><a href="#" class="thickbox">{% lang 'DesignModeIntro5' %}</a></li>-->
			</ul>

			<p>
				<input type="button" onclick="launchDesignMode();" value="{% lang 'LaunchDesignMode' %}" />
			</p>
		</div>
		<div id="div6" style="display: none">
			<p class="intro">
				{{ lang.MobileTemplateIntro }}
			</p>
			<form method="post" action="index.php" id="mobileTemplateSettingsForm" enctype="multipart/form-data">
				<input type="hidden" name="ToDo" value="saveMobileTemplateSettings" />
				{{ formBuilder.startForm }}
					{{ formBuilder.heading(lang.MobileTemplateSettings) }}

					{{ formBuilder.startRow([
						'label': lang.EnableMobileTemplate ~'?'
					]) }}
						<label>
							<input type="checkbox" name="enableMobileTemplate" value="1" {% if mobileSettings.enableMobileTemplate %}checked="checked"{% endif %} />
							{{ lang.YesEnableMobileTemplate }}
						</label>
						(<a href="../templates/__mobile/Previews/default.jpg" class="TplPreviewImage">{{ lang.Preview }}</a>)
						{{ util.tooltip('EnableMobileTemplate', 'EnableMobileTemplateHelp') }}
					{{ formBuilder.endRow }}

					{{ formBuilder.startRow([
						'label': lang.EnableOnTheseDevices ~ ':',
						'class': 'mobileTemplateDevices enableMobileTemplateToggle'
					]) }}

						<label class="row">
							<input type="checkbox" name="enableMobileTemplateDevices[]" value="iphone" {% if 'iphone' in mobileSettings.enableMobileTemplateDevices %}checked="checked"{% endif %} />
							{{ lang.MobileDeviceAppleiPhone }}
						</label>
						<label class="row">
							<input type="checkbox" name="enableMobileTemplateDevices[]" value="ipod" {% if 'ipod' in mobileSettings.enableMobileTemplateDevices %}checked="checked"{% endif %} />
							{{ lang.MobileDeviceAppleiPodTouch }}
						</label>
						<label class="row">
							<input type="checkbox" name="enableMobileTemplateDevices[]" value="ipad" {% if 'ipad' in mobileSettings.enableMobileTemplateDevices %}checked="checked"{% endif %} />
							{{ lang.MobileDeviceAppleiPad }}
						</label>
						<label class="row">
							<input type="checkbox" name="enableMobileTemplateDevices[]" value="pre" {% if 'pre' in mobileSettings.enableMobileTemplateDevices %}checked="checked"{% endif %} />
							{{ lang.MobileDevicePalmPre }}
						</label>
						<label class="row">
							<input type="checkbox" name="enableMobileTemplateDevices[]" value="android" {% if 'android' in mobileSettings.enableMobileTemplateDevices %}checked="checked"{% endif %} />
							{{ lang.MobileDeviceAndroid }}
						</label>
					{{ formBuilder.endRow }}

					{{ formBuilder.startRow([
						'label': lang.MobileTemplateLogo ~ ':',
						'class': 'enableMobileTemplateToggle'
					]) }}
						<input type="file" name="mobileTemplateLogo" />
						{{ util.tooltip('MobileTemplateLogo', 'MobileTemplateLogoHelp') }}
						{% if mobileSettings.mobileTemplateLogo %}
							(<label><input type="checkbox" name="deleteMobileTemplateLogo" /> {{ lang.Delete }} <a href="../{{ ImageDirectory }}/{{ mobileSettings.mobileTemplateLogo }}" target="_blank">{{ lang.LowerCurrentLogo }}</a>?</label>)
						{% endif %}
						<div class="small">
							{% lang 'RecommendedLogoDimensions' with [
								'width': phoneLogoDimensions.width,
								'height': phoneLogoDimensions.height
							] %}
						</div>
					{{ formBuilder.endRow }}

					{{ formBuilder.startButtonRow }}
						<input type="submit" class="saveButton" value="{{ lang.Save }}" />
						{{ lang.Or }} <a href="#" class="cancelLink">{{ lang.Cancel }}</a>
					{{ formBuilder.endButtonRow }}
				{{ formBuilder.endForm }}
			</form>
		</div>
		<div id="div3" style="display:none">
			<p class="intro">
				{% lang 'EmailTemplatesIntro' %}
			</p>
			<table class="GridPanel SortableGrid AutoExpand" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%;">
				<tr class="Heading3">
					<td>{% lang 'ETFileName' %}</td>
					<td>{% lang 'ETFileSize' %}</td>
					<td>{% lang 'ETLastUpdated' %}</td>
					<td>{% lang 'Action' %}</td>
				</tr>
				{{ EmailTemplatesGrid|safe }}
			</table>
		</div>
		<div id="div7" style="display: none">
			{% include 'layout.manage.giftcerts.tpl' %}
		</div>

		<div id="div4" style="display: none;">
			<p class="intro" >
				{% lang 'FaviconIntro' %}
			</p>
			<form method="post" action="index.php?ToDo=TemplateUploadFavicon" enctype="multipart/form-data" onsubmit="return CheckFaviconForm();">
				<table class="Panel" style="margin:0px;">
					<tr>
						<td class="Heading2" colspan='2'>{% lang 'FaviconUpload' %}</td>
					</tr>
					<tr>
						<td class="FieldLabel PanelBottom">
							{% lang 'SelectLogoUpload' %}:
						</td>
						<td class="PanelBottom">
							<img src="{{ Favicon|safe }}" width="16" height="16" />&nbsp;&nbsp;<input type="file" name="FaviconFile" id="FaviconFile" class="Field" value="" /> <input type="submit" value="{% lang 'UploadFavicon' %}" />
						</td>
					</tr>
				</table>
			</form>
		</div>

		<div id="div5" style="display: none; ">
				<p class="intro">{% lang 'LayoutHeaderImageIntro' %}</p>

				<table class="Panel" style="margin:0px;">
					<tr>
					  <td class="Heading2" colspan='2'>{% lang 'LayoutHeaderImageGroupName' %}</td>
					</tr>
					<tr>
						<td align="left" width="200" style="padding:5px 5px 5px 10px;" valign="top">
		{% lang 'LayoutHeaderImageCurrentImage' %}:
						</td>
						<td align="left" valign="top"  style="padding:5px 5px 5px 10px;">
							<div id='currentHeaderImage'></div>
							<div id="DownloadHeaderImages" style="padding-top: 5px;">
							{% lang 'LayoutHeaderDownloadIntro' %} <span id="BrowserBasedHelpText"></span>
							<ul>
								<li id="HeaderImageCurrentLinkContainer"><a href="#" id="">{% lang 'LayoutHeaderImageDownloadCurrentBG' %}</a> (<a href="#" id="HeaderImageDeleteLink">{% lang 'LayoutHeaderImageDelete' %}</a>)</li>
								<li><a href="#" id="HeaderImageOrigLink">{% lang 'LayoutHeaderImageDownloadWithoutBG' %}</a></li>
								<li id="HeaderImageBlankLinkContainer"><a href="#" id="HeaderImageBlankLink">{% lang 'LayoutHeaderImageDownloadWithBG' %}</a></li>
							</ul>
							</div>
						</td>
					</tr>

					<tr id="UploadHeaderImageRow" style="display:">
						<td align="left" width="200" valign="top"  style="padding:5px 5px 5px 10px;">
							{% lang 'LayoutHeaderImageUploadImage' %}:
						</td>
						<td align="left" valign="top"  style="padding:5px 5px 5px 10px;" id="">

							<input type="file" name="HeaderImageFile" id="HeaderImageFile" class="Field300" value=""><br />
							<br /><input type="button" name="SubmitHeaderImageForm" id="SubmitHeaderImageForm" class="Button" value="{% lang 'LayoutHeaderImageUploadButton' %}" />

						</td>
					</tr>
				</table>
		</div>

	</div>
	<div style="display: none" id="templateSelectedMessage"></div>

	<script type="text/javascript" defer>
		lang.InvalidSettingenableMobileTemplateDevices = "{% jslang 'InvalidSettingenableMobileTemplateDevices' %}";
		lang.InvalidSettingEnableTabletTemplateDevices = "{% jslang 'InvalidSettingEnableTabletTemplateDevices' %}";
		lang.UploadValidMobileLogo = "{% jslang 'UploadValidMobileLogo' %}";
		lang.UploadValidTabletLogo = "{% jslang 'UploadValidTabletLogo' %}";

		var DisplayTab = 0;
		var ForceTab = '{{ ForceTab|safe }}';

		if(ForceTab.length > 0){
			DisplayTab = ForceTab;
		}

		DisplayTab = parseInt(DisplayTab);

		if(DisplayTab > -1){
			ShowTab(DisplayTab);
		}

		function edit_template(trID, tplfile) {
			$('#edit_'+trID).show();

			// Load the contents of the file
			jQuery.ajax({
				url: 'remote.php',
				type: 'POST',
				dataType: 'text',
				data: {'w': 'getEmailTemplate', 'file': tplfile, 'id': trID},
				success: function(txt) {
					$('#edit_box_'+trID).html(txt);
					if(typeof(tinyMCE) != 'undefined') {
						eval('LoadEditor_wysiwyg_'+trID+'()');
					}
				}
			});
		}

		function edit_hide(trID) {
			if(confirm("{% lang 'ETHideEdit' %}")) {
				$('#edit_'+trID).hide();
			}
		}

		function save_edit(trID, tplfile) {
			if(typeof(tinyMCE) != 'undefined') {
				var html = tinyMCE.get('wysiwyg_'+trID).getContent();
			}
			else {
				var html = $("#wysiwyg_"+trID).val();
			}

			// Save the contents of the file
			jQuery.ajax({
				url: 'remote.php',
				type: 'POST',
				dataType: 'text',
				data: {'w': 'updateEmailTemplate', 'file': tplfile, 'html': html},
				success: function(status) {
					if(status == "success") {
						msg = "{% lang 'EmailTemplateUpdated' %}";
					}
					else {
						msg = "{% lang 'EmailTemplateUpdateFailed' %}";
					}
					alert(msg);
					$('#edit_'+trID).hide();
				}
			});
		}

		var EmailTemplates = {
			ExpandDirectory: function(row, directory)
			{
				$('#'+row+' .ExpandImg').blur();
				// Already expanded
				if($('#'+row).is('.Expanded')) {
					$('#'+row+' .ExpandImg').attr('src', $('#'+row+' .ExpandImg').attr('src').replace('minus.gif', 'plus.gif'));
					$('.Child_'+row).hide();
					$('#'+row).removeClass('Expanded');
					$('#Indicator_'+row).hide();
					return;
				}

				// We already have results, so just expand
				if($('.Child_'+row).length > 0) {
					$('#'+row+' .ExpandImg').attr('src', $('#'+row+' .ExpandImg').attr('src').replace('plus.gif', 'minus.gif'));
					$('.Child_'+row).show();
					$('#'+row).addClass('Expanded');
					return;
				}
				$('#Indicator_'+row).show();
				$.ajax({
					url: 'remote.php',
					data: {
						w: 'GetEmailTemplateDirectory',
						path: directory,
						parent: row
					},
					success: function(response) {
						$('#'+row+' .ExpandImg').attr('src', $('#'+row+' .ExpandImg').attr('src').replace('plus.gif', 'minus.gif'));
						if(response) {
							$('#Indicator_'+row).hide();
							$('#'+row).after(response);
						}
						else {
							$('#Indicator_'+row+' td').html('<span style="padding-left: 25px;"> {% lang 'DirectoryContainsNoFiles' %}</span>');
						}
						$('#'+row).addClass('Expanded');
						$('.Child_'+row).hover(function() {
							$(this).addClass('GridRowOver');
						}, function() {
							$(this).removeClass('GridRowOver');
						});
					}
				})
			}
		}

		function CheckFaviconForm()
		{
			if (document.getElementById('FaviconFile').value == '') {
				alert('{% lang 'FaviconNoImageSelected' %}');
				return false;
			}

			return true;
		}
	</script>
	<div style="display: none;">
		{{ TemporaryEditor|safe }}
	</div>