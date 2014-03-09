var StoreLinker = {
	path: '',
	mode: 'tinymce',
	selectedItem: "",
	timeout: '',
	currentItemId: 0,

	init: function () {
		StoreLinker.setPath();
	},

	setPath: function() {
		StoreLinker.path = config.ShopPath + '/admin';
	},

	load_list: function (list, dataType, mdata) {
		if (jQuery.trim($("#" + list).html()) != "") {
			return;
		}

		$.ajax({
			type: 'get',
			url: StoreLinker.path + '/remote.php?remoteSection=linker&w=search&d=' + dataType,
			data: mdata,
			success: function(data) {
				var result_list = document.createElement('UL');
				$("#" + list).append(result_list);

				$(data).find('result').each(function(i){
					var newItem = document.createElement('li');
					newItem.index = i;
					newItem.onclick =  function(e) {
						StoreLinker.select_item(this);
					}
					newItem.innerHTML = "<img src=\"" + StoreLinker.path + "/" + $(this).attr('icon') + "\" style=\"vertical-align: middle\" />&nbsp;" + $(this).attr('title');
					newItem.title = $(this).attr('title');
					newItem.id = $(this).attr('id');
					if ($(this).attr('catid') != undefined) {
						newItem.id = list +  "_" + $(this).attr('catid');
					}
					newItem.insertable = $(this).text();
					result_list.appendChild(newItem);
					if ($(this).attr('padding') != undefined) {
						newItem.style.paddingLeft = $(this).attr('padding') + 'px';
					}
				});
			}
		});
	},

	select_item: function (element) {
		$(element).siblings(".selected").removeClass("selected");
		$(element).addClass("selected");

		if ($(element).parents("div").attr("id") == "ProductByCategoryList") {
			var id = $(element).attr("id").substr(22);

			$("#ProductByKeywordList").html("");
			// load the products
			StoreLinker.load_list('ProductByKeywordList', 'products', 'category=' + id);

		}
		else {

			var parentListId = $(element).parents("div").attr("id");
			var dataType = 'product';
			var id = $(element).attr('id');

			switch(parentListId) {
				case 'CategoryList':
					dataType = 'category';
					id = id.replace('CategoryList_', '');
					break;
				case 'BrandList':
					dataType = 'brand';
					break;
				case 'PageList':
					dataType = 'page';
					break;
			}

			StoreLinker.selectedItem = {
				'href': element.insertable,
				'title': $(element).attr('title'),
				'id': id,
				'datatype': dataType
			};
		}
	},

	insertLink: function () {
		if (StoreLinker.selectedItem == "") {
			return;
		}

		var title = '';

		// is text selected?
		if (tinyMCE.activeEditor.selection.getContent()) {
			// delete the selection
			title = tinyMCE.activeEditor.selection.getContent();
			tinyMCE.activeEditor.selection.setContent('');
		}
		else {
			title = StoreLinker.selectedItem.title;
		}

		// Insert the link to the editor
		tinyMCEPopup.editor.execCommand('mceInsertContent', false, '<a href="' + StoreLinker.selectedItem.href + '">' + title + '</a>');

		tinyMCEPopup.close();
	},

	setSearchTimeout: function () {
		if (StoreLinker.timeout) {
			clearTimeout(StoreLinker.timeout);
		}

		StoreLinker.timeout = setTimeout("StoreLinker.searchProducts()", 1000);
	},

	searchProducts: function () {
		if ($("#productName").val() != "" && $("#productName").val().length > 2) {
			$("#ProductByKeywordList").html("");
			// load products by search term
			StoreLinker.load_list('ProductByKeywordList', 'products', 'searchQuery=' + $("#productName").val());

		}
		else {
			$("#ProductByKeywordList").html(lang.Linker_enter_terms);
		}
	},

	openModal: function (id, tabs) {
		StoreLinker.currentItemId = id;
		StoreLinker._openModal(StoreLinker.onModalClose, tabs);
	},

	_openModal: function(onBeforeCloseFunction, tabs) {
		var options = {
			type: 'ajax',
			url: 'remote.php?remoteSection=linker&w=loadLinker',
			onShow: function() {
				StoreLinker.load_list("ProductByCategoryList", "categories", "");
			},
			onBeforeClose: onBeforeCloseFunction,
			top: '10px'
		};

		if (typeof tabs != 'undefined') {
			options.url += '&tabs=' + encodeURIComponent(tabs);
		}

		$.iModal(options);
	},

	onModalClose: function () {
		var id = StoreLinker.currentItemId;
		if(typeof  StoreLinker.selectedItem.id == 'undefined') {
			return;
		}

		$('#RedirectType_' + id).val('auto');

		var postData = {
			'redirectid': id,
			'newid': StoreLinker.selectedItem.id,
			'datatype': StoreLinker.selectedItem.datatype
		};

		$.ajax({
			type: 'POST',
			url: 'remote.php?remoteSection=redirects&w=savelinkbyid',
			data: postData,
			dataType: 'json',
			success: function(json) {
				if(!json.success) {
					alert(json.message);
					return;
				}

				if(typeof json.tmpredirectid != 'undefined') {
					Redirects.updateRowId(json.tmpredirectid, json.redirectid);
				}

				$('#RedirectAutoURL_Link_' + json.redirectid).show().html('<a href="' + json.url + '" target="_blank">' + json.title + '</a>');
				$('#linkerButton_' + json.redirectid).html(lang.ChangeLink);
			}
		});
	},

	ShowTab: function(T, prefix)
	{

		if(typeof prefix == 'undefined') {
			prefix = 'linker_';
		}

		if(T==='' || $('#' + prefix + 'div_'+T).length <= 0 || $('#' + prefix + 'tab_'+T).length <= 0) {
			return false;
		}

		var activeTab = $('#' + prefix + 'tabnav .active');
		var tabName = activeTab.attr('id').replace(prefix + 'tab_', '');
		activeTab.removeClass('active');
		$('#' + prefix + 'div_'+tabName).hide();
		$('#' + prefix + 'div_'+T).show();
		$('#' + prefix + 'tab_'+T).addClass('active');
		$('#' + prefix + 'currentTab').val(T);
	}

}

StoreLinker.init();

$(document).ready(function() {
	$('.linkerButton').live('click', function(event){
		event.preventDefault();
		StoreLinker.openModal(this.id.replace('linkerButton_', ''));
	});
});
