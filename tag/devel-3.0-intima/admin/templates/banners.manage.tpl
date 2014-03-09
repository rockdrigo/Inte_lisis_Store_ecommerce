
	<div class="BodyContainer">
	<table id="Table13" cellSpacing="0" cellPadding="0" width="100%">
		<tr>
			<td class="Heading1">{% lang 'ViewBanners' %}</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{% lang 'BannerIntro' %}</p>
			{{ Message|safe }}
			<table id="IntroTable" cellspacing="0" cellpadding="0" width="100%">
			<tr>
			<td class="Intro" valign="top">
				<input type="button" name="IndexAddButton" value="{% lang 'CreateBanner' %}..." id="IndexCreateButton" class="SmallButton" onclick="document.location.href='index.php?ToDo=createBanner'" /> &nbsp;<input type="button" name="IndexDeleteButton" value="{% lang 'DeleteSelected' %}" id="IndexDeleteButton" class="SmallButton" onclick="ConfirmDeleteSelected()" {{ DisableDelete|safe }} />
			</td>
			<td class="SmallSearch" align="right">
				<table id="Table16" style="display:{{ DisplaySearch|safe }}">
				<tr>
					<form action="index.php?ToDo=viewBanners{{ SortURL|safe }}" method="get" onSubmit="return ValidateForm(CheckSearchForm)">
					<input type="hidden" name="ToDo" value="viewBanners">
					<td nowrap>
						<input name="searchQuery" id="searchQuery" type="text" value="{{ Query|safe }}" id="SearchQuery" class="Button" size="20" />&nbsp;
						<input type="image" name="SearchButton" id="SearchButton" src="images/searchicon.gif" border="0" />
					</td>
					</form>
				</tr>
				<tr>
					<td align="right" style="padding-right:55pt">
						<a id="SearchClearButton" href="index.php?ToDo=viewBanners">{% lang 'ClearResults' %}</a>
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
			<form name="frmBanners" id="frmBanners" method="post" action="index.php?ToDo=deleteBanners">
				<div class="GridContainer">
					{{ BannersGrid|safe }}
				</div>
			</form>
		</td></tr>
	</table>
	</div>

	<script type="text/javascript">

		function CheckSearchForm()
		{
			var filter = document.getElementById("filterCategory");
			var query = document.getElementById("searchQuery");

			if(filter.value == "" && query.value == "")
			{
				alert("{% lang 'ChooseFilterOrEnterSearchTerm' %}");
				return false;
			}

			return true;
		}

		function ConfirmDeleteSelected()
		{
			var fp = document.getElementById("frmBanners").elements;
			var c = 0;

			for(i = 0; i < fp.length; i++) {
				if(fp[i].type == "checkbox" && fp[i].checked)
					c++;
			}

			if(c > 0) {
				if(confirm("{% lang 'ConfirmDeleteBanners' %}"))
					document.getElementById("frmBanners").submit();
			}
			else {
				alert("{% lang 'ChooseBanners' %}");
			}
		}

		function ToggleDeleteBoxes(Status)
		{
			var fp = document.getElementById("frmBanners").elements;

			for(i = 0; i < fp.length; i++)
				fp[i].checked = Status;
		}

	</script>
