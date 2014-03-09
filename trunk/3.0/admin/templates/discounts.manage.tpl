	<div class="BodyContainer">
	<table id="Table13" cellSpacing="0" cellPadding="0" width="100%">
		<tr>
			<td class="Heading1">{% lang 'ViewDiscounts' %}</td>
		</tr>
		<tr>
		<td class="Intro">
			<p>{{ DiscountIntro|safe }}</p>
			<div id="DiscountStatus">
				{{ Message|safe }}
			</div>
			<table id="IntroTable" cellspacing="0" cellpadding="0" width="100%">
			<tr>
			<td class="Intro" valign="top">
				<input type="button" name="IndexAddButton" value="{% lang 'CreateDiscount' %}..." id="IndexCreateButton" class="SmallButton" onclick="document.location.href='index.php?ToDo=createDiscount'" /> &nbsp;<input type="button" name="IndexDeleteButton" value="{% lang 'DeleteSelected' %}" id="IndexDeleteButton" class="SmallButton" onclick="ConfirmDeleteSelected()" {{ DisableDelete|safe }} />
			</td>
			</tr>
			</table>
		</td>
		</tr>
		<tr>
		<td style="display: {{ DisplayGrid|safe }}">
			<form name="frmDiscounts" id="frmDiscounts" method="post" action="index.php?ToDo=deleteDiscounts">
				<div class="GridContainer">
					{{ DiscountsDataGrid|safe }}
				</div>
				<div id="SeeMoreDiscountBox" style="display: {{ HideSeeMoreDiscountBox|safe }}">
					<a href="#" onclick="seeMoreDiscountItems(); return false;">{{ DiscountShowNextBatchItems|safe }}</a>
				</div>
			</form>
		</td></tr>
	</table>
	</div>

	<script type="text/javascript">

		function ConfirmDeleteSelected()
		{
			var fp = document.getElementById("frmDiscounts").elements;
			var c = 0;

			for(i = 0; i < fp.length; i++)
			{
				if(fp[i].type == "checkbox" && fp[i].checked)
					c++;
			}

			if(c > 0)
			{
				if(confirm("{% lang 'ConfirmDeleteDiscounts' %}"))
					document.getElementById("frmDiscounts").submit();
			}
			else
			{
				alert("{% lang 'ChooseDiscounts' %}");
			}
		}

		function ToggleDeleteBoxes(Status)
		{
			var fp = document.getElementById("frmDiscounts").elements;

			for(i = 0; i < fp.length; i++)
				fp[i].checked = Status;
		}

		function DiscountClipboard(Data)
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

		var updatingSortables = false;
		var updateTimeout = null;

		function CreateSortableList() {

			$('#DiscountList').sortable({
				accept: 'SortableRow',
				containment: '#DiscountList',
				handler: '.sort-handle',
				opacity: .8,
				placeholder: 'SortableRowHelper',
				forcePlaceholderSize: true,
				items: 'li',
				tolerance: 'pointer',
				update: function(event, ui) {
					$(this).find('.GridRow').removeClass('RowDown');

					var idx = [];

					$('input.DiscountsIdx').each(
						function()
						{
							idx[idx.length] = $(this).val();
						}
					);

					$.ajax({
						url: 'remote.php',
						type: 'post',
						dataType: 'xml',
						data: {
								'remoteSection': 'discounts',
								'w': 'updateDiscountOrder',
								'sortorder': idx.join(',')
							},
						success: function(response) {

							var status = $('status', response).text();
							var message = $('message', response).text();
							if(status == 0) {
								display_error('DiscountStatus', message);
							}
							else {
								display_success('DiscountStatus', message);
							}
						}
					});
				}
			});
		}

		function seeMoreDiscountItems()
		{
			var lastSortOrder = 1;

			$(".DiscountSortOrder").each(
				function()
				{
					if ($(this).val() > lastSortOrder) {
						lastSortOrder = parseInt($(this).val());
					}
				}
			);

			$.ajax({
				url: 'remote.php',
				type: 'post',
				data: {
						'remoteSection': 'discounts',
						'w': 'getMoreDiscounts',
						'lastSortOrder': lastSortOrder
					},
				success: seeMoreDiscountItemsCallback
			});
		}

		function seeMoreDiscountItemsCallback(data)
		{
			if ($("items", data).text() !== '') {
				$("#DiscountList").append($("items", data).text());
				CreateSortableList();
			}

			if ($("more", data).text() == "0") {
				$("#SeeMoreDiscountBox").hide();
			}
		}

		$(document).ready(function()
		{
			CreateSortableList();
		});

	</script>