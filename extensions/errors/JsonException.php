<?php
/**
 * radium: lithium application framework
 *
 * @copyright     Copyright 2017, Dirk Brünsicke (http://bruensicke.com)
 * @license       http://opensource.org/licenses/BSD-3-Clause The BSD License
 */

namespace radium\extensions\errors;

/**
 * This exception covers the usage of attempting to handle data conversion from or to JSON
 *
 * @see radium\util\Json
 */
class JsonException extends BaseException {}

?>