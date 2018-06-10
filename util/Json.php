<?php
/**
 * radium: lithium application framework
 *
 * @copyright     Copyright 2017, Dirk Brünsicke (http://bruensicke.com)
 * @license       http://opensource.org/licenses/BSD-3-Clause The BSD License
 */

namespace radium\util;

use radium\extensions\errors\JsonException;

class Json {

	/**
	 * JSON error codes
	 *
	 * @see http://php.net/json_last_error
	 * @var array
	 */
	public static $errors = array(
		JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
		JSON_ERROR_STATE_MISMATCH => 'State mismatch',
		JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
		JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
		JSON_ERROR_UTF8 => 'Encoding error occured'
	);

	/**
	 * encodes an object or array to json
	 *
	 * @param array|object $obj the object or array to be encoded
	 * @return string the generated json
	 */
	public static function encode($obj) {
		return json_encode($obj);
	}

	/**
	 * decodes a json string into an array or object
	 *
	 * @throws radium\extensions\errors\JsonException
	 * @param string $json the json string to be decoded
	 * @param boolean $assoc if false, returns an object instead of an array
	 * @param integer $depth how many nested objects/arrays to be returned
	 * @return array|object the decoded object or array
	 */
	public static function decode($json, $assoc = true, $depth = 512) {
		if (empty($json)) {
			return array();
		}
		$result = json_decode($json, $assoc, $depth);
		$errorCode = json_last_error();
		if ($errorCode == JSON_ERROR_NONE) {
			return $result;
		}
		$msg = (isset(static::$errors[$errorCode]))
			? static::$errors[$errorCode]
			: 'Unknown error occured';
		$e = new JsonException(sprintf('JSON Error: %s', $msg));
		$e->setData($json);
		throw $e;
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
		return File::locate('json', $scope, $options);
	}

	/**
	 * parses a given file and its content with neon parser and returns its data structure
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

}

?>