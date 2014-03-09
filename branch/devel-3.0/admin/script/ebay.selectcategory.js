;(function($){

// create and use `fsm` locally
var fsm = new Interspire_FSM();

// the init state immediately starts loading modal content via ajax and waits for it to be loaded, after which it automatically transitions to the select_list state
fsm.state('Init')
	.initial()
	.enter(function(ev, state){
		$.iModal({
			type: 'ajax',
			method: 'post',
			width: 905,
			url: 'remote.php',
			urlData: {
				remoteSection: 'ebay',
				w: 'getSelectCategoryDialog'
			},
			onError: function () {
				state.machine.finish();
				alert('Failed to load dialog');
			},
			onShow: function () {
				state.machine.transition('ModalShow');
			},
			onClose: function () {
				// attempt to clean up the state machine if the user closes the dialog
				state.machine.finish();
			}
		});
	})
	.transition('ModalShow', 'LoadMainCategories')
		.execute(function(){
			$('.CategoryMachine_BackButton').click(function(){
				fsm.transition('ClickBack');
			});

			$('.CategoryMachine_NextButton').click(function(){
				fsm.transition('ClickNext');
			});

			$('.CategoryMachine_CancelButton').click(function(){
				fsm.transition('ClickCancel');
			});

			$('.CategoryMachine_FinishButton').click(function(){
				fsm.transition('ClickFinish');
			});
		});

/**
* This state initially preloads the top level ebay categories
*/
fsm.state('LoadMainCategories')
	.enter(function(ev, state) {
		fsm.getCategoryList(-1, false);
	})
	.transition('ClickCancel', 'End')
	.from.transition('MainCategoriesLoaded', 'SelectCategory')
	.from.transition('MainCategoriesLoadFailed', 'LoadMainCategoriesFailed');

/**
* Displays a message that (top level) categories could not be loaded
*/
fsm.state('LoadMainCategoriesFailed')
	.transition('ClickCancel', 'End');

/**
* This state allows selection of an actual category
*/
fsm.state('SelectCategory')
	.transition('ClickCancel', 'End')
	.from.transition('ClickNext', 'LoadConditions') // for a primary ebay category we want to load the category features and item conditions
		.test(function(){
			if (fsm.payload.selectedCategoryId &&
				fsm.payload.isPrimaryCategory &&
				fsm.payload.categoryType == 'ebay') {
				return true;
			}
			else {
				return false;
			}
		})
	.from.transition('ClickFinish', 'LoadCategoryFeatures') // for secondary categories or store categories proceed to loading the category features then finish
		.test(function(){
			if (fsm.payload.selectedCategoryId &&
				(!fsm.payload.isPrimaryCategory || fsm.payload.categoryType == 'store')) {
				return true;
			}
			else {
				return false;
			}
		});

/**
* For a primary ebay category only: This state loads the category features and category conditions.
* If the category has conditions, it automatically proceeds to the map conditions step,
* otherwise it shows a message that no conditions are available.
*/
fsm.state('LoadConditions')
	.enter(function(ev, state) {
		if (fsm.payload.conditionsLoaded) {
			fsm.transition('ConditionsLoaded');
			return;
		}

		$.ajax({
			url: 'remote.php',
			type: 'post',
			dataType: 'json',
			data: {
				remoteSection: 'ebay',
				w: 'getCategoryFeatures',
				categoryId: fsm.payload.selectedCategoryId,
				primaryCategory: true,
				siteId: fsm.payload.siteId
			},
			success: function(data) {
				if (data && data.success) {
					fsm.payload.categoryFeatures = data.categoryFeatures;

					fsm.payload.categoryFeaturesList = data.categoryFeaturesList;

					if (!data.categoryFeatures.has_conditions) {
						fsm.transition('CategoryHasNoConditions');
						return;
					}

					if (data.categoryFeatures.conditions_required) {
						$("#conditionsIntro").html(lang.ProductCondMapMandatory);
					}
					else {
						$("#conditionsIntro").html(lang.ProductCondMapOptional);
					}

					$("#categoryConditions").html(data.conditionsHTML);

					fsm.payload.conditionsLoaded = true;

					// load conditions to select boxes
					fsm.transition('ConditionsLoaded');
				}
				else {
					fsm.transition('ConditionsLoadFailed');
				}
			},
			error: function() {
				fsm.transition('ConditionsLoadFailed');
			}
		});
	})
	.transition('ConditionsLoaded', 'MapConditions')
	.from.transition('ConditionsLoadFailed', 'LoadConditionsFailed')
	.from.transition('CategoryHasNoConditions', 'AddCategory');

/**
* Displays a message that the conditions could not be loaded
*/
fsm.state('LoadConditionsFailed')
	.transition('ClickCancel', 'End')
	.from.transition('ClickBack', 'SelectCategory');

/**
* For secondary or store categories this state loads the category features then proceeds to add the category to the template
*/
fsm.state('LoadCategoryFeatures')
	.enter(function(ev, state) {
		// for store categories we don't need to load features, so we're done.
		if (fsm.payload.categoryType == 'store') {
			fsm.payload.categoryFeatures = {
					name: fsm.payload.selectedCategoryName,
					path: fsm.payload.selectedCategoryName,
					category_id: fsm.payload.selectedCategoryId
			};
			fsm.transition('CategoryFeaturesLoaded');
			return;
		}

		// load category features from eBay then close
		$.ajax({
			url: 'remote.php',
			type: 'post',
			dataType: 'json',
			data: {
				remoteSection: 'ebay',
				w: 'getCategoryFeatures',
				categoryId: fsm.payload.selectedCategoryId,
				siteId: fsm.payload.siteId
			},
			success: function(data) {
				if (data && data.success) {
					fsm.payload.categoryFeatures = data.categoryFeatures;

					// load conditions to select boxes
					fsm.transition('CategoryFeaturesLoaded');
				}
				else {
					fsm.transition('FeaturesLoadFailed');
				}
			},
			error: function() {
				fsm.transition('FeaturesLoadFailed');
			}
		});
	})
	.transition('CategoryFeaturesLoaded', 'AddCategory')
	.from.transition('FeaturesLoadFailed', 'LoadFeaturesFailed');

/**
* Displays a message that the category details could not be loaded
*/
fsm.state('LoadFeaturesFailed')
	.transition('ClickCancel', 'End')
	.from.transition('ClickBack', 'SelectCategory');


fsm.state('MapConditions')
	.transition('ClickCancel', 'End')
	.from.transition('ClickBack', 'SelectCategory')
	.from.transition('ClickFinish', 'AddCategory')
		.test(function(){
			// ensure conditions are mapped
			if (fsm.payload.categoryFeatures.conditions_required) {
				if ($("#newCondition").val() == '0' ||
					$("#usedCondition").val() == '0' ||
					$("#refurbishedCondition").val() == '0') {
					$("#mapConditionsMessage").html('');
					return false;
				}
				else {
					// message is flashed multiple times without this check due to the test running over each of the selects
					if ($("#mapConditionsMessage").html() == '') {
						display_success('mapConditionsMessage', lang.MappedFinished);
					}
				}
			}

			return true;
		})
		.poll('.CategoryMachine_State_MapConditions select', 'change');

/**
* Displays a message that the category has no conditions available and then allows the user to finish adding the category
*/
fsm.state('NoConditions')
	.transition('ClickCancel', 'End')
	.from.transition('ClickBack', 'SelectCategory')
	.from.transition('ClickFinish', 'AddCategory');

/**
* This state adds the select category to the template then closes the dialog
*/
fsm.state('AddCategory')
	.enter(function(ev, state) {
		var selectedCategoryOptions = fsm.payload.categoryFeatures;

		// store the selected item conditions
		if (fsm.payload.categoryFeatures.has_conditions) {
			selectedCategoryOptions.newCondition = $("#newCondition").val();
			selectedCategoryOptions.usedCondition = $("#usedCondition").val();
			selectedCategoryOptions.refurbishedCondition = $("#refurbishedCondition").val();
		}

		// add the selected category to the template
		EbayTemplate.AddCategory(fsm.payload.categoryType, fsm.payload.isPrimaryCategory, selectedCategoryOptions);

		if (fsm.payload.categoryFeaturesList) {
			EbayTemplate.UpdateCategoryFeaturesList(fsm.payload.categoryFeaturesList);
		}

		if (EbayTemplate.primaryCategoryOptions.category_id && EbayTemplate.secondaryCategoryOptions.category_id) {
			var imgPath = "images/tick.gif"
			var variationsSupportedDesc = lang.EbayProductsWithVariationsAllowed;
			if(EbayTemplate.primaryCategoryOptions.variations_supported == false
			|| EbayTemplate.secondaryCategoryOptions.variations_supported == false) {
				imgPath = "images/cross.gif"
				variationsSupportedDesc = lang.EbayProductsWithVariationsNotAllowed;
			}
			$("#variationsSupported > img").attr("src", imgPath);
			$("#variationsSupported > span").html(variationsSupportedDesc);
		}

		fsm.transition('AddCategoryDone');
	})
	.transition('AddCategoryDone', 'End');

fsm.state('End')
	.enter(function(ev, state){
		state.machine.finish();
	});

$(fsm).bind('machine_finish', function(event, fsm){
	// the machine has finished, so make sure the modal is closed
	$.iModal.close();
});

$(fsm).bind('state_enter', function(event, state){
	// on entering any state, try showing a dialog element that relates to that state
	//console.debug('entering state: ' + state.name);
	$('.CategoryMachine_State_' + state.name).show();
});

$(fsm).bind('state_exit', function(event, state){
	// on exiting any state, try showing a dialog element that relates to that state
	//console.debug('exiting state: ' + state.name);
	$('.CategoryMachine_State_' + state.name).hide();
});

$(fsm).bind('transitions_change', function(event, fsm){
	// the transition available for the machine's current state have changed, check to see how buttons should be configured

	$('.CategoryMachine_NextButton').enabled(fsm.can('ClickNext'));
	$('.CategoryMachine_FinishButton').enabled(fsm.can('ClickFinish'));
	$('.CategoryMachine_BackButton').enabled(fsm.can('ClickBack'));
	$('.CategoryMachine_CancelButton').enabled(fsm.can('ClickCancel'));

	if (fsm.can('ClickFinish')) {
		$('.CategoryMachine_NextButton').hide();
		$('.CategoryMachine_FinishButton').show();
	}
	else {
		$('.CategoryMachine_NextButton').show();
		$('.CategoryMachine_FinishButton').hide();
	}
});

/**
* This function loads a list of categories into the dialog given a specific category Id
*/
fsm.getCategoryList = function(categoryId, parentBoxId) {
	// this forces the conditions to be reloaded if a different category was selected
	fsm.payload.conditionsLoaded = false;

	$('#selectCategoryMessage').html('');

	// determine what level we're at
	var currentLevel = 0;
	if (parentBoxId) {
		var parent = $('#' + parentBoxId).parents('td');
		currentLevel = parent.prevAll('td').length + 1;

		// remove any decendant category boxes if we've selected a different parent category
		parent.nextAll('td').remove();
	}

	if ($("#category_" + categoryId).hasClass('CategoryLeaf')) {
		if (fsm.payload.isPrimaryCategory) {
			display_success('selectCategoryMessage', lang.EbayCategorySelected + ' ' + lang.NextMapCond);
		}
		else {
			display_success('selectCategoryMessage', lang.EbayCategorySelected + ' ' + lang.FinishAddCat);
		}
		fsm.payload.selectedCategoryId = categoryId;
		fsm.payload.selectedCategoryName = $("#category_" + categoryId).parents('label').text();
		fsm.refresh();
		return;
	}
	else {
		$("#selectCategoryMessage").html('');
		fsm.payload.selectedCategoryId = null;
		fsm.payload.selectedCategoryName = null;
		fsm.refresh();
	}

	// disable the existing category boxes so the user can't click and load multiple child categories while we're still processing one request
	$('.EbayCategoryBox input').attr('disabled', 'disabled');

	$.ajax({
		url: 'remote.php',
		type: 'post',
		dataType: 'json',
		data: {
			remoteSection: 'ebay',
			w: 'getCategoryList',
			categoryId: categoryId,
			siteId: fsm.payload.siteId,
			currentLevel: currentLevel,
			categoryType: fsm.payload.categoryType
		},
		success: function(data) {
			if (data && data.success) {
				$("#categoriesRow").append(data.html);

				// scroll the container to the right
				$("#categoriesContainer").scrollLeft($("#categoriesContainer")[0].scrollWidth);

				// are we initially loading the main categories?
				if (categoryId == -1) {
					// transition to the select category step
					fsm.transition('MainCategoriesLoaded');
				}
			}
			else {
				if (categoryId == -1) {
					fsm.transition('MainCategoriesLoadFailed');
				}
				else {
					display_error('selectCategoryMessage', lang.LoadingEbayCategoriesFailure + ' ' + lang.TryAgainMessage);
				}
			}
		},
		complete: function(request, status) {
			// re-enable the categories
			$('.EbayCategoryBox input').removeAttr('disabled');
		}
	});
};

// assign to global scope with proper name
Interspire_Ebay_SelectCategoryMachine = fsm;

$(document).ready(function() {
	$(".EbayCategoryBox input").live('change', function() {
		$(this).parents('ul').find('li').removeClass('SelectedRow')
		$(this).parents('li').addClass('SelectedRow');

		var categoryId = $(this).val();
		var parentElementId = $(this).parents('.EbayCategoryBox').attr('id');

		Interspire_Ebay_SelectCategoryMachine.getCategoryList(categoryId, parentElementId);
	});

	$(".EbayCategoryBox li").live('mouseenter', function() {
		$(this).addClass('ISSelectOptionHover');
	});

	$(".EbayCategoryBox li").live('mouseleave', function() {
		$(this).removeClass('ISSelectOptionHover');
	});
});

})(jQuery);