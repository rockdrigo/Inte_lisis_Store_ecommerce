/**
 * Bind an event listener to a disabled node (like a disabled form button) by layering an interactable div over it. All
 * events will be triggered in the context of the interactable div, not the original element. If the original element
 * ever changes position or size then the overlay div may no longer be accurate and may need to be removed by using
 * disabledUnbind() to remove all events, which will also remove the overlay (then re-bind the listeners as needed).
 *
 * Example:
 * $('#disabledElement').disabledBind([event type, [listener function]]);
 * $('#disabledElement').disabledUnbind([event type, [listener function]]);
 *
 * Changelog:
 *
 * 2010-09-20
 * - Brought in to BC
 * - Changed margins on clickable div from 0, to using original node margin values
 * - Added disabledUnbind
 *
 * @author Gwilym Evans <gwilym.evans@interspire.com>
 * @param {String} type The event type to bind (eg. click, mousedown, etc.)
 * @param {Function} listener The function to call when the disabled element is clicked
 */
;(function($){
	$.fn.disabledBind = function (type, listener) {
		return $(this).each(function(){
			var node = $(this);

			//	check if this node already has a disabled bind wrapper
			var wrapper = node.closest('.disabledBindWrapper');
			if (!wrapper.length) {
				//	if not, create a node with a relative positioning to wrap the disabled node so we can position a clickable node over it
				wrapper = $('<span class="disabledBindWrapper"></span>').insertBefore(node).css('position', 'relative')
				node.appendTo(wrapper);
			}

			//	check if this node already has a clickable overlay
			var clickable = wrapper.find('.disabledBindClickable');

			if (!clickable.length) {
				//	if not, create one

				//	get the new relative position of the disabled node
				var position = node.position();

				//	create a clickable div node
				var clickable = $('<div class="disabledBindClickable"></div>')
					.css('position', 'absolute')				//	positioned on top of the disabled element
					.css('top', position.top + 'px')			//	... using the new relative position of the disabled node
					.css('left', position.left + 'px')
					.css('margin-top', node.css('margin-top'))	// take margins from the original node
					.css('margin-right', node.css('margin-right'))
					.css('margin-bottom', node.css('margin-bottom'))
					.css('margin-left', node.css('margin-left'))
					.css('padding', '0')	//	remove paddings which may be introduced on our div by css
					.css('border', 'none')	//	... and borders
					.width(node.outerWidth())	//	size it to the same width as the original node
					.height(node.outerHeight())	//	... and height of the disabled element
					.insertAfter(node);	//	put it immediately after the disabled node in the dom

				//	if the disabled node had a z-index defined, make the disabled node's z-index 1 higher
				var zIndex = node.css('z-index');
				if (zIndex != 'auto' && zIndex != '') {
					clickable.css('z-index', parseInt(zIndex, 10) + 1);
				}
			}

			clickable.bind(type, listener);	//	bind our event to the clickable node (either created above or existing)
		});
	};

	$.fn.disabledUnbind = function (type, listener) {
		return $(this).each(function(){
			var node = $(this);

			var wrapper = node.closest('.disabledBindWrapper');
			if (!wrapper.length) {
				// if the node has no wrapper, there have been no disabledBind calls yet - abort
				return;
			}

			var clickable = wrapper.find('.disabledBindClickable');
			if (!clickable.length) {
				// if the node has no clickable overlay, there have been no disabledBind calls yet - abort
				return;
			}

			clickable.unbind(type, listener);

			// check to see if the clickable and wrapper dom elements can be removed
			if (clickable.data('events') === null) {
				clickable.remove();
				if (wrapper.data('events') === null) {
					node.insertBefore(wrapper);
					wrapper.remove();
				}
			}
		});
	};
})(jQuery);
