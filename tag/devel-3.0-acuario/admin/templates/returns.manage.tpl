	<div class="BodyContainer">

	<table id="Table13" cellSpacing="0" cellPadding="0" width="100%">
		<tr>
			<td class="Heading1">
				{% lang 'View' %}: <a href="#" style="color:#005FA3" id="ViewsMenuButton" class="PopDownMenu">{{ ViewName|safe }} <img width="8" height="5" src="images/arrow_blue.gif" border="0" /></a>
			</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{% lang 'ManageReturnsIntro' %}</p>
			<div id="ReturnsStatus">
				{{ Message|safe }}
			</div>
			<table id="IntroTable" cellspacing="0" cellpadding="0" width="100%">
			<tr>
			<td class="Intro" valign="top">
				<input type="button" name="IndexDeleteButton" value="{% lang 'DeleteSelected' %}" id="IndexDeleteButton" class="SmallButton" onclick="ConfirmDeleteSelected()" {{ DisableDelete|safe }} />
			</td>
			<td class="SmallSearch" align="right">
				<table id="Table16" style="display:{{ DisplaySearch|safe }}">
				<form name="frmReturns" id="frmReturns" action="index.php?ToDo=viewReturns{{ SortURL|safe }}" method="get">
				<tr>
					<td class="text" nowrap align="right">
						<input name="ToDo" id="ToDo" type="hidden" value="viewReturns" />
						<input name="searchQuery" id="searchQuery" type="text" value="{{ Query|safe }}" id="SearchQuery" class="SearchBox" style="width:150px" />&nbsp;
						<select name="returnStatus" id="returnStatus">
							<option value="0">{% lang 'AllStatuses' %}</option>
							{{ ReturnStatusList|safe }}
						<input type="image" name="SearchButton" id="SearchButton" src="images/searchicon.gif" border="0" style="padding-left: 10px; vertical-align: top;" />
					</td>
				</tr>
				<tr>
					<td nowrap>
						<a href="index.php?ToDo=searchReturns">{% lang 'AdvancedSearch' %}</a>
						<span style="display:{{ HideClearResults|safe }}">| <a id="SearchClearButton" href="index.php?ToDo=viewReturns">{% lang 'ClearResults' %}</a></span>
					</td>
				</tr>
				</form>
				</table>
			</td>
			</tr>
			</table>
		</td>
		</tr>
		<tr>
		<td style="display: {{ DisplayGrid|safe }}">
			<form name="frmReturns1" id="frmReturns1" method="post" action="index.php?ToDo=deleteReturns">
				<div class="GridContainer">
					{{ ReturnsDataGrid|safe }}
				</div>
			</form>
		</td></tr>
	</table>
	</div>
		<div id="ViewsMenu" class="DropDownMenu DropShadow" style="display: none; width:200px">
				<ul>
					{{ CustomSearchOptions|safe }}
				</ul>
				<hr />
				<ul>
					<li><a href="index.php?ToDo=createReturnView" style="background-image:url('images/view_add.gif'); background-repeat:no-repeat; background-position:5px 5px; padding-left:28px">{% lang 'CreateANewView' %}</a></li>
					<li style="display:{{ HideDeleteViewLink|safe }}"><a onclick="$('#ViewsMenu').hide(); ConfirmDeleteCustomSearch('{{ CustomSearchId|safe }}')" href="javascript:void(0)" style="background-image:url('images/view_del.gif'); background-repeat:no-repeat; background-position:5px 5px; padding-left:28px">{% lang 'DeleteThisView' %}</a></li>
				</ul>
			</div>
		</div>
		</div>
		</div>
	</div>

	<script type="text/javascript">
		function ConfirmDeleteSelected()
		{
			if($('.DeleteCheck:checked').length == 0) {
				alert('{% jslang 'ChooseReturnsToDelete' %}');
			}
			else {
				if(confirm('{% jslang 'ConfirmDeleteReturns' %}')) {
					$('#frmReturns1').submit();
				}
			}
		}

		$(function(){
			$('.returnStatusSelect').each(function(){
				// on dom ready, store the initial value of each return status select
				$(this).data('initialValue', $(this).val());
			}).live('change', function(){
				// this replaces the ONCHANGE= that used to be on each select in manage.row template
				var $$ = $(this),
					returnId = $$.attr('id').replace('status_', '');

				if (!returnId) {
					return false;
				}

				var statusId = $$.val();
				var statusText = $$.find(':selected').text();
				UpdateReturnStatus(returnId, statusId, statusText, $$.data('initialValue'))
			});
		});

		function UpdateReturnStatus(returnid, statusid, statustext, initialstatus) {
			// this fn could use rewriting to make better use of $.data -- leaving alone for now and adding the .live() binding instead

			if(confirm('{% jslang 'ConfirmReturnStatusChange' %}' + ' ' + statustext.toLowerCase() + "?")) {
				$('#ajax_status_'+returnid).show();
				$.ajax({
					url: 'remote.php?w=updateReturnStatus&returnId='+returnid+'&status='+statusid,
					success: function(response) {
						$('#ajax_status_'+returnid).hide();
						if(response == 0) {
							alert('{% jslang 'FailedUpdateReturnStatus' %}');
						}
					},
					error: function() {
						alert('{% jslang 'FailedUpdateReturnStatus' %}');
					}
				});
				$('#status_'+returnid).attr('lastStatus', statusid);
			}
			else {
				if($('#status_'+returnid).attr('lastStatus')) {
					$('#status_'+returnid).val($('#status_'+returnid).attr('lastStatus'));
				}
				else {
					$('#status_'+returnid).val(initialstatus);
				}
			}
		}

		function ConfirmDeleteCustomSearch(id) {
			if(confirm('{% jslang 'ConfirmDeleteCustomSearch' %}')) {
				document.location.href = "index.php?ToDo=deleteCustomReturnSearch&searchId="+id;
			}
		}

		function QuickReturnView(id) {
			var tr = document.getElementById("tr"+id);
			var trQ = document.getElementById("trQ"+id);
			var tdQ = document.getElementById("tdQ"+id);
			var img = document.getElementById("expand"+id);

			if(img.src.indexOf("plus.gif") > -1)
			{
				img.src = "images/minus.gif";

				for(i = 0; i < tr.childNodes.length; i++)
				{
					if(tr.childNodes[i].style != null)
						tr.childNodes[i].style.backgroundColor = "#dbf3d1";
				}

				trQ.style.display = "";
				$(trQ).find('.QuickView').load('remote.php?w=returnQuickView&returnId='+id, {}, function() {
					trQ.style.display = "";
				});
			}
			else
			{
				img.src = "images/plus.gif";

				for(i = 0; i < tr.childNodes.length; i++)
				{
					if(tr.childNodes[i].style != null)
						tr.childNodes[i].style.backgroundColor = "";
				}
				trQ.style.display = "none";
			}
		}

		function UpdateReturnNotes(id) {
			var returnNotes = $('#ReturnNotes' + id),
				submitButton = returnNotes.find('input[type=button]');

			submitButton
				.attr('disabled', 'disabled');

			$.ajax({
				url: 'remote.php?w=updateReturnNotes',
				type: 'POST',
				data: {
					returnId: id,
					returnNotes: returnNotes.find('textarea[name=returnNotes]').val()
				},
				complete: function() {
					submitButton.removeAttr('disabled');
				},
				success: function(msg) {
					if (msg == 1) {
						display_success('ReturnsStatus', '{% jslang 'ReturnNotesUpdated' %}');
						return;
					}

					alert('{% jslang 'ReturnNotesUpdateError' %}');
				},
				error: function() {
					display_error('ReturnsStatus', '{% jslang 'ReturnNotesUpdateError' %}');
				}
			});
		}

		function ConfirmIssueCredit(amount) {
			if(confirm('{% jslang 'ConfirmReturnIssueCredit' %}'.replace('%s', amount))) {
				return true;
			}
			return false;
		}
</script>
