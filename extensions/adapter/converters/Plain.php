<?php
/**
 * radium: lithium application framework
 *
 * @copyright     Copyright 2017, Dirk Brünsicke (http://bruensicke.com)
 * @license       http://opensource.org/licenses/BSD-3-Clause The BSD License
 */

namespace radium\extensions\adapter\converters;

use lithium\aop\Filters;

class Plain extends \lithium\core\Object {

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
		$defaults = array('stripHtml' => true);
		$options += $defaults;
		$params = compact('content', 'data', 'options');
		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			if (is_array($params['content'])) {
				return array_map(array(__CLASS__, 'render'), $params['content'], $params['options']);
			}
			if ($params['options']['stripHtml']) {
				return strip_tags($params['content']);
			}
			return $params['content'];
		});
	}

}
?>