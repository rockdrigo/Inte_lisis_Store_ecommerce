<?php
/**
 * Interspire_StateExecutor
 *
 * Derived from Wei Zhuo's php State Executor.
 *
 * Copyright (c) 2010 Wei Zhuo <weizhuo[at]gmail[dot]com>
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */

class Interspire_StateExecutor {
	public $actor;

	public function __construct($actor)
	{
		$this->actor = $actor;
	}

	public function execute($transitions)
	{
		$current = current(array_keys($transitions));

		do
		{
			$next = $transitions[$current];
			if(is_array($next)) {
				$found = false;
				foreach($next as $transition) {
					if(is_array($transition)) {
						if($this->actor->{$transition['on']}() === true) {
							$next = $transition['next'];
							$found = true;
							break;
						}
					}
					else
					{
						$next = $transition;
						$found = true;
						break;
					}
				}
				if(!$found)
					throw new Exception('No valid next state: '.isc_json_encode($current));
			}

			if($next !== null)
				$this->actor->{$next}();
			$current = $next;
		}
		while($next !== null);
	}
}
