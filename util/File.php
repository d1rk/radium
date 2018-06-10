<?php
/**
 * radium: lithium application framework
 *
 * @copyright     Copyright 2017, Dirk Brünsicke (http://bruensicke.com)
 * @license       http://opensource.org/licenses/BSD-3-Clause The BSD License
 */

namespace radium\util;

use radium\data\Converter;

use lithium\aop\Filters;
use lithium\core\Libraries;
use lithium\util\Set;
use lithium\util\Collection;
use lithium\data\collection\DocumentSet;



class File {

	/**
	 * Contains a cascading list of search path templates, indexed by base object type.
	 *
	 * Used by `File::locate()` to perform data-file location. This allows new types of
	 * objects (i.e. json, neon, markdown data sources) to be automatically
	 * 'discovered' when you register a new vendor library or plugin (using `Libraries::add()`).
	 *
	 * Because paths are checked in the order in which they appear, path templates should be
	 * specified from most-specific to least-specific. See the `locate()` method for usage examples.
	 *
	 * @see lithium\core\Libraries::locate()
	 * @see lithium\core\Libraries::paths()
	 * @var array
	 */
	protected static $_paths = [
		'neon' => [
			'{:library}\neon\{:class}\{:name}.neon',
			'{:library}\data\{:class}\{:name}.neon',
		],
		'json' => [
			'{:library}\json\{:class}\{:name}.json',
			'{:library}\data\{:class}\{:name}.json',
		],
		'md' => [
			'{:library}\md\{:class}\{:name}.md',
			'{:library}\docs\{:class}\{:name}.md',
		],
	];

	/**
	 * reads content from `$file` and converts its content with `Converter::get()`
	 *
	 * This method understands library-relative paths. If `$file` starts with a
	 * valid (read: loaded) library name followed by a slash, e.g.
	 * `radium/some-path/file.ext` it will find that file on its own within the
	 * file-system. This method automatically detects and preprends the base-path
	 * for that library.
	 *
	 * @see radium\data\Converter::get()
	 * @param string|array $file filenames to load (and convert)
	 * @param array $data additional data to be put into `Converter::get()`
	 * @param array $options This method accepts three options, as followed. 
	 *        Hint: Options array is also passed into `Converter::get()`, if
	 *        `convert` is set to true (default)
	 *        Common options accepted are:
	 *        - `'convert'` _bool_: If content of loaded file should be run through
	 *           `Converter::get()` method, to decode its content directly into
	 *           the target format.
	 *        - `'type'` _string_: Defines the format of the content in `$file`, 
	 *           which is then used for `Converter::get()`, defaults to
	 *           extension of requested `$file`.
	 *        - `'default'` _mixed_: If no file is found, this will be returned
	 *           instead, allowing you to easily define a fallback value.
	 * @return mixed
	 * @filter Allows to execute logic before loading (e.g. database lookups)
	 *         or after i.e. for caching results.
	 */
	public static function load($file, $data = [], array $options = []) {
		$defaults = [
			'collect' => false,
			'convert' => true,
			'type' => static::extension($file),
			'default' => false,
		];
		$options += $defaults;
		$params = compact('file', 'data', 'options');

		if (is_string($file)) {
			return static::_load($file, $data, $options);
		}

		$result = [];
		foreach ((array) $file as $item) {
			$result[$item] = static::_load($item, $data, $options);
		}
		return ($options['collect'])
			? new Collection(['data' => $result])
			: $result;
	}

	/**
	 * Locate files of a specific type within libraries.
	 * 
	 * This method allows find a list of files with path, relative to their
	 * corresponding libraries, they exist in. Each of these files can be
	 * loaded (and converted) by `File::load()`.
	 * 
	 * ```
	 * //Examples of useful scopes:
	 * $scope = `radium\models\Contents`
	 * $scope = 'contents';
	 * $scope = 'radium';
	 * $scope = 'app';
	 * ```
	 *
	 * @param string|array $types what kind of files to locate, i.e. extension
	 *           of file to locate, whin given paths.
	 * @param string $scope The find scope, can be anything as string of type
	 *           `$library`, `$model` or a specific folder name, i.e. a certain
	 *           type of data-files. If given a `$library` (e.g. `radium`) it returns
	 *           all neon files that are below that library, according to `paths`
	 *           If given a fully namespaced model class `$model`
	 *           (e.g. `radium\models\Contents`) a check is performed, if a
	 *           method named `meta('source')` can be called, as this returns
	 *           the name of the source of a given model, therefore defining
	 *           the sub-folder to look for; in our example `contents`.
	 *           If given a string (e.g. `contents`), the name of the subfolder
	 *           is given directly, therefore defining a specific type of neon
	 *           files to look for. This can be used for neon-files of a certain
	 *           type that do not match a model, e.g. `nav`.
	 * @param array $options Options for the query. By default, accepts:
	 *        - `collect`: Wrap list of files in a `Collection` class, or returns
	 *           just a plain array with a list of filenames, defaults to true.
	 *        - `paths`: additional search paths to look for in 
	 *        - `load`: If set to true, every found file`s content is being
	 *           loaded using `Neon::file()`, which automatically converts the
	 *           content of that file from neon-format to its result.
	 *           Hint: Should only be used with care! This could potentially
	 *           load a lot of information and can significantly raise
	 *           memory consumption, File IO and Runtime of the script.
	 * @return mixed
	 */
	public static function locate($types, $scope = null, array $options = []) {
		$defaults = ['collect' => true, 'load' => false];
		$options += $defaults;

		if (is_string($types)) {
			return static::_locate($types, $scope, $options);
		}

		$files = [];
		$collect = $options['collect'];
		$options['collect'] = false;
		foreach ((array) $types as $type) {
			$files += static::_locate($type, $scope, $options);
		}
		return ($collect)
			? new Collection(['data' => $files])
			: $files;
	}

	/**
	 * returns file-extension for given file
	 *
	 * @param string $file filename
	 * @return string extension of given file
	 */
	public static function extension($file) {
		return pathinfo($file, PATHINFO_EXTENSION);
	}

	/**
	 * returns base filename for given file without path or extension
	 *
	 * @param string $file filename
	 * @return string extension of given file
	 */
	public static function name($file) {
		return pathinfo($file, PATHINFO_FILENAME);
	}

	/**
	 * Accessor method for the data-files path templates which `File` uses to
	 * look up and load data-files. Using this method, you can define your own
	 * types of data-files, or modify the default organization of built-in 
	 * data-file types.
	 *
	 * Note, however, that this is a destructive, not an additive operation
	 * and will replace any existing paths defined for that type. If you wish
	 * to add a search path for an existing type, you must do the following:
	 * ```
	 * $existing = File::paths('neon');
	 * File::paths(['neon' => array_merge(
	 * 	['{:library}\neon-data\{:name}'], (array) $existing
	 * )]);
	 * ```
	 * See `Libraries::locate()` for more information on using built-in and
	 * user-defined paths to look up classes, or data-files respectively.
	 *
	 * @see radium\util\File::locate()
	 * @see lithium\core\Libraries::locate()
	 * @see lithium\core\Libraries::$_paths
	 * @param mixed $path If `$path` is a string, returns the path(s) associated with that path
	 *              type, or `null` if no paths are defined for that type.
	 * @return mixed
	 */
	public static function paths($path = null) {
		if (empty($path)) {
			return static::$_paths;
		}
		if (is_string($path)) {
			return isset(static::$_paths[$path]) ? static::$_paths[$path] : null;
		}
		static::$_paths = array_filter(array_merge(static::$_paths, (array) $path));
	}

	/**
	 * Helper function for returning known paths given a certain type.
	 *
	 * @see lithium\core\Libraries::$_paths
	 * @param string $type Path type (specified in `File::$_paths`).
	 * @param string $scope The find scope, can be anything as string of type
	 *           `$library`, `$model` or a specific folder name, i.e. a certain
	 *           type of data-files. If given a `$library` (e.g. `radium`) it returns
	 *           all neon files that are below that library, according to `paths`
	 *           If given a fully namespaced model class `$model`
	 *           (e.g. `radium\models\Contents`) a check is performed, if a
	 *           method named `meta('source')` can be called, as this returns
	 *           the name of the source of a given model, therefore defining
	 *           the sub-folder to look for; in our example `contents`.
	 *           If given a string (e.g. `contents`), the name of the subfolder
	 *           is given directly, therefore defining a specific type of neon
	 *           files to look for. This can be used for neon-files of a certain
	 *           type that do not match a model, e.g. `nav`.
	 * @param array $options Options for the query. By default, accepts:
	 *        - `collect`: Wrap list of files in a `Collection` class, or returns
	 *           just a plain array with a list of filenames, defaults to true.
	 *        - `paths`: additional search paths to look for in 
	 *        - `load`: If set to true, every found file`s content is being
	 *           loaded using `Neon::file()`, which automatically converts the
	 *           content of that file from neon-format to its result.
	 *           Hint: Should only be used with care! This could potentially
	 *           load a lot of information and can significantly raise
	 *           memory consumption, File IO and Runtime of the script.
	 * @return mixed
	 */
	protected static function _locate($type, $scope = null, array $options = []) {
		$defaults = ['collect' => true, 'load' => false];
		$options += $defaults;

		$files = Libraries::locate($type, null, ['namespaces' => true]);
		$files = array_map(function($file) use ($type) {
			return str_replace('\\', '/', $file).$type;
		}, $files);

		if (is_null($scope)) {
			return ($options['collect'])
				? new Collection(['data' => $files])
				: $files;
		}

		$source = is_callable([$scope, 'meta']) ? $scope::meta('source') : strtolower($scope);

		$filter = function($file) use ($source) {
			return (bool) preg_match(sprintf('#(^%s/|\/%s/)#', $source, $source), $file);
		};
		$files = array_values(array_filter($files, $filter));

		if (!$options['load']) {
			return ($options['collect'])
				? new Collection(['data' => $files])
				: $files;
		}

		$result = [];
		foreach ($files as $file) {
			$result[$file] = static::load($file);
		}
		return ($options['collect'])
			? new Collection(['data' => $result])
			: $result;
	}


	/**
	 * reads content from `$file` and converts its content with `Converter::get()`
	 *
	 * This method understands library-relative paths. If `$file` starts with a
	 * valid (read: loaded) library name followed by a slash, e.g.
	 * `radium/some-path/file.ext` it will find that file on its own within the
	 * file-system. This method automatically detects and preprends the base-path
	 * for that library.
	 *
	 * @see radium\data\Converter::get()
	 * @param string $file filename to retrieve contents from
	 * @param array $data additional data to be put into `Converter::get()`
	 * @param array $options This method accepts three options, as followed. 
	 *        Hint: Options array is also passed into `Converter::get()`, if
	 *        `convert` is set to true (default)
	 *        Common options accepted are:
	 *        - `'convert'` _bool_: If content of loaded file should be run through
	 *           `Converter::get()` method, to decode its content directly into
	 *           the target format.
	 *        - `'type'` _string_: Defines the format of the content in `$file`, 
	 *           which is then used for `Converter::get()`, defaults to
	 *           extension of requested `$file`.
	 *        - `'default'` _mixed_: If no file is found, this will be returned
	 *           instead. Allowing you to easily define a fallback value.
	 * @return mixed
	 * @filter Allows to execute logic before loading (e.g. database lookups)
	 *         or after i.e. for caching results.
	 */
	public static function _load($file, $data = [], array $options = []) {
		$defaults = [
			'convert' => true,
			'type' => static::extension($file),
			'default' => false,
		];
		$options += $defaults;
		$params = compact('file', 'data', 'options');

		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			$options = $params['options'];

			if (file_exists($params['file'])) {
				$content = file_get_contents($params['file']);
				return ($options['convert'])
					? Converter::get($options['type'], $content, $params['data'], $options)
					: $content;
			}

			[$library, $filename] = explode('/', $params['file'], 2);
			if (!$libraryPath = Libraries::get($library, 'path')) {
				return $options['default'];
			}

			$file = sprintf('%s/%s', $libraryPath, $filename);
			if (!file_exists($file)) {
				return $options['default'];
			}

			$content = file_get_contents($file);
			return ($options['convert'])
				? Converter::get($options['type'], $content, $params['data'], $options)
				: $content;
		});
	}

}