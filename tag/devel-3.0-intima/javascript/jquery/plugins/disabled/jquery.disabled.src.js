/**
 * A small jquery plugin that simplifies access to the disabled attribute by introducing 'enabled' and 'disabled' functions on jquery objects.
 *
 * Usage:
 *
 * $(selector).enable(); // enable all selected elements
 * $(selector).disable(); // disable all selected elements
 *
 * $(selector).enabled(<true|false>); // set status of all selected elements
 * $(selector).disabled(<true|false>); // set status of all selected elements
 *
 * $(selector).disabled() === true; // if all elements are disabled
 * $(selector).enabled() === false; // if any element is disabled
 *
 * $(selector).enabled() === true; // if all elements are enabled
 * $(selector).disabled() === false; // if any element is enabled
 *
 * The plugin will internally handle adding+setting or removing of the disabled attribute.
 *
 * @author Gwilym Evans <@interspire.com>
 */
;(function ($) {

	$.fn.enabled = function (enabled) {
		var self = this;
		var $$ = $(self);

		if (typeof enabled == 'undefined') {
			return $$.length === $$.filter(':enabled').length;
		}

		$$.each(function () {
			if (enabled) {
				$(this).removeAttr('disabled');
			} else {
				$(this).attr('disabled', 'disabled');
			}
		});

		return $$;
	};

	$.fn.enable = function () {
		return $(this).enabled(true);
	};

	$.fn.disabled = function (disabled) {
		var self = this;
		var $$ = $(self);

		if (typeof disabled == 'undefined') {
			return $$.length === $$.filter(':disabled').length;
		}

		$$.each(function () {
			if (disabled) {
				$(this).attr('disabled', 'disabled');
			} else {
				$(this).removeAttr('disabled');
			}
		});

		return $$;
	};

	$.fn.disable = function () {
		return $(this).disabled(true);
	};

})(jQuery);
