<form method="post" id="SincroForm" action="index.php?ToDo=viewsincro" onsubmit="return SearchSystemSincro(this);">
	<input type="hidden" name="SortURL" id="SortURL" value="index.php?ToDo=viewsincro{{ SortURL|safe }}" />
	<input type="hidden" name="CurrentTab" id="CurrentTab1" value="{{ CurrentTab|safe }}" />
	<table id="SystemSincroOptions" class="IntroTable" cellspacing="0" cellpadding="0" width="100%">
		<tr>
			<td class="Intro" style="padding-top: 10px;">
				<input type="button" name="IndexDeleteButton" value="{% lang 'DeleteSelected' %}" id="IndexDeleteButton" class="SmallButton" onclick="ConfirmDeleteSelectedS()" {{ DisableDelete|safe }}  />
				<input type="button" name="DeleteAll" value="{% lang 'DeleteAll' %}" class="SmallButton" onclick="ConfirmDeleteAllS()" {{ DisableDelete|safe }}  />
			</td>
			<td align="right" nowrap="nowrap" style="padding-top: 10px;">
				<select name="SincStatus" id="SincStatus">
					<option>Todos los estatus</option>
					<option value="0" {{ Estatus0Selected|safe }}>NEW</option>
					<option value="1" {{ Estatus1Selected|safe }}>SLC</option>
					<option value="2" {{ Estatus2Selected|safe }}>RES</option>
				</select>
				&nbsp;
				<input type="text" id="xmlSummary" class="Button" value="{{ XmlValue|safe }}" size="20" />
			</td>
			<td width="1" style="padding-left: 5px;">
				<input id="SearchButton" type="image" border="0" style="vertical-align: middle;" src="images/searchicon.gif" name="SearchButton" />
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td align="right">
				<a href="index.php?ToDo=viewsincro" style="display: {{ HideClearResults|safe }}" id="SearchClearButton" onclick="return ClearSystemResultsSincro(this);">{% lang 'ClearResults' %}</a>
			</td>
			<td>&nbsp;</td>
		</tr>
	</table>
	<table class="GridPanel SortableGrid" cellspacing="1" cellpadding="2" border="0" style="width:100%;">
		<tr align="right">
			<td colspan="8" style="padding:6px 0px 6px 0px" class="PagingNav">
				{{ Nav|safe }}
			</td>
		</tr>

		<tr class="Heading3">
			<td align="center" width="1"><input type="checkbox" onclick="$(this).parents('form').find('input[type=checkbox]').attr('checked', this.checked);"></td>
			<td>
				Consecutivo &nbsp;
				{{ SortLinksConsecutivo|safe }}
			</td>
			<td>
				&nbsp;
			</td>
			<td>
				xml &nbsp;
				{{ SortLinksXml|safe }}
			</td>
			<td>
				Estatus &nbsp;
				{{ SortLinksEstatus|safe }}
			</td>
			<td>
				Creado &nbsp;
				{{ SortLinksCreado|safe }}
			</td>
		</tr>
		{{ SincroGrid|safe }}
		<tr align="right">
			<td colspan="8" style="padding:6px 0px 6px 0px" class="PagingNav">
				{{ Nav|safe }}
			</td>
		</tr>	
	</table>
</form>

<script type="text/javascript">
	function ShowLogInfo(id)
		{
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
	
				$(trQ).find('.QuickView').load('remote.php?w=sincroInfoQuickView&SincroId='+id, {}, function() {
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
		
	function ConfirmDeleteSelectedS() {
		if($('.DeleteCheck:checked').length == 0) {
			alert('{% lang 'ChooseLogEntry' %}');
		}
		else {
			if(confirm('{% lang 'ConfirmDeleteLogEntries' %}')) {
				g('SincroForm').action = g('SincroForm').action.replace('viewsincro', 'deleteSystemSincro');
				g('SincroForm').method = 'post';
				g('SincroForm').submit();
			}
		}
	}

	function ConfirmDeleteAllS() {
		if(confirm('{% lang 'ConfirmDeleteAllSystemLogEntries' %}')) {
			g('SincroForm').action = g('SincroForm').action.replace('viewsincro', 'deleteAllSystemSincro');
			g('SincroForm').method = 'post';
			g('SincroForm').submit();
		}
	}
	
	function SearchSystemSincro(f) {

		var searchURL = '';
		if($('#SincStatus').val() >= 0) {
			searchURL += '&SincStatus='+$('#SincStatus').val();
			alert('search_url = '.$searchURL);
		}


		if($('#xmlSummary').val() != "") {
			searchURL += '&xmlsummary='+escape($('#XmlSummary').val());
			alert('b');
		}
		
		alert($('#SortURL').val());
		alert($('#searchURL').val());
		
		$(f).parents('.GridContainer').load($('#SortURL').val()+searchURL, '', function() {
			BindAjaxGridSorting();
		});
		return false;
	}

	function ClearSystemResultsSincro(f) {

		$(f).parents('.GridContainer').load($('#SortURL').val(), '', function() {
			BindAjaxGridSorting();
		});
		return false;
	}
	
</script>