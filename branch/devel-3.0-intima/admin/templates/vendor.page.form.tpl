<form action="index.php?ToDo={{ FormAction|safe }}" id="frmPage" method="post" onsubmit="return ValidateForm(CheckPageForm)">
	<input type="hidden" name="vendorId" id="vendorId" value="{{ VendorId|safe }}" />
	<input type="hidden" name="pageId" id="pageId" value="{{ PageId|safe }}" />
<div class="BodyContainer">
	<table class="OuterPanel">
		<tr>
			<td class="Heading1">{{ Title|safe }}</td>
		</tr>

		<tr>
			<td class="Intro">
				<p>{{ Intro|safe }}</p>
				{{ Message|safe }}
				<p>
					<input type="submit" name="SaveButton1" value="{% lang 'Save' %}" class="FormButton" />&nbsp;
					<input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
				</p>
			</td>
		</tr>

		<tr>
			<td>
				<table width="100%" class="Panel">
					<tr>
						<td class="Heading2" colspan="2">{% lang 'PageSettings' %}</td>
					</tr>

					<tr>
						<td class="FieldLabel">
							<span class="Required">*</span> {% lang 'PageTitle' %}:
						</td>
						<td>
							<input type="text" name="pagetitle" id="pagetitle" class="Field250" value="{{ PageTitle|safe }}" />
							<img onmouseout="HideHelp('pagetitlehelp');" onmouseover="ShowHelp('pagetitlehelp', '{% lang 'PageTitle' %}', '{% lang 'PageTitleHelp' %}')" src="images/help.gif" alt="" border="0" />
							<div style="display:none" id="pagetitlehelp"></div>
						</td>
					</tr>

					<tr>
						<td class="FieldLabel">
							&nbsp;&nbsp;&nbsp;{% lang 'NavigationMenu' %}:
						</td>
						<td>
							<input type="checkbox" id="pagevisible" name="pagevisible" value="1" {{ Visible|safe }}> <label for="pagevisible">{% lang 'YesPageVisible' %}</label>
							<img onmouseout="HideHelp('d6');" onmouseover="ShowHelp('d6', '{% lang 'NavigationMenu' %}', '{% lang 'PageNavigationMenuHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
							<div style="display:none" id="d6"></div><br />
						</td>
					</tr>
				</table>
				<table width="100%" class="Panel">
					<tr>
						<td class="Heading2" colspan="2">{% lang 'PageContent' %}</td>
					</tr>
					<tr>
						<td colspan="2">{{ WYSIWYG|safe }}</td>
					</tr>
				</table>
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="PanelPlain">
				<tr>
					<td>
						<input type="submit" name="SaveButton2" value="{% lang 'Save' %}" class="FormButton" />&nbsp;
						<input type="button" name="CancelButton2" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
					</td>
				</tr>
			</table>
		</td>
	</tr>
	</table>
</div>
</form>
<script type="text/javascript">
	function ChecPageForm()
	{
		if(!$('#pagetitle').val()) {
			alert('{% lang 'EnterPageTitle' %}');
			$('#pagetitle').focus();
			return false;
		}

		if(g('wysiwyg')) {
			var content = g('wysiwyg').value;
		}
		else if(g('wysiwyg_html')) {
			var content = g('wysiwyg_html').value;
		}

		if(IsWysiwygEditorEmpty(content)) {
			alert("{% lang 'EnterPageContent' %}");
			return false;
		}
		return true;
	}


	function ConfirmCancel()
	{
		if(confirm('{% lang 'ConfirmCancel' %}')) {
			window.location = 'index.php?ToDo=editVendor&vendorId={{ VendorId|safe }}&currentTab=1';
		}

		return false;
	}
</script>