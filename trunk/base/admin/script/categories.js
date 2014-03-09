var CategoryForm = {
	confirmCancel : function()
	{
		if(confirm(lang.CancelMessage)) {
			document.location.href='index.php?ToDo=viewCategories';
		}
		else
		{
			return false;
		}
	},

	checkForm : function()
	{
		var catname = document.getElementById("catname");
		var cp = document.getElementById("catparentid");
		var cs = document.getElementById("catsort");
		var ci = document.getElementById("catimagefile");

		if(catname.value == "") {
			alert(lang.NoCategoryName);
			catname.focus();
			catname.select();
			CategoryForm.showTab('details');
			return false;
		}

		if(cp.selectedIndex == -1) {
			alert(lang.NoParentCategory);
			cp.focus();
			return false;
		}

		if(isNaN(cs.value) || cs.value == "") {
			alert(lang.NoCatSortOrder);
			cs.focus();
			cs.select();
			return false;
		}

		if(ci.value != "") {
			// Make sure it has a valid extension
			img = ci.value.split(".");
			ext = img[img.length-1].toLowerCase();

			if(ext != "jpg" && ext != "png" && ext != "gif") {
				alert(lang.ChooseValidImage);
				ci.focus();
				ci.select();
				return false;
			}
		}

		//validate google optimzer form
		if ($('#catenableoptimizer').attr('checked')) {
			if(!Optimizer.ValidateConfigForm(CategoryForm.showTab, 'optimizer')) {
				return false;
			}
		}

		// Everything is OK, return true
		return true;
	},

	handleRootCategory: function()
	{
		if ($('#catparentid').val() == 0) {
			$('#CategoryImageRow').hide();
			document.getElementById('catimagefile').disabled = true;
		} else {
			document.getElementById('catimagefile').disabled = false;
			$('#CategoryImageRow').show();
		}
	},

	expandCategoryList: function()
	{
		if ($("#catparentid").attr('size') == 10) {
			$("#catparentid").attr('size', 30);
			$("#expandCategoryList").html(lang.CollapseCategory);
		}
		else {
			$("#catparentid").attr('size', 10);
			$("#expandCategoryList").html(lang.ExpandCategory);
		}
	},

	toggleOptimizerConfigForm: function() {
		if($('#catenableoptimizer').attr('checked')) {

			var showForm = true;
			if(!CategoryForm.skipOptimizerConfirmMsg) {
				showForm = confirm(lang.ConfirmEnableCategoryOptimizer);
			}

			if(showForm) {
				$('#OptimizerConfigForm').show();
			} else {
				$('#catenableoptimizer').attr('checked', false)
			}
		} else {
			$('#OptimizerConfigForm').hide();
		}
	},

	showTab: function(T)
	{
		if(typeof T != "string")
			var T = $(this).attr('id').replace(/^tab_/,'');

		if(T=='' || $('#div_'+T).length <= 0 || $('#tab_'+T).length <= 0) {
			return false;
		}

		var activeTab = $('#tabnav .active');
		var tabName = activeTab.attr('id').replace('tab_', '');
		activeTab.removeClass('active');
		$('#div_'+tabName).hide();
		$('#div_'+T).show();
		$('#tab_'+T).addClass('active');
		$('#currentTab').val(T);
	},

	selectComparisonCategory: function()
	{
		var container = $(this).parent();
		var hiddenCategoryField = $('.comparisoncategory', container);
		var hiddenCategoryPathField = $('.comparisoncategory_path', container);
		var readonlyCategoryField = $('.comparisoncategory_readonly', container);
		var moduleid = hiddenCategoryField.attr('id');

		$.iModal({
			type: 'inline',
			inline: '#categorySelectModal',
			width: 905,
			height: 900
		});

		var name = CategoryForm.shoppingComparisonModules[moduleid].name;

		$('#ModalContainer').categorySelector({
			getCategoriesURL: 'index.php?ToDo=getShoppingComparisonCategories&mid='+moduleid,
			categoryid: hiddenCategoryField.val(),
			title: lang.CategorySelectModalTitle.replace(':name', name),
			intro: lang.CategorySelectModalIntro.replace(':name', name),
			messages: {
				'leafCategorySelected' : lang.CategorySelectLeafCategorySelected,
				'chooseLeafCategory' : lang.CategorySelectChooseLeafCategory
			},
			success: function(cat){
				$.iModal.close();
				hiddenCategoryField.val(cat['id']);
				hiddenCategoryPathField.val(cat['path']);
				readonlyCategoryField.val(cat['path']);
			},
			cancel: function(){
				$.iModal.close();
			}
		});

		return false;
	},

	clearComparisonCategory: function()
	{
		var container = $(this).parent();
		$('.comparisoncategory', container).val('');
		$('.comparisoncategory_readonly', container).val('');
		$('.comparisoncategory_path', container).val('');

		return false;
	},

	initCategoryImageHeader: function()
	{
		var categoryMappingsHeading = $('#ShoppingComparisonCategoryMappingsHeading');
		categoryMappingsHeading.mouseover(
			function(){
				ShowQuickHelp(this,	'',
					lang.ShoppingComparisonCategoryMappingsDesc);
			});

		categoryMappingsHeading.mouseout(
			function(){
				HideQuickHelp(this);
			});

		CategoryForm.handleRootCategory();
	},

	init: function()
	{
		$('.CategoryCancelButton').click(CategoryForm.confirmCancel);
		$('.CategoryFormTab').click(CategoryForm.showTab);
		$('#catparentid').change(CategoryForm.handleRootCategory);
		$('#expandCategoryList').click(function(){CategoryForm.expandCategoryList();return false;});
		$('#catenableoptimizer').click(CategoryForm.toggleOptimizerConfigForm);

		CategoryForm.initCategoryImageHeader();

		$('.categoryselect').click(CategoryForm.selectComparisonCategory);
		$('.categoryclear').click(CategoryForm.clearComparisonCategory);

		$('#frmAddCategory').submit(
			function(){
				return ValidateForm(CategoryForm.checkForm);
			}
		);

		CategoryForm.showTab(CategoryForm.currentTab);
	}
};

$(document).ready(function() {
	CategoryForm.init();
});