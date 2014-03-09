	<div class="BodyContainer">
	<table id="Table13" cellSpacing="0" cellPadding="0" width="100%">
		<tr>
			<td class="Heading1">{% lang 'ViewCoupons' %}</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{{ CouponIntro|safe }}</p>
			{{ Message|safe }}
			<table id="IntroTable" cellspacing="0" cellpadding="0" width="100%">
			<tr>
			<td class="Intro" valign="top">
				<input type="button" name="IndexAddButton" value="{% lang 'CreateCoupon' %}..." id="IndexCreateButton" class="SmallButton" onclick="document.location.href='index.php?ToDo=createCoupon'" /> &nbsp;<input type="button" name="IndexDeleteButton" value="{% lang 'DeleteSelected' %}" id="IndexDeleteButton" class="SmallButton" onclick="ConfirmDeleteSelected()" {{ DisableDelete|safe }} />
			</td>
			</tr>
			</table>
		</td>
		</tr>
		<tr>
		<td style="display: {{ DisplayGrid|safe }}">
			<form name="frmCoupons" id="frmCoupons" method="post" action="index.php?ToDo=deleteCoupons">
				<div class="GridContainer">
					{{ CouponsDataGrid|safe }}
				</div>
			</form>
		</td></tr>
	</table>
	</div>

	<script type="text/javascript">

		function ConfirmDeleteSelected()
		{
			var fp = document.getElementById("frmCoupons").elements;
			var c = 0;

			for(i = 0; i < fp.length; i++)
			{
				if(fp[i].type == "checkbox" && fp[i].checked)
					c++;
			}

			if(c > 0)
			{
				if(confirm("{% lang 'ConfirmDeleteCoupons' %}"))
					document.getElementById("frmCoupons").submit();
			}
			else
			{
				alert("{% lang 'ChooseCoupons' %}");
			}
		}

		function ToggleDeleteBoxes(Status)
		{
			var fp = document.getElementById("frmCoupons").elements;

			for(i = 0; i < fp.length; i++)
				fp[i].checked = Status;
		}

		function CouponClipboard(Data)
		{
			if (window.clipboardData)
			{
				window.clipboardData.setData("Text", Data);
				alert("{% lang 'CopiedClipboard' %}");
			}
			else
			{
				alert("{% lang 'FeatureOnlyInIE' %}");
			}
		}

	</script>