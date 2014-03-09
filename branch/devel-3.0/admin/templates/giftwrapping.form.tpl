<form action="index.php?ToDo={{ FormAction|safe }}" id="frmWrap" method="post" onsubmit="return ValidateForm(CheckWrapForm)" enctype="multipart/form-data">
	<input type="hidden" name="wrapId" id="wrapId" value="{{ WrapId|safe }}" />
	<input type="hidden" name="currentTab" value="{{ CurrentTab|safe }}" id="currentTab" />
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
					<input type="submit" name="SubmitButton1" value="{% lang 'Save' %}" class="FormButton" />&nbsp;
					<input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
				</p>
			</td>
		</tr>

		<tr>
			<td>
				<div id="div0">
					<table width="100%" class="Panel">
						<tr>
							<td class="Heading2" colspan="2">{% lang 'GiftWrapSettings' %}</td>
						</tr>

						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span> {% lang 'WrapName' %}:
							</td>
							<td>
								<input type="text" name="wrapname" id="wrapname" class="Field250" value="{{ WrapName|safe }}" />
								<img onmouseout="HideHelp('wrapnamehelp');" onmouseover="ShowHelp('wrapnamehelp', '{% lang 'WrapName' %}', '{% lang 'WrapNameHelp' %}')" src="images/help.gif" alt="" border="0" />
								<div style="display:none" id="wrapnamehelp"></div>
							</td>
						</tr>

						<tr>
							<td class="FieldLabel">
								<span class="Required">&nbsp;</span> {% lang 'WrapImage' %}:
							</td>
							<td>
								<input type="file" name="wrapimage" id="wrapimage" />
								<img onmouseout="HideHelp('wrapimagehelp');" onmouseover="ShowHelp('wrapimagehelp', '{% lang 'WrapImage' %}', '{% lang 'WrapImageHelp' %}')" src="images/help.gif" alt="" border="0" />
								<div style="display:none" id="wrapimagehelp"></div>
								<span style="{{ HideCurrentWrapImage|safe }}">
									Currently <a href="../{{ ImageDirectory|safe }}/{{ WrapImage|safe }}" target="_blank">{{ WrapImage|safe }}</a>
								</span>
							</td>
						</tr>

						<tr>
							<td class="FieldLabel">
								<span class="Required">*</span> {% lang 'WrapPrice' %}:
							</td>
							<td>
								{{ LeftCurrencyToken|safe }}
								<input type="text" name="wrapprice" id="wrapprice" class="Field50" value="{{ GiftWrapPrice|safe }}" />
								{{ RightCurrencyToken|safe }}
								<img onmouseout="HideHelp('wrappricehelp');" onmouseover="ShowHelp('wrappricehelp', '{% lang 'WrapPrice' %}', '{% lang 'WrapPriceHelp' %}')" src="images/help.gif" alt="" border="0" />
								<div style="display:none" id="wrappricehelp"></div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								&nbsp;&nbsp; <label for="wrapgiftmessage">{% lang 'WrapGiftMessage' %}?</label>
							</td>
							<td>
								<label><input type="checkbox" name="wrapallowcomments" value="1" id="wrapgiftmessage" {{ GiftWrapAllowCommentsChecked|safe }} /> {% lang 'YesAllowWrapGiftMessage' %}</label>
								<img onmouseout="HideHelp('wrapallowcommentshelp');" onmouseover="ShowHelp('wrapallowcommentshelp', '{% lang 'WrapGiftMessage' %}', '{% lang 'WrapGiftMessageHelp' %}')" src="images/help.gif" alt="" border="0" />
								<div style="display:none" id="wrapallowcommentshelp"></div>
							</td>
						</tr>

						<tr>
							<td class="FieldLabel">
								&nbsp;&nbsp; <label for="wrapvisible">{% lang 'Visible' %}?</label>
							</td>
							<td>
								<label><input type="checkbox" name="wrapvisible" value="1" id="wrapvisible" {{ GiftWrapVisibleChecked|safe }} /> {% lang 'YesWrapVisible' %}</label>
								<img onmouseout="HideHelp('wrapvisiblehelp');" onmouseover="ShowHelp('wrapvisiblehelp', '{% lang 'Visible' %}', '{% lang 'WrapVisibleHelp' %}')" src="images/help.gif" alt="" border="0" />
								<div style="display:none" id="wrapvisiblehelp"></div>
							</td>
						</tr>
					</table>
					<table border="0" cellspacing="0" cellpadding="2" width="100%" class="PanelPlain">
					<tr>
						<td width="200" class="FieldLabel">
							&nbsp;
						</td>
						<td>
							<input type="submit" name="SubmitButton1" value="{% lang 'Save' %}" class="FormButton" />&nbsp;
							<input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" />
						</td>
					</tr>
				</table>
			</div>
		</td>
	</tr>
	</table>
</div>
</form>
<script type="text/javascript">
	function CheckWrapForm() {
		if(!$('#wrapname').val()) {
			alert('{% lang 'EnterWrapName' %}');
			$('#wrapname').focus();
			return false;
		}

		var price = $('#wrapprice');
		if(isNaN(priceFormat(price.val())) || price.val() == '') {
			alert('{% lang 'EnterWrapPrice' %}');
			price.focus();
			price.select();
			return false;
		}

		return true;
	}

	function ConfirmCancel()
	{
		if(confirm('{% lang 'ConfirmCancel' %}'))
		{
			document.location.href='index.php?ToDo=viewGiftWrapping';
		}
		else
		{
			return false;
		}
	}
</script>