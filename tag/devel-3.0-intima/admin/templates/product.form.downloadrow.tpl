									<tr id="download_{{ DownloadId|safe }}" class="GridRow DownloadGridRow" onmouseover="if(this.className != 'QuickView') { this.oldClass = this.className; this.className='GridRowOver'; }" onmouseout="if(this.className != 'QuickView') { this.className=this.oldClass }">
										<td align="center" style="width:25px">
											<img src="images/download.gif" />
										</td>
										<td class="FileName" style="width: 40%">
											{{ DownloadName|safe }}
										</td>
										<td class="FileSize" style="width: 100px;" align="right" nowrap="nowrap">
											{{ DownloadSize|safe }}
										</td>
										<td align="right" nowrap="nowrap">
											{{ NumDownloads|safe }}
										</td>
										<td class="MaxDownloads" style="width: 150px;" nowrap="nowrap">
											{{ MaxDownloads|safe }}
										</td>
										<td class="ExpiresAfter" style="width: 150px;" nowrap="nowrap">
											{{ ExpiresAfter|safe }}
										</td>
										<td style="width: 130px;" nowrap="nowrap">
											<a href="index.php?ToDo=downloadProductFile&amp;downloadid={{ DownloadId|safe }}" target="_blank">{% lang 'ViewDownload' %}</a>&nbsp;&nbsp;
											<a href="#" onclick="editDownload('{{ DownloadId|safe }}'); return false;">{% lang 'Edit' %}</a>&nbsp;&nbsp;<a href="#" onclick="return deleteDownload('{{ DownloadId|safe }}'); return false;">{% lang 'Delete' %}</a>
										</td>
									</tr>
									<tr id="download_edit_{{ DownloadId|safe }}" style="display: none;">
										<td>&nbsp;</td>
										<td class="QuickView" colspan="3"></td>
										<td colspan="3">&nbsp;</td>
									</tr>