<?php
/**
 * radium: lithium application framework
 *
 * @copyright     Copyright 2017, Dirk Brünsicke (http://bruensicke.com)
 * @license       http://opensource.org/licenses/BSD-3-Clause The BSD License
 */

use lithium\util\Inflector;
use lithium\util\Validator;
use radium\media\Mime;

/*
 * We want to avoid method names like `statuses()` - therefore, we go this route
 */
Inflector::rules('uninflected', 'status');

/*
 * apply new validation rules to the Validator class, because we need them
 */
Validator::add(array(
	'sha1' => '/^[A-Fa-f0-9]{40}$/',
	'slug' => '/^[a-z0-9\_\-\.]*$/',			// only lowercase, digits and dot
	'loose_slug' => '/^[a-zA-Z0-9\_\-\.]*$/',	// both cases, digits and dot
	'strict_slug' => '/^[a-z][a-z0-9\_\-]*$/',  // only lowercase, starting with letter, no dot
	'isUnique' => function ($value, $format, $options) {
		$conditions = array($options['field'] => $value);
		foreach ((array) $options['model']::meta('key') as $field) {
			if (!empty($options['values'][$field])) {
				$conditions[$field] = array('!=' => $options['values'][$field]);
			}
		}
		$fields = $options['field'];
		$result = $options['model']::find('first', compact('fields', 'conditions'));
		return (bool) empty($result);
	},
	'status' => function ($value, $format, $options) {
		return (bool) $options['model']::status($value);
	},
	'type' => function ($value, $format, $options) {
		return (bool) $options['model']::types($value);
	},
	'md5' => function ($value, $format, $options) {
		return (bool) (strlen($value) === 32 && ctype_xdigit($value));
	},
	'attachmentType' => function($value, $type, $data) {
		if (!isset($data['attachment'])) {
			return true;
		}
		$mime = $data['attachment']['type'];
		$mimeTypes = Mime::types();
		foreach ($data['types'] as $each) {
			if (isset($mimeTypes[$each]) && in_array($mime, $mimeTypes[$each])) {
				return true;
			}
		}
		return false;
	},
	'attachmentSize' => function($value, $type, $data) {
		if (!isset($data['attachment'])) {
			return true;
		}
		$size = $data['attachment']['size'];
		if (is_string($data['size'])) {
			if (preg_match('/([0-9\.]+) ?([a-z]*)/i', $data['size'], $matches)) {
				$number = $matches[1];
				$suffix = $matches[2];
				$suffixes = array(""=> 0, "Bytes"=>0, "KB"=>1, "MB"=>2, "GB"=>3, "TB"=>4, "PB"=>5);
				if (isset($suffixes[$suffix])) {
					$data['size'] = round($number * pow(1024, $suffixes[$suffix]));
				}
			}
		}
		return $data['size'] >= $size;
	},
));
