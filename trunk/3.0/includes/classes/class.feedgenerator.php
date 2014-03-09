<?php

	/**
	* The Interspire Shopping Cart feed generation class, used to generate RSS & Atom feeds.
	*/
	class ISC_FEED_GENERATOR
	{

		public $cacheLifetime = 3600;
		public $_cacheFile = '';
		public $_channel = array();
		public $_items = array();

		public function __construct($feedId, $type="rss", $cacheLifetime=0)
		{
			$this->cacheLifetime = $cacheLifetime;

			if($type == "atom") {
				$this->_generator = new ISC_FEED_GENERATOR_ATOM;
			}
			else {
				$this->_generator = new ISC_FEED_GENERATOR_RSS;
			}

			// Caching support enabled?
			if($this->cacheLifetime > 0) {
				$this->_cacheFile = ISC_BASE_PATH  . '/cache/feeds/feed_' . $type. '_' . $feedId . '.xml';

				// Feed is cached, was cached not too long ago so serve it up
				if(file_exists($this->_cacheFile) && filemtime($this->_cacheFile) > time()-$this->cacheLifetime) {
					$this->_generator->_feed = file_get_contents($this->_cacheFile);
					$this->_generator->OutputFeed();
				}
			}
		}

		public function SetChannel($channel)
		{
			$this->_channel = $channel;
		}

		public function AddItem($item)
		{
			if(!isset($item['date'])) {
				$item['date'] = time();
			}

			$this->_items[] = $item;
		}

		public function OutputFeed()
		{
			$this->GenerateFeed();
			$this->_generator->OutputFeed();
		}

		public function GenerateFeed()
		{
			$feed = $this->_generator->GenerateFeed($this->_channel, $this->_items);

			// Are we caching this feed to a file?
			if($this->_cacheFile) {
				@file_put_contents($this->_cacheFile, $feed);
			}
			return $feed;

		}
	}

	class ISC_FEED_GENERATOR_ATOM
	{
		public $_feed = '';
		public $_encoding = 'UTF-8';

		private $namespaces = array();

		public function GenerateFeed($channel, $items)
		{
			if(!isset($channel['date'])) {
				$channel['date'] = time();
			}
			$channel['date'] = gmdate("Y-m-d\TH:i:s\Z", $channel['date']);
			if(!isset($channel['encoding'])) {
				$channel['encoding'] = "UTF-8";
			}
			$this->_encoding = $channel['encoding'];

			$extraNamespaces = '';
			$namespaceData = '';
			if(!empty($channel['namespaces'])) {
				$this->namespaces = $channel['namespaces'];
				foreach($this->namespaces as $namespace => $nsData) {
					if(!is_array($nsData)) {
						$nsData = array($nsData);
					}
					$extraNamespaces .= " xmlns:".$namespace.'="'.$nsData[0].'"';
					if(!empty($nsData[1])) {
						foreach($nsData[1] as $tag => $data) {
							$namespaceData .= "\t\t\t<".$namespace.":".$tag."><![CDATA[".$this->_sanitizeCData($data)."]]></".$namespace.":".$tag.">\n";
						}
					}
				}
			}

			$this->_feed = sprintf("<?xml version=\"1.0\" encoding=\"%s\"?>\n", $channel['encoding']);
			$this->_feed .= "<feed xmlns=\"http://www.w3.org/2005/Atom\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" ".$extraNamespaces.">\n";
			$this->_feed .= sprintf("\t<title type=\"html\"><![CDATA[%s]]></title>\n", $this->_SanitizeCDATA($channel['title']));
			$this->_feed .= sprintf("\t<subtitle type=\"html\"><![CDATA[%s]]></subtitle>\n", $this->_SanitizeCDATA($channel['description']));
			$this->_feed .= sprintf("\t<link rel=\"self\" href=\"%s\" />\n", $this->_SanitizeAttribute("http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']));
			$this->_feed .= sprintf("\t<id>%s</id>\n", $this->_SanitizeData($channel['link']));
			$this->_feed .= sprintf("\t<updated>%s</updated>\n", $this->_SanitizeData($channel['date']));
			$this->_feed .= $namespaceData;

			foreach($items as $item) {
				$this->_feed .= $this->GenerateItem($item);
			}
			$this->_feed .= "</feed>\n";
			return $this->_feed;
		}

		public function GenerateItem($item)
		{
			$item['date'] = isc_date("Y-m-d\TH:i:s\Z", $item['date']);
			$xml = "\t<entry xmlns=\"http://www.w3.org/2005/Atom\">\n";
			if(isset($item['author'])) {
				$xml .= "\t\t<author>\n";
				$xml .= sprintf("\t\t\t<name>%s</name>", $this->_SanitizeData($item['author']));
				$xml .= "\t\t</author>\n";
			}
			$xml .= sprintf("\t\t<published>%s</published>\n", $this->_SanitizeData($item['date']));

			if(!isset($item['updated'])) {
				$item['updated'] = $item['date'];
			} else {
				$item['updated'] = isc_date("Y-m-d\TH:i:s\Z", $item['updated']);
			}

			$xml .= sprintf("\t\t<updated>%s</updated>\n", $this->_SanitizeData($item['updated']));
			$xml .= sprintf("\t\t<link rel=\"alternate\" type=\"text/html\" href=\"%s\" />\n", $this->_SanitizeAttribute($item['link']));
			$xml .= sprintf("\t\t<id>%s</id>\n", $this->_SanitizeData($item['link']));
			$xml .= sprintf("\t\t<title type=\"html\" xml:space=\"preserve\"><![CDATA[%s]]></title>\n", $this->_SanitizeCDATA($item['title']));
			$xml .= sprintf("\t\t<content type=\"html\" xml:space=\"preserve\" xml:base=\"%s\"><![CDATA[%s]]></content>\n", $this->_SanitizeAttribute($item['link']), $this->_SanitizeCDATA($item['description']));
			$xml .= "\t\t<draft xmlns=\"http://purl.org/atom-blog/ns#\">false</draft>\n";

			if(!empty($item['namespaces'])) {
				foreach($item['namespaces'] as $namespace => $nsData) {
					if(empty($this->namespaces[$namespace])) {
						continue;
					}

					foreach($nsData as $tag => $data) {
						$xml .= "\t\t<".$namespace.":".$tag."><![CDATA[".$this->_sanitizeCData($data)."]]></".$namespace.":".$tag.">\n";
					}
				}
			}

			$xml .= "\t</entry>\n";
			return $xml;
		}

		public function _SanitizeAttribute($string)
		{
			return str_replace(array('"', '>', '<', '&'), array("&quot;", "&gt;", "&lt;", "&amp;"), $string);
		}

		public function _SanitizeData($string)
		{
			return str_replace(array('"', '>', '<', '&'), array("&quot;", "&gt;", "&lt;", "&amp;"), $string);
		}

		public function _SanitizeCDATA($string)
		{
			$string = str_replace("]", "&91;", $string);
			return $string;
		}

		public function OutputFeed()
		{
			$header = sprintf("Content-Type: application/atom+xml; charset=\"%s\"", $this->_encoding);
			header($header);
			echo $this->_feed;
			exit;
		}
	}

	class ISC_FEED_GENERATOR_RSS
	{
		public $_feed = '';
		public $_encoding = 'UTF-8';

		private $namespaces = array();

		public function GenerateFeed($channel, $items)
		{
			if(!isset($channel['date'])) {
				$channel['date'] = time();
			}
			$channel['date'] = gmdate("D, d M Y H:i:s O", $channel['date']);
			if(!isset($channel['encoding'])) {
				$channel['encoding'] = "UTF-8";
			}
			$this->_encoding = $channel['encoding'];

			$extraNamespaces = '';
			$namespaceData = '';
			if(!empty($channel['namespaces'])) {
				$this->namespaces = $channel['namespaces'];
				foreach($this->namespaces as $namespace => $nsData) {
					if(!is_array($nsData)) {
						$nsData = array($nsData);
					}
					$extraNamespaces .= " xmlns:".$namespace.'="'.$nsData[0].'"';
					if(!empty($nsData[1])) {
						foreach($nsData[1] as $tag => $data) {
							$namespaceData .= "\t\t<".$namespace.":".$tag."><![CDATA[".$this->_sanitizeCData($data)."]]></".$namespace.":".$tag.">\n";
						}
					}
				}
			}

			$this->_feed = sprintf("<?xml version=\"1.0\" encoding=\"%s\"?>\n", $channel['encoding']);
			$this->_feed .= "<rss version=\"2.0\" xmlns:content=\"http://purl.org/rss/1.0/modules/content/\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" ".$extraNamespaces.">\n";
			$this->_feed .= "\t<channel>\n";
			$this->_feed .= sprintf("\t\t<title><![CDATA[%s]]></title>\n", $this->_SanitizeCDATA($channel['title']));
			$this->_feed .= sprintf("\t\t<link>%s</link>\n", $this->_SanitizeData($channel['link']));
			$this->_feed .= sprintf("\t\t<description><![CDATA[%s]]></description>\n", $this->_SanitizeCDATA($channel['description']));
			$this->_feed .= sprintf("\t\t<pubDate>%s</pubDate>\n", $this->_SanitizeData($channel['date']));
			$this->_feed .= $namespaceData;

			foreach($items as $item) {
				$this->_feed .= $this->GenerateItem($item);
			}

			$this->_feed .= "\t</channel>\n";
			$this->_feed .= "</rss>\n";
			return $this->_feed;
		}

		public function GenerateItem($item)
		{
			$item['date'] = isc_date("D, d M Y H:i:s O", $item['date']);
			$xml = "\t\t<item>\n";
			$xml .= sprintf("\t\t\t<title><![CDATA[%s]]></title>\n", $this->_SanitizeCDATA($item['title']));
			$xml .= sprintf("\t\t\t<link>%s</link>\n", $this->_SanitizeData($item['link']));
			$xml .= sprintf("\t\t\t<pubDate>%s</pubDate>\n", $item['date']);
			if(isset($item['author'])) {
				$xml .= sprintf("\t\t\t<dc:creator>%s</dc:creator>\n", $this->_SanitizeData($item['author']));
			}
			$xml .= sprintf("\t\t\t<guid isPermaLink=\"false\">%s</guid>\n", $this->_SanitizeData($item['link']));
			$xml .= sprintf("\t\t\t<description><![CDATA[%s]]></description>\n", $this->_SanitizeCDATA($item['description']));
			$xml .= sprintf("\t\t\t<content:encoded><![CDATA[%s]]></content:encoded>\n", $this->_SanitizeCDATA($item['description']));

			if(!empty($item['namespaces'])) {
				foreach($item['namespaces'] as $namespace => $nsData) {
					if(empty($this->namespaces[$namespace])) {
						continue;
					}

					foreach($nsData as $tag => $data) {
						$xml .= "\t\t\t<".$namespace.":".$tag."><![CDATA[".$this->_sanitizeCData($data)."]]></".$namespace.":".$tag.">\n";
					}
				}
			}

			$xml .= "\t\t</item>\n";
			return $xml;
		}

		public function _SanitizeAttribute($string)
		{
			return str_replace(array('"', '>', '<', '&'), array("&quot;", "&gt;", "&lt;", "&amp;"), $string);
		}

		public function _SanitizeData($string)
		{
			return str_replace(array('"', '>', '<', '&'), array("&quot;", "&gt;", "&lt;", "&amp;"), $string);
		}

		public function _SanitizeCDATA($string)
		{
			$string = str_replace("]", "&91;", $string);
			return $string;
		}

		public function OutputFeed()
		{
			$header = sprintf("Content-Type: text/xml; charset=\"%s\"", $this->_encoding);
			header($header);
			echo $this->_feed;
			exit;
		}
	}