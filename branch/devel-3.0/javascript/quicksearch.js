var QuickSearch = {
	minimum_length: 3,
	search_delay: 125,
	cache: new Object(),
	init: function()
	{
		$('#search_query').bind("keydown", QuickSearch.on_keydown);
		$('#search_query').bind("keyup", QuickSearch.on_keyup);
		$('#search_query').bind("change", QuickSearch.on_change);
		$('#search_query').blur(QuickSearch.on_blur);
		$('#search_query').attr('autocomplete', 'off');

		var scripts = document.getElementsByTagName('SCRIPT');
		for(var i = 0; i < scripts.length; i++)
		{
			s = scripts[i];
			if(s.src && s.src.indexOf('quicksearch.js') > -1)
			{
				QuickSearch.path = s.src.replace(/quicksearch\.js$/, '../');
				break;
			}
		}

	},

	on_blur: function(event)
	{
		if(!QuickSearch.item_selected && !QuickSearch.over_all)
		{
			QuickSearch.hide_popup();
		}
	},

	on_keydown: function(event)
	{
		if(event.keyCode == 13 && !event.altKey)
		{
			if(QuickSearch.selected)
			{
				try {
					event.preventDefault();
					event.stopPropagation();
				} catch(e) { }
				window.location = QuickSearch.selected.url;
				return false;
			}
			else
			{
				QuickSearch.hide_popup();
			}
		}
		else if(event.keyCode == 27)
		{
			if(document.getElementById('QuickSearch'))
			{
				try {
					event.preventDefault();
					event.stopPropagation();
				} catch(e) { }
			}
			QuickSearch.hide_popup();
		}
	},

	on_keyup: function(event)
	{
		if(QuickSearch.timeout)
		{
			clearTimeout(QuickSearch.timeout);
		}

		// Down key was pressed
		if(event.keyCode == 40 && QuickSearch.results)
		{
			if(QuickSearch.selected && QuickSearch.results.length >= QuickSearch.selected.index+1)
			{
				QuickSearch.highlight_item(QuickSearch.selected.index+1, true);
			}
			if(!QuickSearch.selected && QuickSearch.results.length > 0)
			{
				QuickSearch.highlight_item(0, true);
			}
			try {
				event.preventDefault();
				event.stopPropagation();
			} catch(e) { }
			return false;
		}
		else if(event.keyCode == 38 && QuickSearch.results)
		{
			if(QuickSearch.selected && QuickSearch.selected.index > 0)
			{
				QuickSearch.highlight_item(QuickSearch.selected.index-1, true);
			}
			try {
				event.preventDefault();
				event.stopPropagation();
			} catch(e) { }
		}
		else if(event.keyCode == 27)
		{
			QuickSearch.hide_popup();
		}
		else
		{
			if($('#search_query').val() == QuickSearch.last_query)
			{
				return false;
			}
			QuickSearch.selected = false;
			if($('#search_query').val().replace(/^\s+|\s+$/g, '').length >= QuickSearch.minimum_length)
			{
				QuickSearch.last_query = $('#search_query').val().replace(/^\s+|\s+$/g, '');
				if(QuickSearch.timeout)
				{
					window.clearTimeout(QuickSearch.timeout);
				}
				QuickSearch.timeout = window.setTimeout(QuickSearch.do_search, QuickSearch.search_delay);
			}
			else {
				if(document.getElementById('QuickSearch'))
				{
					$('#QuickSearch').remove();
				}
			}
		}
	},

	on_change: function(event)
	{
		return (QuickSearch.on_keydown(event) && QuickSearch.on_keyup(event));
	},

	do_search: function()
	{
		var cache_name = $('#search_query').val().length+$('#search_query').val();
		if(QuickSearch.cache[cache_name])
		{
			QuickSearch.search_done(QuickSearch.cache[cache_name]);
		}
		else
		{
			$.ajax({
				type: 'GET',
				dataType: 'xml',
				url: QuickSearch.path+'search.php?action=AjaxSearch&search_query='+encodeURIComponent($('#search_query').val()),
				success: function(response) { QuickSearch.search_done(response); }
			});
		}
	},

	search_done: function(response)
	{
		// Cache results
		var cache_name = $('#search_query').val().length+$('#search_query').val();
		QuickSearch.cache[cache_name] = response;

		if(document.getElementById('QuickSearch')) {
			$('#QuickSearch').remove();
		}

		if ($('result', response).length > 0) {
			var popup_container = document.createElement('TABLE');
			popup_container.className = 'QuickSearch';
			popup_container.id = 'QuickSearch';
			popup_container.cellPadding = "0";
			popup_container.cellSpacing = "0";
			popup_container.border = "0";

			var popup = document.createElement('TBODY');
			popup_container.appendChild(popup);

			var counter = 0;

			$('result', response).each(
				function()
				{
					var tr = $($(this).text());
					var url = $('.QuickSearchResultName a', tr).attr('href');
					var tmpCounter = counter;

					$(tr).attr('id', 'QuickSearchResult' + tmpCounter);
					$(tr).bind('mouseover', function() { QuickSearch.item_selected = true; QuickSearch.highlight_item(tmpCounter, false); });
					$(tr).bind('mouseup', function() { window.location = url; });
					$(tr).bind('mouseout', function() { QuickSearch.item_selected = false; QuickSearch.unhighlight_item(tmpCounter) });
					$(popup).append(tr);

					counter++;
				}
			);

			// More results than we're showing?
			var all_results_count = $('viewmoreurl', response).size();

			if(all_results_count)
			{
				var tr = document.createElement('TR');
				var td = document.createElement('TD');
				tr.className = "QuickSearchAllResults";
				tr.onmouseover = function() { QuickSearch.over_all = true; };
				tr.onmouseout = function() { QuickSearch.over_all = false; };
				td.colSpan = 2;
				td.innerHTML = $('viewmoreurl', response).text();
				tr.appendChild(td);
				popup.appendChild(tr);
			}

			var clone = popup.cloneNode(true);
			document.body.appendChild(clone);
			clone.style.top = "10px";
			clone.style.left = "10px";
			offset_height = clone.offsetHeight;
			offset_width = clone.offsetWidth;
			clone.parentNode.removeChild(clone);

			var offset_top = offset_left = 0;
			var element = document.getElementById('search_query');
			if(typeof(QuickSearchAlignment) != 'undefined' && QuickSearchAlignment == 'left') {
				offset_left = 0;
			}
			else {
				offset_left += element.offsetWidth - $('#SearchForm').width();
			}

			offset_top = -3;
			do
			{
				offset_top += element.offsetTop || 0;
				offset_left += element.offsetLeft || 0;
				element = element.offsetParent;
			} while(element);

			popup_container.style.position = "absolute";
			popup_container.style.left = offset_left + 1 + "px";
			popup_container.style.top = offset_top + document.getElementById('search_query').offsetHeight + "px";
			if(typeof(QuickSearchWidth) != 'undefined') {
				popup_container.style.width = QuickSearchWidth;
			}
			else {
				popup_container.style.width = document.getElementById('SearchForm').offsetWidth - 2 + "px";
			}
			if($('#QuickSearch'))
			{
				$('#QuickSearch').remove();
			}
			document.body.appendChild(popup_container);
			popup_container.style.display = '';
		}
		else
		{
			if(document.getElementById('QuickSearch'))
			{
				$('#QuickSearch').remove();
			}
		}
	},


	hide_popup: function()
	{
		$('#QuickSearch').remove();
		QuickSearch.selected = null;
	},

	highlight_item: function(index, keystroke)
	{
		element = $('#QuickSearchResult'+index);
		if(keystroke == true)
		{
			if(QuickSearch.selected) QuickSearch.selected.className = 'QuickSearchResult';
			QuickSearch.selected = document.getElementById('QuickSearchResult'+index);
		}
		element.addClass("QuickSearchHover");
	},

	unhighlight_item: function(index)
	{
		element = $('#QuickSearchResult'+index);
		element.removeClass('QuickSearchHover');
	}
};

$(document).ready(function()
{
	QuickSearch.init();
});
