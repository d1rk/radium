<?php
/**
 * radium: lithium application framework
 *
 * @copyright     Copyright 2017, Dirk Brünsicke (http://bruensicke.com)
 * @license       http://opensource.org/licenses/BSD-3-Clause The BSD License
 */

namespace radium\extensions\adapter\converters;

use lithium\aop\Filters;
use li3_mustache\libraries\Mustache as Renderer;

class Mustache extends \lithium\core\Object {

	/**
	 * returns rendered content
	 *
	 * @param string $content input content
	 * @param array $data additional data to be passed into render context
	 * @param array $options an array with additional options
	 * @return string content as given
	 * @filter
	 */
	public function get($content, $data = array(), array $options = array()) {
		$defaults = array('allowed' => true);
		$options += $defaults;
		$params = compact('content', 'data', 'options');
		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			$renderer = new Renderer($params['content'], $params['data']);
			return $renderer->render();
		});
	}

}
?>