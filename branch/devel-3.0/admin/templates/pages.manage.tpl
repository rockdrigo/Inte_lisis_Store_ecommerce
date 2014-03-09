	<div class="BodyContainer">
		<script type="text/javascript">
			ShowLoadingIndicator();
			window.onload = function() {
				HideLoadingIndicator();
			};
		</script>
		<form name="frmPages" id="frmPages" method="post" action="index.php?ToDo=deletePages">
		<table id="Table13" cellSpacing="0" cellPadding="0" width="100%">
			<tr>
				<td class="Heading1">{% lang 'ViewPages' %}</td>
			</tr>
			<tr>
			<td class="Intro">
				<p>{{ PageIntro|safe }}</p>
				<div id="PagesStatus">{{ Message|safe }}</div>
				<p class="Intro">
					<input type="button" name="IndexAddButton" value="{% lang 'CreatePage' %}..." id="IndexCreateButton" class="SmallButton" onclick="document.location.href='index.php?ToDo=createPage'" /> &nbsp;
					<input type="button" name="IndexDeleteButton" value="{% lang 'DeleteSelected' %}" id="IndexDeleteButton" class="SmallButton" onclick="ConfirmDeleteSelected()" {{ DisableDelete|safe }} />
				</p>
			</td>
			</tr>
			<tr style="{{ HideTabs|safe }}">
				<td>
					<ul id="tabnav">
						<li><a href="#" class="active" id="tab0" onclick="ShowTab(0)">{% lang 'StorePages' %}</a></li>
						<li><a href="#" id="tab1" onclick="ShowTab(1)">{% lang 'VendorPages' %}</a></li>
					</ul>
				</td>
			</tr>
			<tr>
			<td>
				<div id="div0" style="display: {{ DisplayGrid|safe }}">
					{{ NoPagesMessage|safe }}
					<table class="GridPanel SortablePanel" cellspacing="0" cellpadding="0" border="0" style="width:100%; margin-top:10px">
						<tr class="Heading3">
							<td width="1" style="padding-left: 5px;">
								<input type="checkbox" onclick="ToggleDeleteBoxes(this.checked)" style="vertical-align: middle;" />
							</td>
							<td>
								{% lang 'PageTitle' %} &nbsp;
							</td>
							<td width="120">
								{% lang 'PageTypeHeading' %} &nbsp;
							</td>
							<td width="80" align="center">
								{% lang 'Visible' %} &nbsp;
							</td>
							<td width="80">
								{% lang 'Action' %}
							</td>
						</tr>
					</table>
					<ul class="SortableList" id="PageList">
						{{ PageGrid|safe }}
					</ul>
				</div>
				<div id="div1" style="display: none;">
					{{ NoVendorPagesMessage|safe }}
					<table class="GridPanel SortablePanel" cellspacing="0" cellpadding="0" border="0" style="width:100%; margin-top:10px">
						<tr class="Heading3">
							<td width="1" style="padding-left: 5px;">
								<input type="checkbox" onclick="ToggleDeleteBoxes(this.checked)" style="vertical-align: middle;" />
							</td>
							<td width="150">
								{% lang 'Vendor' %} &nbsp;
							</td>
							<td>
								{% lang 'PageTitle' %} &nbsp;
							</td>
							<td width="120">
								{% lang 'PageTypeHeading' %} &nbsp;
							</td>
							<td width="80" align="center">
								{% lang 'Visible' %} &nbsp;
							</td>
							<td width="80">
								{% lang 'Action' %}
							</td>
						</tr>
					</table>
					<ul class="SortableList" id="VendorPageList">
						{{ VendorPagesGrid|safe }}
					</ul>
				</div>
			</tr>
			</td>
		</table>
		<input type="hidden" name="currentTab" id="currentTab" value="{{ CurrentTab|safe }}" />
				</form>
	</div>
	<script type="text/javascript" src="../javascript/jquery/plugins/jquery.ui.nestedSortable.js?{{ JSCacheToken }}"></script>
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
			var fp = document.getElementById("frmPages").elements;
			var c = 0;

			for(i = 0; i < fp.length; i++)
			{
				if(fp[i].type == "checkbox" && fp[i].checked)
					c++;
			}

			if(c > 0)
			{
				if(confirm("{% lang 'ConfirmDeletePages' %}"))
					document.getElementById("frmPages").submit();
			}
			else
			{
				alert("{% lang 'ChoosePages' %}");
			}
		}

		function ToggleDeleteBoxes(Status)
		{
			var fp = document.getElementById("frmPages").elements;

			for(i = 0; i < fp.length; i++)
				fp[i].checked = Status;
		}

		function PreviewPage(PageId)
		{
			var l = screen.availWidth / 2 - 300;
			var t = screen.availHeight / 2 - 300;
			var win = window.open('index.php?ToDo=previewPage&pageId='+PageId, 'pagePreview', 'width=600,height=600,left='+l+',top='+t+',scrollbars=1');
		}

		var updatingSortables = false;
		var updateTimeout = null;
		function CreateSortableList() {
			$('#PageList').nestedSortable({
				disableNesting: 'no-nest',
				forcePlaceholderSize: true,
				handle: '.sort-handle',
				items: 'li',
				opacity: .8,
				tabSize: 20,
				tolerance: 'pointer',
				toleranceElement: '> table',
				listClass: 'SortableList',
				placeholder: 'SortableRowHelper',
				update: function(event, ui) {
					var serialized = $(this).sortable("serialize");
					$.ajax({
						url: 'remote.php?w=updatePageOrders',
						type: 'POST',
						dataType: 'xml',
						data: serialized,
						success: function(response) {
							var status = $('status', response).text();
							var message = $('message', response).text();
							if(status == 0) {
								display_error('PagesStatus', message);
							}
							else {
								display_success('PagesStatus', message);
							}
						}
					});
				}
			});
		}

		$(document).ready(function()
		{
			CreateSortableList();
		});

		function ShowTab(T)
		{
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
	</script>
	