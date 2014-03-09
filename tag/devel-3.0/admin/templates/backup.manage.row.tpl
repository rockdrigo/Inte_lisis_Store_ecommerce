<tr class="GridRow" onmouseover="this.className='GridRowOver'" onmouseout="this.className='GridRow'">
	<td align="center" style="width:25px">
		<input type="checkbox" name="backup[]" class="DeleteBackup" value="{{ FileName|safe }}">
	</td>
	<td align="center" style="width:18px;">
		<img src='images/{{ BackupImage|safe }}.gif' title='{{ BackupType|safe }}'>
	</td>
	<td>
		{{ FileName|safe }}
	</td>
	<td align="right">
		{{ FileSize|safe }}
	</td>
	<td>
		{{ ModifiedTime|safe }}
	</td>
	<td>
		<a title='{{ DownloadOpen|safe }}' class='Action' href='{{ ViewLink|safe }}' target='_blank'>{{ DownloadOpen|safe }}</a>
	</td>
</tr>