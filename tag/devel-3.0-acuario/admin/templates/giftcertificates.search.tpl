	<form enctype="multipart/form-data" action="index.php" id="frmSearch" method="get" onsubmit="return ValidateForm(CheckSearchForm)">
	<input type="hidden" name="ToDo" value="searchGiftCertificatesRedirect" />
	<div class="BodyContainer">
	<table class="OuterPanel">
	  <tr>
		<td class="Heading1" id="tdHeading">{% lang 'SearchGiftCertificates' %}</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{% lang 'SearchGiftCertificatesIntro' %}</p>
			{{ Message|safe }}
			<p><input type="submit" name="SubmitButton1" value="{% lang 'Search' %}" class="FormButton">&nbsp; <input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()"></p>
		</td>
	  </tr>
		<tr>
			<td>
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'AdvancedSearch' %}</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'SearchKeywords' %}:
					</td>
					<td>
						<input type="text" id="searchQuery" name="searchQuery" class="Field250">
						<img onmouseout="HideHelp('d1');" onmouseover="ShowHelp('d1', '{% lang 'SearchKeywords' %}', '{% lang 'SearchKeywordsGiftCertificateHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
						<div style="display:none" id="d1"></div>
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'GiftCertificateStatus' %}:
					</td>
					<td>
						<select id="certificateStatus" name="certificateStatus" class="Field250">
							<option value="">{% lang 'ChooseAGiftCertificateStatus' %}</option>
							{{ GiftCertificateStatusOptions|safe }}
						</select>
					</td>
				</tr>
				<tr><td class="Gap" colspan="2"></td></tr>
			 </table>
			</td>
		</tr>
		<tr>
			<td>
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'SearchByRange' %}</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'GiftCertificateId' %}:
					</td>
					<td>
						{% lang 'SearchFrom' %} &nbsp;&nbsp;&nbsp;&nbsp;<input type="text" id="certificateFrom" name="certificateFrom" class="Field50"> {% lang 'SearchTo' %}
						&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" id="certificateTo" name="certificateTo" class="Field50">
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'GiftCertificateAmount' %}:
					</td>
					<td>
						{% lang 'SearchFrom' %} &nbsp;{{ CurrencyToken|safe }}&nbsp;<input type="text" id="amountFrom" name="amountFrom" class="Field50"> {% lang 'SearchTo' %}
						&nbsp;{{ CurrencyToken|safe }}&nbsp;<input type="text" id="amountTo" name="amountTo" class="Field50">
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'GiftCertificateBalance' %}:
					</td>
					<td>
						{% lang 'SearchFrom' %} &nbsp;{{ CurrencyToken|safe }}&nbsp;<input type="text" id="balanceFrom" name="balanceFrom" class="Field50"> {% lang 'SearchTo' %}
						&nbsp;{{ CurrencyToken|safe }}&nbsp;<input type="text" id="balanceTo" name="balanceTo" class="Field50">
					</td>
				</tr>
				<tr><td class="Gap" colspan="2"></td></tr>
			 </table>
			</td>
		</tr>
		<tr>
			<td>
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'SearchByDate' %}</td>
				</tr>
				<tr><td class="Gap"></td></tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'GiftCertificatePurchaseDate' %}:
					</td>
					<td>
						<select name="dateRange" id="dateRange" onchange="ToggleRange()" class="Field250">
							<option value="">{% lang 'ChooseGiftCertificateDate' %}</option>
							<option value="today">{% lang 'Today' %}</option>
							<option value="yesterday">{% lang 'Yesterday' %}</option>
							<option value="day">{% lang 'Last24Hours' %}</option>
							<option value="week">{% lang 'Last7Days' %}</option>
							<option value="month">{% lang 'Last30Days' %}</option>
							<option value="this_month">{% lang 'ThisMonth' %}</option>
							<option value="this_year">{% lang 'ThisYear' %}</option>
							<option value="custom">{% lang 'CustomPeriod' %}</option>
						</select>
						<div id="dateRangeCustom" style="margin-left: 30px; margin-top: 10px;">
							{% lang 'SearchFrom' %} <input class="plain" name="fromDate" id="dc1" size="12" onfocus="this.blur()" readonly><a href="javascript:void(0)" onclick="if(self.gfPop)gfPop.fStartPop(g('dc1'),g('dc2'));return false;" HIDEFOCUS><img name="popcal" align="absmiddle" src="images/calbtn.gif" width="34" height="22" border="0" alt=""></a>
							{% lang 'SearchTo' %} <input class="plain" name="toDate" id="dc2" size="12" onfocus="this.blur()" readonly><a href="javascript:void(0)" onclick="if(self.gfPop)gfPop.fEndPop(g('dc1'),g('dc2'));return false;" HIDEFOCUS><img name="popcal" align="absmiddle" src="images/calbtn.gif" width="34" height="22" border="0" alt=""></a>
						</div>
					</td>
				</tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'ExpiryDateRange' %}:
					</td>
					<td>
						<select name="expiryRange" id="expiryRange" onchange="ToggleExpiryRange()" class="Field250">
							<option value="">{% lang 'ChooseGiftCertificateExpiryDate' %}</option>
							<option value="today">{% lang 'Today' %}</option>
							<option value="tomorrow">{% lang 'Yesterday' %}</option>
							<option value="week">{% lang 'Next7Days' %}</option>
							<option value="month">{% lang 'Next30Days' %}</option>
							<option value="this_month">{% lang 'ThisMonth' %}</option>
							<option value="next_month">{% lang 'NextMonth' %}</option>
							<option value="this_year">{% lang 'ThisYear' %}</option>
							<option value="next_year">{% lang 'NextYear' %}</option>
							<option value="custom">{% lang 'CustomPeriod' %}</option>
						</select>
						<div id="expiryRangeCustom" style="margin-left: 30px; margin-top: 10px;">
							{% lang 'SearchFrom' %} <input class="plain" name="expiryFromDate" id="dc3" size="12" onfocus="this.blur()" readonly><a href="javascript:void(0)" onclick="if(self.gfPop)gfPop.fStartPop(g('dc3'),g('dc4'));return false;" HIDEFOCUS><img name="popcal" align="absmiddle" src="images/calbtn.gif" width="34" height="22" border="0" alt=""></a>
							{% lang 'SearchTo' %} <input class="plain" name="expiryToDate" id="dc4" size="12" onfocus="this.blur()" readonly><a href="javascript:void(0)" onclick="if(self.gfPop)gfPop.fEndPop(g('dc3'),g('dc4'));return false;" HIDEFOCUS><img name="popcal" align="absmiddle" src="images/calbtn.gif" width="34" height="22" border="0" alt=""></a>
						</div>
					</td>
				</tr>
				<tr><td class="Gap" colspan="2"></td></tr>
			 </table>
			</td>
		</tr>
		<tr>
			<td>
			  <table class="Panel">
				<tr>
				  <td class="Heading2" colspan=2>{% lang 'SortOrder' %}</td>
				</tr>
				<tr><td class="Gap"></td></tr>
				<tr>
					<td class="FieldLabel">
						&nbsp;&nbsp;&nbsp;{% lang 'SortOrder' %}:
					</td>
					<td>
						<select name="sortField" class="Field120">
							<option value="giftcertid">{% lang 'GiftCertificateId' %}</option>
							<option value="giftcertcode">{% lang 'GiftCertificateCode' %}</option>
							<option value="customername">{% lang 'GiftCertificatePurchasedBy' %}</option>
							<option value="giftcertamount">{% lang 'GiftCertificateAmount' %}</option>
							<option value="giftcertbalance">{% lang 'GiftCertificateBalance' %}</option>
							<option value="giftcertdatepurchased">{% lang 'GiftCertificatePurchaseDate' %}</option>
							<option value="giftcertstatus">{% lang 'GiftCertificateStatus' %}</option>
						</select>
						in&nbsp;
						<select name="sortOrder" class="Field110">
						<option value="asc">{% lang 'AscendingOrder' %}</option>
						<option value="desc">{% lang 'DescendingOrder' %}</option>
					</td>
				</tr>
				<tr>
					<td class="Gap">&nbsp;</td>
					<td class="Gap"><input type="submit" name="SubmitButton1" value="{% lang 'Search' %}" class="FormButton">&nbsp; <input type="button" name="CancelButton1" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()">
					</td>
				</tr>
				<tr><td class="Gap" colspan="2"></td></tr>
				<tr><td class="Gap" colspan="2"></td></tr>
				<tr><td class="Sep" colspan="2"></td></tr>
			 </table>
			</td>
		</tr>
	</table>
	<iframe width=132 height=142 name="gToday:contrast:agenda.js" id="gToday:contrast:agenda.js" src="calendar/ipopeng.htm" scrolling="no" frameborder="0" style="visibility:visible; z-index:999; position:absolute; left:-500px; top:0px;"></iframe>
	</div>
	</form>

	<script type="text/javascript">

		function ToggleRange()
		{
			var range = g('dateRange');
			if($('#dateRange').val() == "custom") {
				$('#dateRangeCustom').show();
			}
			else
			{
				$('#dateRangeCustom').hide();
			}
		}

		function ToggleExpiryRange()
		{
			var range = g('expiryRange');
			if($('#expiryRange').val() == "custom") {
				$('#expiryRangeCustom').show();
			}
			else
			{
				$('#expiryRangeCustom').hide();
			}
		}

		ToggleRange();
		ToggleExpiryRange();

		function ConfirmCancel() {
			if(confirm("{% lang 'ConfirmCancelSearch' %}")) {
				history.go(-1);
			}
		}

		function CheckSearchForm() {
			if($('#certificateFrom').val() != "" && isNaN($('#certificateFrom').val())) {
				alert("{% lang 'SearchEnterValidCertificateId' %}");
				$('#certificateFrom').focus();
				$('#certificateFrom').select();
				return false;
			}

			if($('#certificateTo').val() != "" && isNaN($('#certificateTo').val())) {
				alert("{% lang 'SearchEnterValidCertificateId' %}");
				$('#certificateTo').focus();
				$('#certificateTo').select();
				return false;
			}

			if($('#amountFrom').val() != "" && isNaN($('#amountFrom').val())) {
				alert("{% lang 'SearchEnterValidAmount' %}");
				$('#amountFrom').focus();
				$('#amountFrom').select();
				return false;
			}

			if($('#amountTo').val() != "" && isNaN($('#amountTo').val())) {
				alert("{% lang 'SearchEnterValidAmount' %}");
				$('#amountTo').focus();
				$('#amountTo').select();
				return false;
			}

			if($('#balanceFrom').val() != "" && isNaN($('#balanceFrom').val())) {
				alert("{% lang 'SearchEnterValidBalance' %}");
				$('#balanceFrom').focus();
				$('#balanceFrom').select();
				return false;
			}

			if($('#balanceTo').val() != "" && isNaN($('#balanceTo').val())) {
				alert("{% lang 'SearchEnterValidBalance' %}");
				$('#balanceTo').focus();
				$('#balanceTo').select();
				return false;
			}

			if($('#dateRange').val() == "custom" && $('#dc1').val() == $('#dc2').val()) {
				alert("{% lang 'SearchChooseDifferentDates' %}");
				return false;
			}

			if($('#expiryRange').val() == "custom" && $('#dc3').val() == $('#dc4').val()) {
				alert("{% lang 'SearchChooseDifferentExpiryDates' %}");
				return false;
			}

			return true;
		}

	</script>
