<?php
/**
 * radium: lithium application framework
 *
 * @copyright     Copyright 2017, Dirk Brünsicke (http://bruensicke.com)
 * @license       http://opensource.org/licenses/BSD-3-Clause The BSD License
 */

namespace radium\util;

use radium\extensions\errors\ConditionsException;
use lithium\aop\Filters;
use lithium\util\Text;
use lithium\util\Set;

class Conditions extends \lithium\core\StaticObject {

	public static function parse($conditions, $data, array $options = array()) {
		$params = compact('conditions', 'data', 'options');
		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			extract($params);
			$defaults = array();
			$options += $defaults;
			$check = Text::insert($conditions, Set::flatten($data));
			if (strpbrk($check, '&|')) {
				return eval("return (boolean)($check);");
			}
			// TODO: take care, that spaces are around operator
			list($value1, $operator, $value2) = explode(" ", $check, 3);
			return static::compare($value1, $operator, $value2);
		});
	}


	/**
	 * allows dynamic checking of fields and values
	 *
	 * @see radium\util\Conditions::parse()
	 * @param scalar $field what field to be checked
	 * @param string $operator operator to be used for check
	 * @param string $value (optional) pass in value to check against, can be appended to operator
	 * @return boolean true on met condition, false otherwise
	 * @filter
	 */
	public static function check($field, $operator, $value = '') {
		$params = compact('field', 'operator', 'value');
		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			extract($params);
			return eval("return (boolean)($field " . $operator . " $value);");
		});
	}

	/**
	 * allows strict comparing 2 fields with a given operator
	 *
	 * @throws radium\extensions\errors\ConditionsException
	 * @param scalar $value1 what field to be checked
	 * @param string $operator operator to be used for check
	 * @param string $value2 pass in value to check against
	 * @return boolean true on met condition, false otherwise
	 * @filter
	 */
	public static function compare($value1, $operator, $value2) {
		$params = compact('value1', 'operator', 'value2');
		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			extract($params);
			switch (trim($operator)) {
				case "=":  return (boolean) ($value1 == $value2);
				case "==": return (boolean) ($value1 == $value2);
				case "!=": return (boolean) ($value1 != $value2);
				case ">=": return (boolean) ($value1 >= $value2);
				case "<=": return (boolean) ($value1 <= $value2);
				case ">":  return (boolean) ($value1 >  $value2);
				case "<":  return (boolean) ($value1 <  $value2);
				default:
					$e = new ConditionsException('invalid operator');
					$e->setData($params);
					throw $e;
			}
			$e = new ConditionsException('operator not found');
			$e->setData($params);
			throw $e;
		});
	}


}

?>