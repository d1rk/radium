<?php
/**
 * radium: lithium application framework
 *
 * @copyright     Copyright 2017, Dirk Brünsicke (http://bruensicke.com)
 * @license       http://opensource.org/licenses/BSD-3-Clause The BSD License
 */

namespace radium\data;

use lithium\aop\Filters;

class Converter extends \lithium\core\Adaptable {

	/**
	 * holds configuration per adapter
	 *
	 * @var array
	 */
	protected static $_configurations = [
		'array' => [
			'adapter' => 'Ini',
		],
		'ini' => [
			'adapter' => 'Ini',
		],
		'import' => [
			'adapter' => 'Json',
		],
		'json' => [
			'adapter' => 'Json',
		],
		'neon' => [
			'adapter' => 'Neon',
		],
		'plain' => [
			'adapter' => 'Plain',
		],
		'html' => [
			'adapter' => 'Html',
		],
		'mustache' => [
			'adapter' => 'Mustache',
		],
		'handlebars' => [
			'adapter' => 'Handlebars',
		],
		'markdown' => [
			'adapter' => 'Markdown',
		],
	];

	/**
	 * Libraries::locate() compatible path to adapters for this class.
	 *
	 * @see lithium\core\Libraries::locate()
	 * @var string Dot-delimited path.
	 */
	protected static $_adapters = 'adapter.converters';

	/**
	 * renders content with given configuration/adapter using data
	 *
	 * @param string $name The name of the `Parser` configuration
	 * @param string $content content that needs to be rendered
	 * @param array $data additional data to be passed into render context
	 * @param array $options Additional options to be forwarded into Adapters render method.
	 * @return string the rendered content
	 * @filter
	 */
	public static function get($name, $content = null, $data = [], array $options = []) {
		$params = compact('name', 'content', 'data', 'options');
		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			extract($params);
			return static::adapter($name)->get($content, $data, $options);
		});
	}

	/**
	 * A stub method called by `_config()` which allows `Adaptable` subclasses to automatically
	 * assign or auto-generate additional configuration data, once a configuration is first
	 * accessed. This allows configuration data to be lazy-loaded from adapters or other data
	 * sources.
	 *
	 * @param string $name The name of the configuration which is being accessed. This is the key
	 *               name containing the specific set of configuration passed into `config()`.
	 * @param array $config Contains the configuration assigned to `$name`. If this configuration is
	 *              segregated by environment, then this will contain the configuration for the
	 *              current environment.
	 * @return array Returns the final array of settings for the given named configuration.
	 */
	protected static function _initConfig($name, $config) {
		$defaults = ['adapter' => ucwords($name), 'filters' => []];
		return (array) $config + $defaults;
	}

}

?>