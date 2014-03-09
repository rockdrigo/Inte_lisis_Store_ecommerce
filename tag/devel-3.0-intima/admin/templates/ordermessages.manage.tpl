
	<div class="BodyContainer">
	<table id="Table13" cellSpacing="0" cellPadding="0" width="100%">
		<tr>
			<td class="Heading1">{{ ViewOrderMessages|safe }}</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{{ MessageIntro|safe }}</p>
			{{ Message|safe }}
			<table id="IntroTable" cellspacing="0" cellpadding="0" width="100%">
				<tr>
					<td class="Intro" valign="top">
						<input type="button" name="IndexAddButton" value="{% lang 'CreateMessage' %}..." id="IndexCreateButton" class="SmallButton" onclick="document.location.href='index.php?ToDo=createOrderMessage&amp;orderId={{ OrderId|safe }}'" />
						&nbsp;<input type="button" name="IndexDeleteButton" value="{% lang 'DeleteSelected' %}" id="IndexDeleteButton" class="SmallButton" onclick="ConfirmDeleteSelected()" {{ DisableDelete|safe }} />
						&nbsp;<input type="button" name="IndexReturnButton" value="{% lang 'ViewOrders' %}" class="SmallButton" onclick="document.location.href='index.php?ToDo=viewOrders'" />
					</td>
				</tr>
			</table>
			<br />
		</td>
		</tr>
		<tr>
		<td style="display: {{ DisplayGrid|safe }}">
			<form name="frmMessages" id="frmMessages" method="post" action="index.php?ToDo=deleteOrderMessages">
			<input type="hidden" name="orderId" value="{{ OrderId|safe }}">
			<table class="GridPanel" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%;">
			<tr class="Heading3">
				<td width="25" align="center"><input type="checkbox" onclick="ToggleDeleteBoxes(this.checked)"></td>
				<td width="25">&nbsp;</td>
				<td width="25">&nbsp;</td>
				<td>
					{% lang 'MessageSubject' %}
				</td>
				<td>
					{% lang 'OrderMessage' %}
				</td>
				<td>
					{% lang 'OrderFrom' %}
				</td>
				<td>
					{% lang 'OrderDate' %}
				</td>
				<td nowrap>
					{% lang 'Status' %}
				</td>
				<td style="width:100px;">
					{% lang 'Action' %}
				</td>
			</tr>
			{{ MessageGrid|safe }}
		</table>
		</form>
		</td></tr>
	</table>
	</div>

	<script type="text/javascript">

		function ToggleDeleteBoxes(Status)
		{
			var fp = document.getElementById("frmMessages").elements;

			for(i = 0; i < fp.length; i++)
				fp[i].checked = Status;
		}

		function ConfirmDeleteSelected() {

			var fp = document.getElementById("frmMessages").elements;
			var c = 0;

			for(i = 0; i < fp.length; i++)
			{
				if(fp[i].type == "checkbox" && fp[i].checked)
					c++;
			}

			if(c > 0)
			{
				if(confirm("{% lang 'ConfirmDeleteMessages' %}"))
					document.getElementById("frmMessages").submit();
			}
			else
			{
				alert("{% lang 'ChooseMessages' %}");
			}
		}

	</script>

