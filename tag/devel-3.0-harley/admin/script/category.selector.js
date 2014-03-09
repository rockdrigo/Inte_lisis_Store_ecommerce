/*
 * jQuery UI Interspire Category Selector
 */
(function($) {
	$.widget("ui.categorySelector", {
		_init: function() {
			var o = this.options;
			var all = false;

			$('.intro', this.element).html(o.intro);
			$('.ModalTitle', this.element).html(o.title);

			// set cancel and save buttons
			$('.cancel', this.element).click(this.options.cancel);
			$('.save', this.element).click(this.delegate('save'));

			if(typeof o.categoryid == 'undefined' || o.categoryid === '' || o.categoryid == 0) {
				this.getCategories(0);
			}
			else
			{
				this.setSelectedCategory(o.categoryid);
				this.leafSelected(true);
				this.getCategories(o.categoryid, true);
			}
		},

		/**
		 * Make an ajax request to get categories
		 */
		getCategories : function(categoryid, all){
			var o = this.options;
			o.currentid = categoryid;

			if(typeof all == 'undefined')
				all = '';
			else
				all = '&all=true';

			$.ajax({
				url: o.getCategoriesURL + '&categoryid=' + categoryid + all,
				dataType: 'json',
				success: this.delegate('getCategoriesResponse')
			});
		},

		/**
		 * Handles the getCategories response, attaches callbacks
		 * to list elements in the response.
		 */
		getCategoriesResponse : function(data){
			var o = this.options;

			// @todo: change to manage concurrency issues by using
			// sequential request ids, rather than categoryid
			if(o.currentid != null & o.currentid != data.categoryid)
				return;
			o.currentid = o.currentid + 'f';

			for(var i=data.boxes.length - 1;i >=0 ;i--)
			{

			var box = data.boxes[i];

			if(box.categoryid == 0) {
				$('.CategoriesRow', this.element).html(box.html);
			}
			else
			{
				$(box.html).appendTo($('.CategoriesRow', this.element));
				$('.CategoriesContainer', this.element).scrollLeft(
					$('.CategoriesContainer', this.element)[0].scrollWidth);
			}

			var categorySelectorWidget = this;
			var boxid = '#' + 'Category-' + box.categoryid;

			/**
			 * Set callbacks to trigger a getCategories call when a category
			 * with children is clicked, otherwise set the clicked category
			 * as the selected category and return.
			 */
			$(boxid + ' input', this.element).bind('click', function() {
				if($(this).attr('checked') == 'checked')
					return;

				var box = $(this).parents('td');

				box.nextAll('td').remove();

				$('li', box).removeClass('SelectedRow');
				$(this).parents('li').addClass('SelectedRow');
				$(this).attr('checked', 'checked');

				var name = $(this).next('.category_name').html();
				$('.selected_category_name', box).html(name);
				categorySelectorWidget.setSelectedCategory($(this).val());

				if($(this).hasClass('CategoryLeaf')) {
					categorySelectorWidget.leafSelected(true);
					return;
				}

				categorySelectorWidget.leafSelected(false);
				categorySelectorWidget.getCategories($(this).val());
			});

			/**
			 * Set callbacks to do on hover row highlighting
			 */
			$(boxid + ' li', this.element).bind('mouseenter', function() {
				$(this).addClass('ISSelectOptionHover');
			});

			$(boxid + ' li', this.element).bind('mouseleave', function() {
				$(this).removeClass('ISSelectOptionHover');
			})
			}
		},

		leafSelected: function(selected)
		{
			var o = this.options;

			if(selected) {
				o.leafSelected = true;
				this.showMessage(
					o.messages['leafCategorySelected'],
					'Success');
			}
			else
			{
				o.leafSelected = false;
				this.clearMessage();
			}
		},

		/**
		 * Displays a message in the modal window's message location
		 * marked by a container element with the class 'message'
		 */
		showMessage: function(message, type)
		{
			var messageBox = $('.message', this.element);

			messageBox
				.hide()
				.removeClass('MessageBox MessageBoxSuccess MessageBoxInfo')
				.addClass('MessageBox MessageBox' + type)
				.html(message)
				.fadeIn('slow');
		},

		/**
		 * Clears messages
		 */
		clearMessage: function()
		{
			$('.message', this.element)
				.hide()
				.removeClass('MessageBox MessageBoxSuccess MessageBoxInfo')
				.html('');
		},

		setSelectedCategory: function(categoryid)
		{
			var o = this.options;

			o.selectedCategory = categoryid;
		},

		getSelectedCategoryPath: function()
		{
			var names = [];

			$.each($('.selected_category_name', this.element), function(index, e) {
				if($(e).text())
					names.push($(e).text());
			});

			return names.join(' > ');
		},

		/**
		 * Check if a valid category is selected and trigger
		 * the save callback passed in the option.
		 */
		save : function(){
			var o = this.options;

			if(!o.leafSelected) {
				this.showMessage(
					o.messages['chooseLeafCategory'],
					'Info');
			}
			else
			{
				var path = this.getSelectedCategoryPath();
				o.success({path: path, id: o.selectedCategory});
			}
		},

		/**
		 * Returns an anonymous function wrapper to the given
		 * method with context bound to the current object.
		 */
		delegate: function(method){
			var self = this;

			return function(){
				return self[method].apply(self, arguments);
			};
		},

		options: {
			title: null,
			intro: null,
			getCategoriesURL: null,
			success: null,
			currentid: null
	    }
	});
})(jQuery);