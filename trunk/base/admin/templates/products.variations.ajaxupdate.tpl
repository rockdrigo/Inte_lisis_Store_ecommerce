<div class="ModalTitle">
	{% lang 'UpdateVariationsTitle' with ['totalProducts': totalProducts] %}
</div>
<div class="ModalContent" style="padding:5px;">
	<div style="padding-bottom: 5px;">
		{{ lang.UpdateVariationsIntro }}
	</div>
	<div id="cachingStatus">
		<div style="width: 208px; padding: 0px; margin: 10px auto 10px auto; position: relative; background: url('images/loadingAnimation.gif') no-repeat;">
			<div id="ProgressBarPercentage" style="margin: 0; padding: 0; height: 13px; width: 0%; background: url('images/progressbar.gif') no-repeat; background-color: transparent;">
				&nbsp;
			</div>
			<div style="position: absolute; top: 0; text-align: center; width: 208px; font-weight: bold;" id="ProgressPercent">&nbsp;</div>
		</div>
	</div>
</div>

<script type="text/javascript">//<![CDATA[
	var totalProducts = {{ totalProducts }};
	var currentProgress = 0;

	function UpdateProgress(percentage) {
		$('#ProgressBarPercentage').css('width', percentage + "%");
		$('#ProgressPercent').html(Math.round(percentage) + "%");
	}
	$(document).ready(function() {
		UpdateProgress(0);

		updateNextProduct();
	});

	function updateNextProduct() {
		$.ajax({
			url: 'remote.php',
			type: 'post',
			dataType: 'json',
			data: {
				remoteSection: 'product_variations',
				w: 'continueRebuildVariations',
				session: '{{ sessionId }}',
			},
			success: function(response) {
				if (response.success) {
					if (response.done) {
						$.iModal.close();
						return;
					}

					currentProgress++;

					var currentPercent = currentProgress / totalProducts * 100;
					UpdateProgress(currentPercent);

					updateNextProduct();
				}
				else {
					alert(response.message);
					window.parent.location = 'index.php?ToDo=viewProductVariations';
				}
			},
			error: function() {
				alert('error in request');
			}
		});
	}
//]]></script>
