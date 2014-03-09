/**
 * All functions have been moved to product.functions.js
 * This is because this file was used in the control panel as well as the front end, but the
 * below initialization code is only meant for the frontend.
 */
$(document).ready(function() {
	initiateImageCarousel();
	initiateImageZoomer();
	//variationDisableExtraFields();
	$('.LayerField').hide();
	$('.SelectBoxLayer').value="";

	if(typeof(HideProductTabs) != 'undefined' && HideProductTabs == 0) {
		GenerateProductTabs();
		if (CurrentProdTab && CurrentProdTab != "") {
			ActiveProductTab(CurrentProdTab);
			document.location.href = '#ProductTabs';
		}
	} else {
		$('.ProductSectionSeparator').show();
	}

	// are there any videos in the middle column?
	if($('.videoRow').size() > 0) {
		$('.videoRow').bind('click', function () {
			var videoId = $(this).attr('id').replace('video_', '');
			$('#FeaturedVideo').html('<object width="320" height="265">'
				+ '<param name="movie" value="http://www.youtube.com/v/' + videoId + '?fs=1"></param>'
				+ '<param name="allowFullScreen" value="true"></param>'
				+ '<param name="allowscriptaccess" value="always"></param>'
				+ '<embed src="http://www.youtube.com/v/'  + videoId + '?&fs=1&autoplay=1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="320" height="265"></embed>'
				+ '</object>'
			);
			selectCurrentVideo(videoId);
		});
	}

	// are there any videos in the left or right columns?
	if($('.sideVideoRow').size() > 0) {
		$('.sideVideoRow a').bind('click', function () {
			// grab the video id out of the tag id
			var videoId = $(this).attr('id').replace('sidevideo_', '');

			if(config.ProductImageMode == 'lightbox') {
				// we need to hide any objects on the page as they appear onto of our modal window
				$('#VideoContainer object').css('visibility', 'hidden');

				$.iModal({
					data: '<object width="480" height="385">'
						+ '<param name="movie" value="http://www.youtube.com/v/' + videoId + '?fs=1"></param>'
						+ '<param name="allowFullScreen" value="true"></param>'
						+ '<param name="allowscriptaccess" value="always"></param>'
						+ '<embed src="http://www.youtube.com/v/'  + videoId + '?&fs=1&autoplay=1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="480" height="385"></embed>'
						+ '</object>',
					title: $(this).find('img').attr('title'),
					width: 510,
					buttons: '<input type="button" onclick="$.iModal.close();" value="  ' + lang.Close +'  " />',
					onBeforeClose: function() {
						// reshow any objects that were hidden
						$('#VideoContainer object').css('visibility', 'visible');
					}
				});
			} else {
				showVideoPopup(videoId);
			}
			return false;
		});
	}

	var VariationSelects = $(".VariationSelect");
	
	var i; var j; var activate = false;
	for(i=0;i<VariationSelects.length;i++)
	{
		for(j=0;j<VariationSelects[i].length;j++)
		{
			if (VariationSelects[i].options[j].innerHTML == "N/A") activate = true;
		}
	}
	
	// disable all but the first variation box
	//NES: Only of in all the variations we didn't find any "N/A" options
	if(activate == false) $(".VariationSelect:gt(0)").attr('disabled', 'disabled');

	var prodVarSelectionMap = {}
	$(".VariationSelect").change(function() {
		// cache a map of currently selected values.
		var mapIndex = 0;
		$('.VariationSelect').each(function() {
			prodVarSelectionMap[mapIndex] = this.value;
			mapIndex++;
		});

		// get the index of this select
		var index = $('.VariationSelect').index($(this));

		if(activate == false)
		{
			// deselected an option, disable all select's greater than this
			if (this.selectedIndex == 0) {
				$('.VariationSelect:gt(' + index + ')').attr('disabled', 'disabled')
				updateSelectedVariation($('body'));
				return;
			}
			else {
				// disable selects greater than the next
				$('.VariationSelect:gt(' + (index + 1) + ')').attr('disabled', 'disabled')
			}
		}

		//serialize the options of the variation selects
		var optionIds = '';
		$('.VariationSelect:lt(' + (index + 1) + ')').each(function() {
			if (optionIds != '') {
				optionIds += ',';
			}

			optionIds += $(this).val();
		});
		
		var LayerSelects = $('select.SelectBoxLayer').filter(':visible');
		
		var i; var selections = Array();
		for(i=0;i<LayerSelects.length;i++) {
			(LayerSelects[i].value != "") ? selections.push(LayerSelects[i].value) : selections;
		}
		
		var LayerModifiers = $('.LayerModifier').filter(':visible');
		
		var modifiers = Array();
		for(i=0;i<LayerModifiers.length;i++) {
			(LayerModifiers[i].checked) ? modifiers.push(LayerModifiers[i].value.substring(1) ) : modifiers;
		}
		
		// request values for this option
		$.getJSON(
			config.AppPath + '/remote.php?w=GetVariationOptions&productId=' + productId + '&options=' + optionIds + '&selections=' + selections.toString()+ '&modifiers=' + modifiers.toString(),
			function(data) {
				// were options returned?
				if (data.hasOptions) {
					// load options into the next select, disable and focus it
					$('.VariationSelect:eq(' + (index + 1) + ') option:gt(0)').remove();
					$('.VariationSelect:eq(' + (index + 1) + ')').append(data.options).attr('disabled', '').focus();

					// auto select previously selected option, and cascade down, if possible
					var preVal = prodVarSelectionMap[(index + 1)];
					if (preVal != '') {
						var preOption = $('.VariationSelect:eq(' + (index + 1) + ') option[value=' +preVal+']');
						if (preOption) {
							preOption.attr('selected', true);
							$('.VariationSelect:eq(' + (index + 1) + ')').trigger('change');
						}
					}
				}
				else if (data.comboFound) { // was a combination found instead?
					/*
					if($(".SelectBoxLayer").filter(':visible').length > 0){
						data.thumb = '';
					}
					*/
					// display price, image etc
					updateSelectedVariation($('body'), data, data.combinationid);
					ChangeLayerImage($('body'), data, data.combinationid);
				}
			}
		);
	});

	//radio button variations
	$('.ProductOptionList input[type=radio]').click(function() {
		//current selected option id
		var optionId = $(this).val();
		
		var LayerSelects = $('select.SelectBoxLayer').filter(':visible');

		var i; var selections = Array();
		for(i=0;i<LayerSelects.length;i++) {
			(LayerSelects[i].value != "") ? selections.push(LayerSelects[i].value) : selections;
		}
		
		var LayerModifiers = $('.LayerModifier').filter(':visible');
		
		var modifiers = Array();
		for(i=0;i<LayerModifiers.length;i++) {
			(LayerModifiers[i].checked) ? modifiers.push(LayerModifiers[i].value.substring(1) ) : modifiers;
		}
		
		// request values for this option
		$.getJSON(
			config.AppPath + '/remote.php?w=GetVariationOptions&productId=' + productId + '&options=' + optionId + '&selections=' + selections.toString()+ '&modifiers=' + modifiers.toString(),
			function(data) {
				if (data.comboFound) { // was a combination found instead?
					// display price, image etc
					updateSelectedVariation($('body'), data, data.combinationid);
					ChangeLayerImage($('body'), data, data.combinationid);
				}
			}
		);
	});
	
	$(".ActivatesLayersClass input[type=radio]").change(function() {
		var numExtras = $(this).parent().html();
		numExtras = numExtras.substring(numExtras.lastIndexOf('>')+2);
		variationActivateExtraFields(numExtras);
	});
	
	$("Select.ActivatesLayersClass").change(function() {
		var index = this.selectedIndex;
		var numExtras = this.options[index].text;
		
		variationActivateExtraFields(numExtras);
	});	
	
	$(".SelectBox").change(function() {
		var thisNumSelect = this.name.substring(this.name.lastIndexOf('[')+1, this.name.lastIndexOf(']'));
		
		if(this.value == "") {
			$('.LayerFieldModificerList'+thisNumSelect).hide();
		}
		else {
			$('.LayerFieldModificerList'+thisNumSelect).show();
		}
	});
	
	$(".LayerModifier").change(function() {
		$('.ImageCarouselBox').hide();
		var optionId = $('.ProductOptionList input[type=radio]').val();

		if(!optionId) {
			var optionId = '';
			var index = $('.VariationSelect').length;//index($(this));
			$('.VariationSelect:lt(' + (index + 1) + ')').each(function() {
				if (optionId != '') {
					optionId += ',';
				}
	
				optionId += $(this).val();
			});
		}
	
		var LayerSelects = $('select.SelectBoxLayer').filter(':visible');

		var i; var selections = Array();
		for(i=0;i<LayerSelects.length;i++) {
			(LayerSelects[i].value != "") ? selections.push(LayerSelects[i].value) : selections;
		}
		
		var LayerModifiers = $('.LayerModifier').filter(':visible');
		
		var modifiers = Array();
		for(i=0;i<LayerModifiers.length;i++) {
			(LayerModifiers[i].checked) ? modifiers.push(LayerModifiers[i].value.substring(1) ) : modifiers;
		}

		// request values for this option
		$.getJSON(
			config.AppPath + '/remote.php?w=GetVariationOptions&productId=' + productId + '&options=' + optionId + '&selections=' + selections.toString()+ '&modifiers=' + modifiers.toString(),
			function(data) {
				if (data.comboFound) { // was a combination found instead?
					// display price, image etc
					updateSelectedVariation($('body'), data, data.combinationid);
					ChangeLayerImage($('body'), data, data.combinationid);
				}
			}
		);
	});	
	
});