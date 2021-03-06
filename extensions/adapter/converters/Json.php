<?php
/**
 * radium: lithium application framework
 *
 * @copyright     Copyright 2017, Dirk Brünsicke (http://bruensicke.com)
 * @license       http://opensource.org/licenses/BSD-3-Clause The BSD License
 */

namespace radium\extensions\adapter\converters;

use radium\util\Json as JsonFormat;
use radium\extensions\errors\JsonException;
use Exception;

use lithium\aop\Filters;
use lithium\util\Set;

class Json extends \lithium\core\Object {

	/**
	 * returns rendered content
	 *
	 * @param string $content input content
	 * @param string $data field to retrieve from configuration
	 * @param array $options an array with additional options
	 * @return string content as given
	 * @filter
	 */
	public function get($content, $data = null, array $options = array()) {
		$defaults = array('assoc' => true, 'depth' => 512, 'default' => array(), 'flat' => false, 'clean' => false);
		$options += $defaults;
		$params = compact('content', 'data', 'options');
		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			extract($params);
			try {
				if ($options['clean']) {
					$content = str_replace(array("\n","\r"),"\\n", $content);
					$content = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/','$1"$3":', $content);
					$content = preg_replace('/(,)\s*}$/','}', $content);
				}
				$config = JsonFormat::decode($content, $options['assoc'], $options['depth']);
			} catch(JsonException $e) {
				return $options['default'];
			} catch(Exception $e) {
				return $options['default'];
			}
			if (empty($data)) {
				return ($options['flat'])
					? Set::flatten($config)
					: $config;
			}
			if (is_scalar($data) && isset($config[$data])) {
				return $config[$data];
			}
			$data = '/'.str_replace('.', '/', (string) $data).'/.';
			$result = current(Set::extract((array) $config, $data));
			if (!empty($result)) {
				return $result;
			}
			return $options['default'];
		});
	}

}
?>