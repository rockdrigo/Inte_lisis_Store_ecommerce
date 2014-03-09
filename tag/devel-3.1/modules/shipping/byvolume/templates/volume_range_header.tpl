
<script type="text/javascript" charset="utf-8">

	function AddVolumeRange(node)
	{
		var newNode = $(node).clone();
		var newVal = $(newNode).find('input:eq(1)').val();

		$(newNode).find('input:first').val(newVal);
		$(newNode).find('input:gt(0)').val('');

		var oldName = $('.VolumeRanges:last input:first').attr('name');
		var oldParts = oldName.replace(/^.*_/, '');
		var oldParts = oldParts.replace(/\]/, '');
		var newNum = parseInt(oldParts)+1;

		$(newNode).find('input').each(function() {
			parts = $(this).attr('name').split(/[_|\[|\]]/);
			$(this).attr('name', parts[0] + '_' + parts[1] + '[' + parts[2] + '_' + newNum + ']');
		});

		$(node.parentNode).append(newNode);

		ShowCorrectLinks();
	}

	function RemoveVolumeRange(node)
	{
		if (ConfirmRemove(node)) {
			$(node).remove();
			ShowCorrectLinks();
		}
	}
	function ShowCorrectLinks()
	{
		$('.VolumeRanges:first a.remove').hide();
		$('.VolumeRanges:gt(0) a.remove').show();
	}

	function ConfirmRemove(node)
	{
		var canRemove = true;
		$(node).find('input').each(function() {
			if ($(this).val() != '') {
				if (confirm("{% lang 'ConfirmRemoveVolumeRange' %}")) {
					canRemove = true;
				} else {
					canRemove = false;
				}
				return false;
			}
		});

		return canRemove;
	}

	$(document).ready(function () {
		ShowCorrectLinks();
	});

</script>

