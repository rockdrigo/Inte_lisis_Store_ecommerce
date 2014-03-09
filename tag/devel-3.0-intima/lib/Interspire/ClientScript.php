<?php
class Interspire_ClientScript
{
	protected $scripts=array();

	/**
	 * Registers some javascript code.
	 *
	 * @param string the javascript code
	 * @param string the location where the javascript should be generated. Possible values:
	 * - 'ready': within a jQuery.ready() block
	 * - 'head': in the head tag
	 * - 'start': at the start of a body tag
	 * - 'end': at the end of a body tag
	 */
	public function registerScript($script, $position='ready')
	{
		$this->scripts[$position][] = $script;
	}

	/**
	 * Injects registered scripts into a html document. @see registerScript
	 *
	 * @param string the html document to be modified.
	 */
	public function render(&$output)
	{
		if($html = $this->getHeadHTML())
			$output = preg_replace('/<\\/head\s*>/is',$html.'</head>', $output);

		if($html = $this->getBodyStartHTML())
			$output = preg_replace('/(<body\b[^>]*>)/is','$1'.$html, $output);

		if($html = $this->getBodyEndHTML())
			$output = preg_replace('/<\\/body\s*>/is',$html.'</body>', $output);
	}

	public function getBodyEndHTML()
	{
		if(empty($this->scripts['end']) && empty($this->scripts['ready']))
			return null;

		$html = "<script>\n";

		if(!empty($this->scripts['end'])) {
			$html .= implode("\n", $this->scripts['end'])."\n";
		}

		if(!empty($this->scripts['ready'])) {
			$html .= "jQuery(document).ready(function(){\n";
			$html .= implode("\n", $this->scripts['ready']);
			$html .= "\n});";
		}

		$html .= "\n</script>";

		return $html;
	}

	public function getBodyStartHTML()
	{
		if(empty($this->scripts['start']))
			return null;

		$html = "<script>\n";
		$html .= implode("\n", $this->scripts['start']);
		$html .= "\n</script>";

		return $html;
	}

	public function getHeadHTML()
	{
		if(empty($this->scripts['head']))
			return null;

		$html = "<script>\n";
		$html .= implode("\n", $this->scripts['head']);
		$html .= "\n</script>";

		return $html;
	}
}
