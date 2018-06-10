<?php
/**
 * radium: lithium application framework
 *
 * @copyright     Copyright 2017, Dirk Brünsicke (http://bruensicke.com)
 * @license       http://opensource.org/licenses/BSD-3-Clause The BSD License
 */

namespace radium\util;

use radium\util\File;

use lithium\core\Libraries;
use lithium\util\Collection;
use lithium\data\collection\DocumentSet;

use Nette\Neon\Neon as NeonRenderer;

class Neon {

	/**
	 * encodes given $input to neon format syntax
	 *
	 * @see radium\util\Neon::renderer()
	 * @param mixed $input content that will be converted into neon format
	 * @param array $options additional options to be passed into `encode()` method
	 * @return string the neon markup that represents given `$input`
	 */
	public static function encode($input, $options = null) {
		return NeonRenderer::encode($input, $options);
	}

	/**
	 * decodes given $input from neon format into native php structure
	 *
	 * That will be most likely an array or a string.
	 *
	 * @see radium\util\Neon::renderer()
	 * @param string $input neon marku that will be converted into native php format
	 * @return mixed generated php structure derived from given `$input`
	 */
	public static function decode($content) {
		return NeonRenderer::decode($content);
	}

	/**
	 * Locate files of a specific type within libraries.
	 * 
	 * This method allows find a list of files with path, relative to their
	 * corresponding libraries, they exist in. Each of these files can be
	 * loaded (and converted) by `File::load()`.
	 * 
	 * @see radium\util\File::locate()
	 * @param string $scope The find scope, can be anything as string of type
	 *           `$library`, `$model` or a specific folder name, i.e. a certain
	 *           type of data-files. If given a `$library` (e.g. `radium`) it returns
	 *           all data files that are below that library, according to `paths`
	 *           If given a fully namespaced model class `$model`
	 *           (e.g. `radium\models\Contents`) a check is performed, if a
	 *           method named `meta('source')` can be called, as this returns
	 *           the name of the `source` of a given model, therefore defining
	 *           the sub-folder to look for; in our example `contents`.
	 *           If given a string (e.g. `contents`), the name of the subfolder
	 *           is given directly, therefore defining a specific type of data
	 *           files to look for. This can be used for data-files of a certain
	 *           type that do not match a model, e.g. `nav`.
	 * @param array $options Options for the query. By default, accepts:
	 *        - `collect`: Wrap list of files in a `Collection` class, or returns
	 *           just a plain array with a list of filenames, defaults to true.
	 *        - `paths`: additional search paths to look for in 
	 *        - `load`: If set to true, every found file`s content is being
	 *           loaded using `File::load()`, which automatically converts the
	 *           content of that file from its data-format to its decoded result.
	 *           Hint: Should only be used with care! This could potentially
	 *           load a lot of information and can significantly raise
	 *           memory consumption, File IO and Runtime of the script.
	 * @return mixed
	 */
	public static function locate($scope = null, array $options = []) {
		return File::locate('neon', $scope, $options);
	}

	/**
	 * Loads a given file, parses it with neon and returns the result
	 *
	 * This method is able to understand library-relative paths. If filename starts with a valid
	 * library name followed by a slash, i.e. `radium/some-path/file.ext` it will find that file
	 * on its own within the file-system. It automatically detects the base-path for that library
	 * and will find the file within that library.
	 *
	 * @see radium\util\File::load()
	 * @param string $file full path to file or a library-related path, starting with `$library/`
	 * @param string $field only return that field from loaded file or null if not present
	 * @return mixed generated php structure derived from neon-parsed file
	 */
	public static function file($file, $field = null) {
		return File::load($file, $field);
	}

	/**
	 * Loads entities and records from file-based structure
	 * 
	 * 
	 * 
	 * Neon::file('devices.neon');
	 * Neon::find('li3_airy\models\Devices', 'all', ['id' => 'foo']);
	 * Neon::files('li3_airy\models\Devices');
	 *
	 * 
	 * 
	 * Trys to implement a similar method to load datasets not from database, but rather from
	 * a file that holds all relevant model data and is laid into the filesystem of a library.
	 * This allows for easy default-data to be loaded without using a database as backend.
	 *
	 * Put your files into `{:library}\data\{:class}\{:name}.neon` and let the content be found
	 * with loading just the id or slug of that file.
	 *
	 * Attention: This feature is considered experimental and should be used with care. It might
	 *            not work as expected. Also, not all features are implemented.
	 *
	 * If nothing is found, it just returns null or an empty array to ensure a falsey value.
	 *
	 * @param string|array $model fully namespaced model class, e.g. `radium\models\Contents`
	 *                     can also be an array, in which case `key` and `source` must be given
	 *                     according to the internal structure of `Model::meta()`.
	 * @param string $type The find type, which is looked up in `Model::$_finders`. By default it
	 *        accepts `all`, `first`, `list` and `count`. Later two are not implement, yet.
	 * @param array $options Options for the query. By default, accepts:
	 *        - `conditions`: The conditional query elements, e.g.
	 *                 `'conditions' => array('published' => true)`
	 *        - `fields`: The fields that should be retrieved. When set to `null`, defaults to
	 *             all fields.
	 *        - `order`: The order in which the data will be returned, e.g. `'order' => 'ASC'`.
	 *        - `limit`: The maximum number of records to return.
	 *        - `page`: For pagination of data.
	 * @return mixed returns null or an empty array if nothing is found
	 *               If `$type` is `first` returns null or the correct entity with given data
	 *               If `$type` is `all` returns null or a DocumentSet object with loaded entities
	 */
	public static function find($model, $type, array $options = []) {
		$defaults = ['conditions' => null, 'fields' => null];
		$options += $defaults;



		$files = static::locate($model);
// var_dump($files);
// var_dump($files);exit;	

		foreach ($files as $file) {
			// $content = static::file($file);
			$content = File::load($file, null, ['type' => 'json']);
			var_dump($content);
		}

		
		var_dump($files);exit;
		if ($files->count() == 0) {
			return false;
		}

		$meta = is_callable([$model, 'meta']) ? $model::meta() : $model;
		var_dump($model);

/*
	RE-USE Filters foreach
		$filters = $options['filters'];
		$pattern = sprintf('#\/%s/#', $source);
		$filters[] = function($file) use ($pattern) {
			return (bool) preg_match($pattern, $file);
		};
		
		foreach ($filters as $filter) {
			$files = array_filter($files, $filter);
		}
*/



		extract($options);
		if (isset($conditions['slug'])) {
			$field = 'slug';
		}
		if (isset($conditions[$meta['key']])) {
			$field = $meta['key'];
		}
		if (!isset($field)) {
			// $field = 'all';
			// var_dump($field);
			// return array();
		}
		$value = $conditions[$field];
		var_dump($value, $conditions, $field);
		exit;

		switch (true) {
			case is_string($value):
				$pattern = sprintf('/%s/', $value);
				break;
			case isset($value['like']):
				$pattern = $value['like'];
				break;
		}

		if (isset($pattern)) {
			$filter = function($file) use ($pattern) {
				return (bool) preg_match($pattern, $file);
			};
		}
		if (isset($filter)) {
			$files = $files->find($filter);
		}
		if (isset($order)) {
			// TODO: add sort
		}

		if ($type == 'count') {
			return count($files);
		}

		if ($type == 'list') {
			// TODO: implement me
		}

		if ($type == 'first' && count($files)) {
			$data = self::file($files->first());
			$data[$field] = $value;
			if ($model === 'radium\models\Configurations') {
				$data['value'] = Neon::encode($data['value']);
			}
			return $model::create($data);
		}

		// we found one (!) file with name 'all.neon', this is the entire set
		if ($type == 'all' && count($files) == 1 && (substr($files->first(), -strlen('all.neon')) === 'all.neon')) {
			$rows = self::file($files->first());
			$data = [];
			foreach($rows as $row) {
				$data[] = $model::create($row);
			}
			if (is_array($model)) {
				return new Collection(compact('data'));
			}
			$model = $meta['class'];
			return new DocumentSet(compact('data', 'model'));
		}

		if ($type == 'all' && count($files)) {
			$data = [];
			foreach ($files as $file) {
				$current = self::file($file);

				if (is_array($model)) {
					$filename = File::name($file);
					$data[$filename] = $current;
					continue;
				}
				if ($model === 'radium\models\Configurations') {
					$current['value'] = Neon::encode($current['value']);
				}
				$data[] = $model::create($current);
			}
			if (is_array($model)) {
				return new Collection(compact('data'));
			}
			$model = $meta['class'];
			return new DocumentSet(compact('data', 'model'));
		}
		return false;
	}



}

?>