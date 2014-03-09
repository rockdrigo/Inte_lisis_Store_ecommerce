/**
* code for the media service tinymce plugin - defines service models and tinymce communication stuff
*
* minifying note: jsmin+ doesn't seem to like this, jsmin regular is fine
*
* see js/mediaservice.js for code that runs the dialog
*/
;(function() {
	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('mediaservice');

	tinymce.create('tinymce.plugins.MediaService', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
			ed.addCommand('mceMediaService', function() {
				ed.windowManager.open({
					file : url + '/mediaservice.html',
					width : 486 + parseInt(ed.getLang('mediaservice.delta_width', 0), 10),
					height : 540 + parseInt(ed.getLang('mediaservice.delta_height', 0), 10),
					inline : 1
				}, {
					plugin_url : url, // Plugin absolute URL
					some_custom_arg : 'custom arg' // Custom argument
				});
			});

			// Register example button
			ed.addButton('mediaservice', {
				title : 'mediaservice.desc',
				cmd : 'mceMediaService',
				image : url + '/img/mediaservice.gif'
			});

			ed.onInit.add(function() {
				if (ed.settings.content_css !== false) {
					ed.dom.loadCSS(url + "/css/content.css");
				}
			});

			ed.onBeforeSetContent.add(function(ed, o){
				o.content = o.content.replace(/<!-- mceItemMediaService_(.*?):(.*?) -->[\s\S]*?<!-- \/mceItemMediaService -->/g, function(match, serviceName, serializedData){
					// for every instance of an embed created by the media service plugin, replace it with an image placeholder
					// the full embed code inside the comments is not needed as all useful data is stored, serialized, after the mceItemMediaService_????: part of the first comment
					var service = tinymce.plugins.MediaService.services[serviceName];
					service.unserializeData($.htmlDecode(serializedData));
					var ph = service.getPlaceholderHtml(url);
					return ph;
				});
			});

			ed.onPostProcess.add(function(ed, o){
				if (!o.get) {
					// only process the placeholders when reading the content of the editor, not when setting
					return;
				}

				o.content = o.content.replace(/<img.*?title="mceItemMediaService_([^:]+):([^"]+)".*?\/>/g, function(match, serviceName, serializedData){
					// for every instance of an img placeholder for the mediaservice plugin, replace it with full embed html specific to the media service it represents
					var service = tinymce.plugins.MediaService.services[serviceName];
					service.unserializeData($.htmlDecode(serializedData));

					if (o.source_view) {
						// when opening the html source view, don't include video embed html because editing it is pointless
						return service.getEmbedHtml(false);
					}

					return service.getEmbedHtml();
				});
			});

			ed.onNodeChange.add(function(ed, cm, n) {
				if (n !== null && /^mceItemMediaService_(.*?):(.*?)$/.test(n.title)) {
					cm.setActive('mediaservice', true);
				} else {
					cm.setActive('mediaservice', false);
				}
			});
		},

		/**
		 * Creates control instances based in the incomming name. This method is normally not
		 * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
		 * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
		 * method can be used to create those.
		 *
		 * @param {String} n Name of the control to create.
		 * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
		 * @return {tinymce.ui.Control} New control instance or null if no control was created.
		 */
		createControl : function(n, cm) {
			return null;
		},

		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
				longname : 'Hosted Media Service Embedder',
				author : 'Interspire',
				authorurl : 'http://www.interspire.com',
				infourl : 'http://www.interspire.com',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('mediaservice', tinymce.plugins.MediaService);

	tinymce.plugins.MediaService.services = {};

	// base service model class
	tinymce.plugins.MediaService.ServiceModel = function () {
		var self = this;

		var _serviceName, _logo, _description, _urlRegExp, _width, _height, _url, _videoId;

		_urlRegExp = [];

		self.setUrl = function (url) {
			_url = url;

			// update the video id by checking assigned url regexps
			var result;
			_videoId = null;
			for (var i = _urlRegExp.length; i--;) {
				result = _urlRegExp[i].exec(_url);
				if (!!result) {
					_videoId = result[1];
				}
			}
		}

		self.getUrl = function () {
			return _url;
		};

		self.setServiceName = function (serviceName) {
			_serviceName = serviceName;
		};

		self.getServiceName = function () {
			return _serviceName;
		};

		self.setDescription = function (description) {
			_description = description;
		};

		self.getDescription = function () {
			return _description;
		};

		self.setLogo = function (logo) {
			_logo = logo;
		};

		self.getLogo = function () {
			return _logo;
		};

		self.addUrlRegExp = function (urlRegExp) {
			_urlRegExp.push(urlRegExp);
		};

		/**
		* @type array
		*/
		self.getUrlRegExp = function () {
			return _urlRegExp;
		};

		self.setWidth = function (width) {
			_width = width;
		};

		self.getWidth = function () {
			return _width;
		};

		self.getCssWidth = function () {
			if (typeof _width == 'string' && (_width.indexOf('%') !== -1 || _width.indexOf('px'))) {
				return _width;
			}
			return _width + 'px';
		};

		self.setHeight = function (height) {
			_height = height;
		};

		self.getHeight = function () {
			return _height;
		};

		self.getCssHeight = function () {
			if (typeof _height == 'string' && (_height.indexOf('%') !== -1 || _height.indexOf('px'))) {
				return _height;
			}
			return _height + 'px';
		};

		/**
		* @returns true if the url matched this model's url regexp list, otherwise false
		* @type boolean
		*/
		self.testUrl = function (url) {
			for (var i = _urlRegExp.length; i--;) {
				if (_urlRegExp[i].test(url)) {
					return true;
				}
			}
			return false;
		};

		self.setVideoId = function (videoId) {
			_videoId = videoId;
		};

		/**
		* @returns the id of the video based on the input url provided by the constructor, or false if no match was found -- a child class may override this method and return a different type if necessary
		* @type string
		*/
		self.getVideoId = function () {
			return _videoId;
		};

		/**
		* @returns the current service's configuration data as a string which can be stored in the HTML DOM of the tinymce editor instance
		* @type string
		*/
		self.serializeData = function () {
			return tinymce.util.JSON.serialize(self.getSerializableData());
		};

		/**
		*
		*/
		self.unserializeData = function (data) {
			self.setUnserializedData(tinymce.util.JSON.parse(data));
		};

		/**
		* If a model needs to override the data which is saved by serializing, it can override this method -- all models have video id, width and height
		*/
		self.getSerializableData = function () {
			var data = {};
			data.id = self.getVideoId();
			data.width = self.getWidth();
			data.height = self.getHeight();
			return data;
		};

		/**
		* If a model needs to override the data which is loaded by unserializing, it can override this method -- all models have video id, width and height
		*/
		self.setUnserializedData = function (data) {
			self.setVideoId(data.id);
			self.setWidth(data.width);
			self.setHeight(data.height);
		};

		/**
		* @returns the placeholder HTML for use when in WYSIWYG mode of tinymce
		* @type string
		*/
		self.getPlaceholderHtml = function (pluginUrl) {
			return '<img src="' + $.htmlEncode(pluginUrl) + '/img/trans.gif" mce_src="' + $.htmlEncode(pluginUrl) + '/img/trans.gif" class="mceItemMediaService mceItemMediaService_' + $.htmlEncode(self.getServiceName()) + ' mceItemNoResize" title="mceItemMediaService_' + $.htmlEncode(self.getServiceName()) + ':' + $.htmlEncode(self.serializeData()) + '" width="' + $.htmlEncode(self.getWidth()) + '" height="' + $.htmlEncode(self.getHeight()) + '" />';
		};

		/**
		* Given a DOM node, will update the properties of the node with the current service settings
		*/
		self.updatePlaceholderNode = function (node) {
			node.className = "mceItemMediaService mceItemMediaService_" + self.getServiceName();
			node.title = "mceItemMediaService_" + self.getServiceName() + ":" + self.serializeData();
			node.width = self.getWidth();
			node.height = self.getHeight();
			node.style.width = self.getCssWidth();
			node.style.height = self.getCssHeight();
		};

		self.getEmbedHtml = function (includeHtml) {
			if (typeof includeHtml == 'undefined') {
				var includeHtml = true;
			}

			var html;
			if (includeHtml) {
				html = self._getEmbedHtml();
			} else {
				html = '';
			}

			return '<!-- mceItemMediaService_' + self.getServiceName() + ':' + $.htmlEncode(self.serializeData()) + ' --><!-- do not directly edit this HTML, it will be overwritten by the mediaservice plugin -->' + html + '<!-- /mceItemMediaService -->';
		};

		// global default options
		self.setWidth(440);
		self.setHeight(330);

	}; // base service model

	tinymce.plugins.MediaService.YouTubeServiceModel = function () {
		// this model is more detailed that the others as it was originally intended to have a per-service-model "advanced" tab

		var self = this;

		// @todo "enable delayed cookies"

		tinymce.plugins.MediaService.ServiceModel.call(this);

		var _color1, _color2, _delayedCookies;

		self.getColor1 = function () {
			return _color2;
		};

		self.getColor2 = function () {
			return _color1;
		};

		self.getDelayedCookies = function () {
			return _delayedCookies;
		};

		self.getTimeCode = function () {
			return '';
		};

		self.getAllowFullScreen = function () {
			return true;
		};

		self.getVideoUrl = function () {
			return 'http://youtube.com/watch?v=' + encodeURIComponent(self.getVideoId());
		};

		self.getEmbeddedUrl = function () {
			var url = "http://www.";

			if (self.getDelayedCookies()) {
				url += "youtube-nocookie";
			} else {
				url += "youtube"
			}

			url += ".com/v/" + encodeURIComponent(self.getVideoId());

			params = [];

			if (self.getAllowFullScreen()) {
				params.push("fs=1");
			}

			// @todo implement these
			// &hl=en ?
			// &hd=1 enabled high def mode if available on video
			// &rel=0 disable related videos
			// &color1=0x3a3a3a border/ui colour 1
			// &color2=0x999999 border/ui colour 2
			// &border=1 show border

			if (params.length) {
				url += "?" + params.join("&");
			}

			var timeCode = self.getTimeCode();
			if (timeCode) {
				url += "#t=" + timeCode;
			}

			return url;
		};

		self._getEmbedHtml = function () {
			var html = [];

			// @todo html encoding of variables
			html.push('<object width="' + $.htmlEncode(self.getWidth()) + '" height="' + $.htmlEncode(self.getHeight()) + '">');
			html.push('<param name="movie" value="' + $.htmlEncode(self.getEmbeddedUrl()) + '"></param>');

			if (self.getAllowFullScreen()) {
				html.push('<param name="allowFullScreen" value="true"></param>');
			}

			html.push('<param name="allowscriptaccess" value="always"></param>');

			html.push('<embed src="' + $.htmlEncode(self.getEmbeddedUrl()) + '"');
			html.push(' type="application/x-shockwave-flash"');
			html.push(' allowscriptaccess="always"');

			if (self.getAllowFullScreen()) {
				html.push(' allowfullscreen="true"');
			}

			html.push(' width="' + $.htmlEncode(self.getWidth()) + '"');
			html.push(' height="' + $.htmlEncode(self.getHeight()) + '"');
			html.push('></embed></object>');

			return html.join('');
		};

		self.setServiceName('youtube');
		self.setDescription('YouTube');
		self.setLogo('youtube.gif');

		/*
		self.setWidth(425);
		self.setHeight(344);
		*/

		var rxp = new RegExp();
		rxp.compile('youtube\\..*?v=([^&]+)&?', 'i');
		self.addUrlRegExp(rxp);
	}; // youtube

	tinymce.plugins.MediaService.VimeoServiceModel = function () {
		var self = this;

		tinymce.plugins.MediaService.ServiceModel.call(this);

		self.getVideoUrl = function () {
			return 'http://vimeo.com/' + encodeURIComponent(self.getVideoId());
		};

		self.getEmbeddedUrl = function () {
			var url = "http://vimeo.com/moogaloop.swf";

			params = {};

			params.clip_id = self.getVideoId();
			params.server = "vimeo.com";

			// default options
			params.show_title = 1;
			params.show_byline = 1;
			params.show_portrait = 0;
			params.color = '';
			params.fullscreen = 1;

			var paramName, paramValue;
			var first = true;

			for (paramName in params) {
				if (first) {
					url += "?";
					first = false;
				} else {
					url += "&";
				}

				paramValue = params[paramName];

				url += paramName + "=" + encodeURIComponent(paramValue);
			}

			return url;
		};

		self._getEmbedHtml = function () {
			var html = '<object width="' + $.htmlEncode(self.getWidth()) + '" height="' + $.htmlEncode(self.getHeight()) + '"><param name="allowfullscreen" value="true" /><param name="allowscriptaccess" value="always" /><param name="movie" value="' + $.htmlEncode(self.getEmbeddedUrl()) + '" /><embed src="' + $.htmlEncode(self.getEmbeddedUrl()) + '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' + $.htmlEncode(self.getWidth()) + '" height="' + $.htmlEncode(self.getHeight()) + '"></embed></object>';

			// standard embed code from vimeo also includes a text link, like...
			// <p><a href="http://vimeo.com/$id">$title</a> from <a href="http://vimeo.com/user$userid">$user</a> on <a href="http://vimeo.com">Vimeo</a>.</p>
			// but we can't do that without an api call

			return html;
		};

		self.setServiceName('vimeo');
		self.setDescription('Vimeo');
		self.setLogo('vimeo.gif');

		/*
		self.setWidth(504);
		self.setHeight(340);
		*/

		var rxp = new RegExp();
		rxp.compile('vimeo\\.com/([0-9]+)', 'i');
		self.addUrlRegExp(rxp);
	}; // vimeo

	/*
	// google video support removed as it would cause too much confusion -- most videos on google video are externally hosted as they no longer allow new uploads
	tinymce.plugins.MediaService.GoogleVideoServiceModel = function () {
		var self = this;

		tinymce.plugins.MediaService.ServiceModel.call(this);

		self.getVideoUrl = function () {
			return 'http://video.google.com/videoplay?docid=' + encodeURIComponent(self.getVideoId());
		};

		self.getEmbeddedUrl = function () {
			var url = "http://video.google.com/googleplayer.swf";

			params = {};

			params.docid = self.getVideoId();

			// default options
			params.fs = 'true';

			var paramName, paramValue;
			var first = true;

			for (paramName in params) {
				if (first) {
					url += "?";
					first = false;
				} else {
					url += "&";
				}

				paramValue = params[paramName];

				url += paramName + "=" + encodeURIComponent(paramValue);
			}

			return url;
		};

		self._getEmbedHtml = function () {
			return '<embed src="' + $.htmlEncode(self.getEmbeddedUrl()) + '" style="width:' + self.getCssWidth() + ';height:' + self.getCssHeight() + '" allowFullScreen="true" allowScriptAccess="always" type="application/x-shockwave-flash"></embed>';
		};

		self.setServiceName('googlevideo');
		self.setDescription('Google Videos');
		self.setLogo('googlevideo.gif');

		var rxp = new RegExp();
		rxp.compile('video\\.google\\..*?docid=([\\-0-9]+)', 'i');
		self.addUrlRegExp(rxp);
	}; // google video
	*/

	tinymce.plugins.MediaService.MegavideoServiceModel = function () {
		var self = this;

		tinymce.plugins.MediaService.ServiceModel.call(this);

		self.getVideoUrl = function () {
			return 'http://www.megavideo.com/?v=' + encodeURIComponent(self.getVideoId());
		};

		self.getEmbeddedUrl = function () {
			return 'http://www.megavideo.com/v/' + encodeURIComponent(self.getVideoId());
		};

		self._getEmbedHtml = function () {
			return '<object width="' + self.getWidth() + '" height="' + self.getHeight() + '"><param name="movie" value="' + $.htmlEncode(self.getEmbeddedUrl()) + '"></param><param name="allowFullScreen" value="true"></param><embed src="' + $.htmlEncode(self.getEmbeddedUrl()) + '" type="application/x-shockwave-flash" allowfullscreen="true" width="' + self.getWidth() + '" height="' + self.getHeight() + '"></embed></object>';
		};

		self.setServiceName('megavideo');
		self.setDescription('Megavideo');
		self.setLogo('megavideo.gif');

		var rxp = new RegExp();
		rxp.compile('megavideo\\.com/\\?v=([A-Z0-9]+)', 'i');
		self.addUrlRegExp(rxp);
	}; // megavideo

	tinymce.plugins.MediaService.MetacafeServiceModel = function () {
		var self = this;

		tinymce.plugins.MediaService.ServiceModel.call(this);

		self.getVideoUrl = function () {
			return 'http://www.metacafe.com/watch/' + encodeURIComponent(self.getVideoId());
		};

		self.getEmbeddedUrl = function () {
			return 'http://www.metacafe.com/fplayer/' + encodeURIComponent(self.getVideoId()) + '/' + encodeURIComponent(self.getVideoId()) + '.swf';
		};

		self._getEmbedHtml = function () {
			return '<embed src="' + $.htmlEncode(self.getEmbeddedUrl()) + '" width="' + self.getWidth() + '" height="' + self.getHeight() + '" wmode="transparent" allowFullScreen="true" allowScriptAccess="always" name="Metacafe_' + $.htmlEncode(self.getVideoId()) + '" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash"></embed>';
		};

		self.setServiceName('metacafe');
		self.setDescription('Metacafe');
		self.setLogo('metacafe.gif');

		var rxp = new RegExp();
		rxp.compile('metacafe\\.com/watch/([0-9]+)', 'i');
		self.addUrlRegExp(rxp);
	}; // metacafe

	/*
	* can't use these without a backend call to an api or screen-scraping...
	*
	* blip.tv - uses a different embed id which isn't in the page url
	* break.com - uses different embed id which isn't in the page url
	* dailymotion - uses different embed id which isn't in the page url
	* flickr.com - has a second photo_secret id which isn't in the page url
	*
	* other potential embeds...
	*
	* myspace videos
	* page url: http://vids.myspace.com/index.cfm?fuseaction=vids.individual&VideoID=60967361
	* embed: <object width="425px" height="360px" ><param name="allowFullScreen" value="true"/><param name="wmode" value="transparent"/><param name="movie" value="http://mediaservices.myspace.com/services/media/embed.aspx/m=60967361,t=1,mt=video"/><embed src="http://mediaservices.myspace.com/services/media/embed.aspx/m=60967361,t=1,mt=video" width="425" height="360" allowFullScreen="true" type="application/x-shockwave-flash" wmode="transparent"></embed></object>
	*
	* veoh
	* page url: http://www.veoh.com/browse/videos/#watch%3Dv18990116bTYDNcK8
	* embed: <object width="410" height="341" id="veohFlashPlayer" name="veohFlashPlayer"><param name="movie" value="http://www.veoh.com/static/swf/webplayer/WebPlayer.swf?version=AFrontend.5.4.2.24.1005&permalinkId=v18990116bTYDNcK8&player=videodetailsembedded&videoAutoPlay=0&id=anonymous"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.veoh.com/static/swf/webplayer/WebPlayer.swf?version=AFrontend.5.4.2.24.1005&permalinkId=v18990116bTYDNcK8&player=videodetailsembedded&videoAutoPlay=0&id=anonymous" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="410" height="341" id="veohFlashPlayerEmbed" name="veohFlashPlayerEmbed"></embed></object>
	* note: id="veohFlashPlayer" should probably be stripped
	*
	* yahoo videos
	* page url: http://video.yahoo.com/watch/5979735/15372610
	* embed: <object width="512" height="322"><param name="movie" value="http://d.yimg.com/static.video.yahoo.com/yep/YV_YEP.swf?ver=2.2.46" /><param name="allowFullScreen" value="true" /><param name="AllowScriptAccess" VALUE="always" /><param name="bgcolor" value="#000000" /><param name="flashVars" value="id=15372610&vid=5979735&lang=en-us&intl=us&thumbUrl=http%3A//l.yimg.com/a/im_siggDK5ok7Xsk.ncjAkBARbJOw---x158/p/i/bcst/yp/primetimetv/10342/93121855.jpg&embed=1" /><embed src="http://d.yimg.com/static.video.yahoo.com/yep/YV_YEP.swf?ver=2.2.46" type="application/x-shockwave-flash" width="512" height="322" allowFullScreen="true" AllowScriptAccess="always" bgcolor="#000000" flashVars="id=15372610&vid=5979735&lang=en-us&intl=us&thumbUrl=http%3A//l.yimg.com/a/im_siggDK5ok7Xsk.ncjAkBARbJOw---x158/p/i/bcst/yp/primetimetv/10342/93121855.jpg&embed=1" ></embed></object>
	* note: there's a thumbnail url though, not sure what'll happen if that's left off
	*
	* revver:
	* page url: http://revver.com/video/1902863/there-is-no-ball/
	* embed: <object width="480" height="392" data="http://flash.revver.com/player/1.0/player.swf?mediaId=1902863" type="application/x-shockwave-flash" id="revvervideoa17743d6aebf486ece24053f35e1aa23"><param name="Movie" value="http://flash.revver.com/player/1.0/player.swf?mediaId=1902863"></param><param name="FlashVars" value="allowFullScreen=true"></param><param name="AllowFullScreen" value="true"></param><param name="AllowScriptAccess" value="always"></param><embed type="application/x-shockwave-flash" src="http://flash.revver.com/player/1.0/player.swf?mediaId=1902863" pluginspage="http://www.macromedia.com/go/getflashplayer" allowScriptAccess="always" flashvars="allowFullScreen=true" allowfullscreen="true" height="392" width="480"></embed></object>
	* node: id="revvervideoa17743d6aebf486ece24053f35e1aa23" should probably be stripped
	*/

	// the order services are added here will be the order they are displayed in for any html lists
	tinymce.plugins.MediaService.services.youtube = new tinymce.plugins.MediaService.YouTubeServiceModel();
	//tinymce.plugins.MediaService.services.googlevideo = new tinymce.plugins.MediaService.GoogleVideoServiceModel();
	tinymce.plugins.MediaService.services.vimeo = new tinymce.plugins.MediaService.VimeoServiceModel();
	tinymce.plugins.MediaService.services.metacafe = new tinymce.plugins.MediaService.MetacafeServiceModel();
	tinymce.plugins.MediaService.services.megavideo = new tinymce.plugins.MediaService.MegavideoServiceModel();

	/**
	* Checks all registered media services against the given url and returns the first one that matches
	* @returns an instance of ServiceModel for the matched service or false if no service was matched
	* @type tinymce.plugins.MediaService.ServiceModel
	*/
	tinymce.plugins.MediaService.getServiceByUrl = function (url) {
		if (!url) {
			return false;
		}

		var serviceName, service;
		for (serviceName in tinymce.plugins.MediaService.services) {
			service = tinymce.plugins.MediaService.services[serviceName];

			if (service.testUrl(url)) {
				return service;
			}
		}

		return false;
	};

	/**
	* @returns a list of registered services as an array
	* @type Array
	*/
	tinymce.plugins.MediaService.getServiceList = function () {
		var list = [];
		var serviceName;
		for (serviceName in tinymce.plugins.MediaService.services) {
			list.push(tinymce.plugins.MediaService.services[serviceName]);
		}
		return list;
	};

})();
