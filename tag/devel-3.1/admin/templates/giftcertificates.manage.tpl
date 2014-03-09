	<div class="BodyContainer">

	<table id="Table13" cellSpacing="0" cellPadding="0" width="100%">
		<tr>
			<td class="Heading1">
				{% lang 'View' %}: <a href="#" style="color:#005FA3" id="ViewsMenuButton" class="PopDownMenu">{{ ViewName|safe }} <img width="8" height="5" src="images/arrow_blue.gif" border="0" /></a>
			</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{% lang 'ManageGiftCertificatesIntro' %}</p>
			<div id="GiftCertificatesStatus">
				{{ Message|safe }}
			</div>
			<table id="IntroTable" cellspacing="0" cellpadding="0" width="100%">
			<tr>
			<td class="Intro" valign="top">
				<input type="button" name="IndexDeleteButton" value="{% lang 'DeleteSelected' %}" id="IndexDeleteButton" class="SmallButton" onclick="ConfirmDeleteSelected()" {{ DisableDelete|safe }} />
			</td>
			<td class="SmallSearch" align="right">
				<table id="Table16" style="display:{{ DisplaySearch|safe }}">
				<tr>
					<td class="text" nowrap align="right">
						<form name="frmGiftCertificates" id="frmGiftCertificates" action="index.php?{{ SortURL|safe }}" method="get">
						<input type="hidden" name="ToDo" value="viewGiftCertificates" />
						<input name="searchQuery" id="searchQuery" type="text" value="{{ Query|safe }}" id="SearchQuery" class="SearchBox" style="width:150px" />&nbsp;
						<select name="certificateStatus" id="certificateStatus">
							<option value="">{% lang 'AllStatuses' %}</option>
							{{ GiftCertificateStatusList|safe }}
						</select>
						<input type="image" name="SearchButton" style="padding-left: 10px; vertical-align: top;" id="SearchButton" src="images/searchicon.gif" border="0" />
						</form>
					</td>
				</tr>
				<tr>
					<td nowrap>
						<a href="index.php?ToDo=searchGiftCertificates">{% lang 'AdvancedSearch' %}</a>
						<span style="display:{{ HideClearResults|safe }}">| <a id="SearchClearButton" href="index.php?ToDo=viewGiftCertificates">{% lang 'ClearResults' %}</a></span>
					</td>
				</tr>
				</table>
			</td>
			</tr>
			</table>
		</td>
		</tr>
		<tr>
		<td style="display: {{ DisplayGrid|safe }}">
			<form name="frmGiftCertificates1" id="frmGiftCertificates1" method="post" action="index.php?ToDo=deleteGiftCertificates">
				<div class="GridContainer">
					{{ GiftCertificatesDataGrid|safe }}
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
					<li><a href="index.php?ToDo=createGiftCertificateView" style="background-image:url('images/view_add.gif'); background-repeat:no-repeat; background-position:5px 5px; padding-left:28px">{% lang 'CreateANewView' %}</a></li>
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
				alert('{% lang 'ChooseGiftCertificatesToDelete' %}');
			}
			else {
				if(confirm('{% lang 'ConfirmDeleteGiftCertificates' %}')) {
					$('#frmGiftCertificates1').submit();
				}
			}
		}

		function UpdateGiftCertificateStatus(giftcertid, statusid, statustext) {
			$('#ajax_status_'+giftcertid).show();
			$.ajax({
				url: 'remote.php?w=updateGiftCertificateStatus&giftCertificateId='+giftcertid+'&status='+statusid,
				success: function(response) {
					$('#ajax_status_'+giftcertid).hide();
					if(response == 0) {
						alert('{% lang 'FailedUpdateGiftCertificateStatus' %}');
					}
				},
				error: function() {
					alert('{% lang 'FailedUpdateGiftCertificateStatus' %}');
				}
			});
		}

		function ConfirmDeleteCustomSearch(id) {
			if(confirm('{% lang 'ConfirmDeleteCustomSearch' %}')) {
				document.location.href = "index.php?ToDo=deleteCustomGiftCertificateSearch&searchId="+search_id;
			}
		}

		function QuickGiftCertificateView(id) {
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

				$(trQ).find('.QuickView').load('remote.php?w=giftCertificateQuickView&giftCertificateId='+id, {}, function() {
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
</script>
