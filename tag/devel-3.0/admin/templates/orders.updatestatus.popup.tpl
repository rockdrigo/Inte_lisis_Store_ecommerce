<table id="OuterPanel" cellSpacing="0" cellPadding="0" width="100%">
	<tr>
		<td class="Heading1">
			{% lang 'OrderUpdateStatusInProgress' %}
		</td>
	</tr>
	<tr>
		<td class="Intro">
			<div class="IntroItem">
				<div>
					<div style="position: relative;border: 1px solid #ccc; width: 300px; padding: 0px; margin: 0 auto;">
						<div id="ProgressBarBar" style="margin: 0; padding: 0; background: url(images/progressbar.gif) no-repeat; height: 20px; width: 0%;">
							&nbsp;
						</div>
						<div style="position: absolute; top: 2px; left: 0; text-align: center; width: 300px; font-weight: bold;" id="ProgressBarPercentage">&nbsp;</div>
					</div>
					<div id="ProgressBarStatus" style="text-align: center;">&nbsp;</div>
				</div>
			</div>
		</td>
	</tr>
</table>
<script type="text/javascript">

	var orderidx = [{{ JavaScriptOrderIds|safe }}];
	var currentOrder = 0;
	var success = 0;
	var failed = 0;

	function requestOrderUpdate()
	{
		$.ajax({
			type	: "POST",
			url	: "remote.php",
			data	: "w=updateMultiOrderStatusRequest&remoteSection=orders&orderId=" + orderidx[currentOrder] + "&statusId={{ StatusID|safe }}&success=" + success + "&failed=" + failed,
			success	: handleOrderUpdate
		});

		currentOrder += 1;
		return;
	}

	function handleOrderUpdate(data)
	{
		var status = data.substring(0,1);
		var report = data.substring(1);

		if (status == '1') {
			success++;
		} else {
			failed++;
		}

		var percentage = new String(Math.round((currentOrder/orderidx.length) * 100));

		$("#ProgressBarStatus").text("{% lang 'OrderUpdateStatusUpdating' %} " + orderidx.length + " {% lang 'Orders' %}");
		$("#ProgressBarPercentage").text(percentage + "%");
		$("#ProgressBarBar").css("width", percentage + "%");

		if (currentOrder < orderidx.length) {
			setTimeout("requestOrderUpdate()", 100);
		} else {
			var page = window.parent.document.getElementById('CurrentPage').value;
			var url = window.parent.location.href + '&page=' + page + '&ajax=1';

			$.ajax({
				type	: "GET",
				url	: url,
				success	: handleOrderFinish
			});

			window.parent.document.getElementById('OrdersStatus').innerHTML = report;
		}
	}

	function handleOrderFinish(data)
	{
		window.parent.document.getElementById('GridContainer').innerHTML = data;
		window.parent.BindAjaxGridSorting();
		window.parent.tb_remove();
	}

	$(document).ready(function() {
		requestOrderUpdate();
	});

</script>