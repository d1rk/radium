<?php
/**
 * radium: lithium application framework
 *
 * @copyright     Copyright 2017, Dirk Brünsicke (http://bruensicke.com)
 * @license       http://opensource.org/licenses/BSD-3-Clause The BSD License
 */

namespace radium\models;

trait Base {

	/**
	 * returns primary id as string from current entity
	 *
	 * @param object $entity instance of current Record
	 * @return string primary id of current record
	 */
	public function id($entity) {
		return (string) $entity->{static::key()};
	}

	/**
	 * Returns all schema-fields, without their types
	 *
	 * @return array
	 */
	public static function fields() {
		$schema = static::schema();
		return $schema->names();
	}

	/**
	 * generic method to retrieve a list or an entry of an array of a static property or a
	 * configuration with given properties list
	 *
	 * This method is used to allow an easy addition of key/value pairs, mainly for usage
	 * in a dropdown for a specific model.
	 *
	 * If you want to provide a list of available options, declare your properties in the same
	 * manner as `$_types` or `$_status` or create a new configuration with a slug that follows
	 * this structure: `{static::meta('sources')}.$property` (e.g. `content.types`).
	 * This array is used, then.
	 *
	 * @see radium\models\BaseModel::types()
	 * @see radium\models\BaseModel::status()
	 * @param string $property name of property to look for.
	 *               automatically prepended by an underscore: `_`. Must be static and public
	 * @param string $type type to look for, optional
	 * @return mixed all types with keys and their name, or value of `$type` if given
	 */
	public static function _group($property, $type = null) {
		$field = sprintf('_%s', $property);
		$slug = sprintf('%s.%s', static::meta('source'), $property);
		if (!empty($type)) {
			$var =& static::$$field;
			$default = (isset($var[$type])) ? $var[$type] : false;
		} else {
			$default = static::$$field;
		}
		return Configurations::get($slug, $default, ['field' => $type]);
	}

}