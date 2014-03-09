<?php
class ISC_ADMIN_EDITOR extends ISC_ADMIN_BASE
{
	/**
	 * Generate a usable textarea (either text or WYSIWYG based on the store setting) for entering content on to a page.
	 *
	 * @param array An array of options (id, value, width, height). If not specified, uses defaults.
	 * @return string The HTML for the generated WYSIWYG editor.
	 */
	public function GetWysiwygEditor($options=array())
	{
		$defaultOptions = array(
			'id' => 'wysiwyg',
			'value' => '',
			'width' => '400px',
			'height' => '300px'
		);

		$options = array_merge($defaultOptions, $options);

		if(!GetConfig('UseWYSIWYG')) {
			return $this->DrawPlainTextEditor($options);
		}
		else {
			return $this->DrawTinyMceEditor($options);
		}
	}

	/**
	 * Generate a plain text textarea for entering content on to a page.
	 *
	 * @param array An array of options (id, value, width, height). If not specified, uses defaults.
	 * @return string The HTML for the generated text editor.
	 */
	public function DrawPlainTextEditor($options)
	{
		$options['value'] = preg_replace("#<br( /?)>#", "\r\n", $options['value']);

		$this->template->Assign('WysiwygId', $options['id']);
		$this->template->Assign('WysiwygValue', isc_html_escape($options['value']));
		$this->template->Assign('WysiwygWidth', $options['width']);
		$this->template->Assign('WysiwygHeight', $options['height']);

		return $this->template->render('Snippets/EditorTextarea.html');
	}

	/**
	 * Generate a TinyMCE based textarea for entering text on to a page.
	 *
	 * @param array An array of options (id, value, width, height). If not specified, uses defaults.
	 * @return string The HTML for the generated WYSIWYG editor.
	 */
	public function DrawTinyMceEditor($options)
	{
		$this->template->Assign('WysiwygId', $options['id']);
		$this->template->Assign('WysiwygValue', isc_html_escape($options['value']));
		$this->template->Assign('WysiwygWidth', $options['width']);
		$this->template->Assign('WysiwygHeight', $options['height']);

		$this->template->Assign('LoadFunctionName', 'LoadEditor_'.$options['id']);

		// Load a custom valid_elements set for TinyMCE
		if(!empty($options['validElementsSet'])) {
			$setName = $options['validElementsSet'];
			$setTemplateName = 'Snippets/EditorTinyMCE.validElements.'.$setName.'.tpl';
			$validElements = $this->template->render($setTemplateName);
			$this->template->Assign('ValidElements', $validElements);
		}

		if(isset($options['delayLoad']) && $options['delayLoad'] == true) {
			$this->template->Assign('LoadFuntion', '');
		}
		else {
			$this->template->Assign('LoadFunction', 'LoadEditor_'.$options['id'].'()');
		}
		$common = $this->template->render('Snippets/EditorTinyMCECommon.html');

		if(isset($options['editorOnly']) && $options['editorOnly'] == true) {
			return $common;
		}

		$this->template->Assign('EditorTinyMCECommon', $common);

		// Check to see if GZIp support can be enabled
		$encodings = array();
		$supportsGzip = false;

		// Check if it supports gzip
		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']))
		$encodings = explode(',', strtolower(preg_replace("/\s+/", "", $_SERVER['HTTP_ACCEPT_ENCODING'])));
		if ((in_array('gzip', $encodings) || in_array('x-gzip', $encodings) || isset($_SERVER['---------------'])) && function_exists('ob_gzhandler') && !ini_get('zlib.output_compression')) {
			if(in_array('x-gzip', $encodings)) {
				$enc = 'x-gzip';
			}
			else {
				$enc = 'gzip';
			}
			$supportsGzip = true;
		}

		if($supportsGzip) {
			return $this->template->render('Snippets/EditorTinyMCEGzip.html');
		}
		else {
			return $this->template->render('Snippets/EditorTinyMCE.html');
		}
	}
}