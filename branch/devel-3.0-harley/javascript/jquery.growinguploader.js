(function($){
	$.fn.growingUploader = function (options) {
		var self = this;

		self.randomString = function() {
			var chars = "0123456789abcdefghiklmnopqrstuvwxyz";
			var string_length = 8;
			var randomstring = '';
			for (var i=0; i<string_length; i++) {
				var rnum = Math.floor(Math.random() * chars.length);
				randomstring += chars.substring(rnum,rnum+1);
			}
			return randomstring;
		};

		var options = $.extend({
			fileSelector: ':file',
			clearSelector: ':button',
			minimum: 1,
			maximum: 0,
			first: true
		}, options);

		var file = $(options.fileSelector, this);
		var clear = $(options.clearSelector, this);

		if (options.first) {
			options.master = $(this).clone();
		}

		this.fileChange = function () {
			var val = file.val();
			if (val != lastval) {
				lastval = val;
				var emptyfiles = $(':file[value=]', this.parent());

				if (val) {
					//	duplicate the row if there are no more blank file inputs
					options.first = false;
					if (!emptyfiles.length) {
						this.each(function(){
							var node = options.master
											.clone()
											.insertAfter(this)
											.growingUploader(options);
						});
					}
				} else {
					//	remove the row if there are other blank file inputs
					if (emptyfiles.length > 1) {
						this.each(function(){
							$(this).remove();
						});
					}
				}
			}

			if (val) {
				$(options.clearSelector, this).show();
			} else {
				$(options.clearSelector, this).hide();
			}
		};

		this.clearClick = function () {
			self.remove();
		};

		if (options.first) {
			var lastval = file.val();
			//	wrap our first upload container in a parent div so we can examine all uploaders as a collection
			this.appendTo($('<div></div>').insertBefore(this));
			if (!file.attr('id')) {
				// if the first uploader does not have an id already, generate one for it
				file.attr('id', 'growingUpload_' + self.randomString());
			}
		} else {
			var lastval = '';
			file.val('');

			while (true) {
				var randomId = 'growingUpload_' + self.randomString();
				if (!$('#' + randomId).length) {
					file.attr('id', randomId);
					break;
				}
			}
		}

		this.fileChange();

		return this.each(function(){
			file.change(function(){ self.fileChange(); });
			clear.click(function(evt){ evt.preventDefault(); self.clearClick(); });
		});
	};
})(jQuery);

$(function(){
	$('.Uploader').growingUploader();
});
