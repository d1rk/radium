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
	 * generic method to retrieve a list or an entry of an array of a static
	 * property or a configuration with given properties list
	 *
	 * This method is used to allow an easy addition of key/value pairs mainly
	 * for usage as a selectable list in a dropdown for a specific model.
	 *
	 * If you want to provide a list of available options, declare a property as
	 * an array like `$_status`, in which case $property would be 'status':
	 * 
	 * '''
	 *  public static $_status = [
	 *    'active' => 'active',
	 *    'inactive' => 'inactive',
	 *  ];
	 * '''
	 * 
	 * In addition to that propterty a Configuration would be used for lookin up
	 * valid values for that list. The slug of that Configuration should match
	 * the following pattern: `{static::meta('sources')}.$property`
	 * For example a Configuration with slug `contents.status` would be used for
	 * a list of values with property name `status` on the Model `Contents`.
	 * 
	 * @see radium\models\BaseModel::types()
	 * @see radium\models\BaseModel::status()
	 * @see radium\models\Configurations::get()
	 * @param string $property name of public static property to retrieve value
	 *               from, automatically prepended by an underscore: `_`.
	 * @param string $type type to look for (optional) which returns the value
	 *               of the requested key.
	 * @param array $options Options for this lookup:
	 *        - `configuration`: Control, if lookups via Configurations should
	 *          be used, or not. Defaults to true.
	 * @return mixed all types with keys and their name
	 *               or value of `$type`, if given.
	 */
	public static function _group($property, $type = null, array $options = []) {
		$defaults = ['configuration' => true];
		$options += $defaults;

		$field = sprintf('_%s', $property);
		$slug = sprintf('%s.%s', static::meta('source'), $property);
		if (!empty($type)) {
			$var =& static::$$field;
			$default = (isset($var[$type])) ? $var[$type] : false;
		} else {
			$default = static::$$field;
		}
		return ($options['configuration'])
			? Configurations::get($slug, $default, ['field' => $type])
			: $default;
	}

}