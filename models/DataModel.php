<?php
/**
 * radium: lithium application framework
 *
 * @copyright     Copyright 2017, Dirk Brünsicke (http://bruensicke.com)
 * @license       http://opensource.org/licenses/BSD-3-Clause The BSD License
 */

namespace radium\models;

use lithium\aop\Filters;

use radium\models\Configurations;
use radium\util\Neon;
use radium\util\IniFormat;

use lithium\core\Libraries;
use lithium\core\Environment;
use lithium\util\Set;
use lithium\util\Text;
use lithium\util\Validator;
use lithium\util\Inflector;

/**
 * Base class for all Models
 *
 * If you have models in your app, you should extend this class like that:
 *
 * {{{
 *  class MyModel extends \radium\models\DataModel {
 * }}}
 *
 * @see app\models
 * @see lithium\data\Model
 */
class DataModel extends \radium\models\BaseModel {

	use \radium\models\Base;

	/**
	 * Custom status options
	 *
	 * @var array
	 */
	public static $_status = [
		'active' => 'active',
		'inactive' => 'inactive',
	];

	/**
	 * Custom type options
	 *
	 * @var array
	 */
	public static $_types = [
		'default' => 'default',
	];

	/**
	 * Custom actions available on this object
	 *
	 * @var array
	 */
	protected static $_actions = [
		'first' => [
			'delete' => ['icon' => 'remove', 'class' => 'hover-danger', 'data-confirm' => 'Do you really want to delete this record?'],
			'export' => ['icon' => 'download'],
			'duplicate' => ['name' => 'clone', 'icon' => 'copy', 'class' => 'hover-primary'],
			'edit' => ['icon' => 'pencil2', 'class' => 'primary'],
		],
		'all' => [
			'import' => ['icon' => 'upload'],
			'export' => ['icon' => 'download'],
			'add' => ['name' => 'create', 'icon' => 'plus', 'class' => 'primary'],
		]
	];

	/**
	 * Stores the Models data schema.
	 *
	 * @see lithium\data\source\MongoDb::$_schema
	 * @var array
	 */
	protected $_schema = [
		'_id' => ['type' => 'id'],
		'config_id' => ['type' => 'configuration', 'null' => true],
		'name' => ['type' => 'string', 'default' => '', 'null' => false],
		'slug' => ['type' => 'string', 'default' => '', 'null' => false],
		'type' => ['type' => 'string', 'default' => 'default'],
		'status' => ['type' => 'string', 'default' => 'active', 'null' => false],
		'notes' => ['type' => 'string', 'default' => '', 'null' => false],
		'created' => ['type' => 'datetime', 'default' => '', 'null' => false],
		'updated' => ['type' => 'datetime'],
		'deleted' => ['type' => 'datetime'],
	];

	/**
	 * Criteria for data validation.
	 *
	 * @see lithium\data\Model::$validates
	 * @see lithium\util\Validator::check()
	 * @var array
	 */
	public $validates = [
		'_id' => [
			['notEmpty', 'message' => 'a unique _id is required.', 'last' => true, 'on' => 'update'],
		],
		'name' => [
			['notEmpty', 'message' => 'a name is required.', 'last' => true],
		],
		'slug' => [
			['slug', 'message' => 'only numbers, small letters and . - _ are allowed.', 'last' => true],
		],
		'type' => [
			['notEmpty', 'message' => 'Please specify a type'],
			['type', 'message' => 'Type is unknown. Please adjust.'],
		],
		'status' => [
			['notEmpty', 'message' => 'Please specify a status'],
			['status', 'message' => 'Status is unknown. Please adjust.'],
		],
	];

	/**
	 * If this contains an array, the containing fields ar rendered as tabs in add/edit forms.
	 *
	 * 	$_renderLayout = array(
	 * 		'Tab1' => array(
	 * 			'field1',
	 * 			'field2',
	 * 			'field3'
	 * 		),
	 *		'Tab2' => array(
	 *			'field4',
	 * 			'field5'
	 *		),
	 * 	);
	 *
	 * @var array
	 */
	public static $_renderLayout = [];

	/**
	 * Custom find query properties, indexed by name.
	 *
	 * @var array
	 */
	public $_finders = [
		// 'deleted' => [
		// 	'conditions' => [
		// 		'deleted' => ['>=' => 1],
		// 	]
		// ]
	];

	/**
	 * Default query parameters.
	 *
	 * @var array
	 */
	protected $_query = [
		'order' => [
			'slug' => 'ASC',
			'name' => 'ASC',
			'updated' => 'DESC',
			'created' => 'DESC',
		],
		'conditions' => [
			// 'deleted' => null,
		],
	];


	/**
	 * Specifies all meta-information for this model class,
	 * overwritten to enable versions by default.
	 *
	 * @see lithium\data\Connections::add()
	 * @var array
	 */
	protected $_meta = [
		// 'versions' => true,
		'neon' => false,
	];

	protected static $_rss = [
		'title' => 'name',
		'description' => 'notes',
		'link' => 'http://{:host}/{:controller}/view/{:_id}',
		'guid' => '{:controller}/view/{:_id}',
	];

	/**
	 * automatically adds timestamps on saving.
	 *
	 * In case of creation it correctly fills the `created` field with a unix timestamp.
	 * Same holds true for `updated` on updates, accordingly.
	 *
	 * @see lithium\data\Model
	 * @param object $entity current instance
	 * @param array $data Any data that should be assigned to the record before it is saved.
	 * @param array $options additional options
	 * @return boolean true on success, false otherwise
	 * @filter
	 */
	public function save2($entity, $data = [], array $options = []) {
		if (!empty($data)) {
			$entity->set($data);
		}
		$schema = $entity->schema();
		foreach ($schema->fields() as $name => $meta) {
			if (isset($meta['type']) && $meta['type'] != 'list') {
				continue;
			}
			if(is_string($entity->$name)) {
				$listData = explode("\n", $entity->$name);
				array_walk($listData, function (&$val) { $val = trim($val); });
				$entity->$name = $listData;
			}
		}


		$versions = static::meta('versions');
		if (!isset($options['callbacks']) || $options['callbacks'] !== false) {
			$field = ($entity->exists()) ? 'updated' : 'created';
			$entity->set([$field => time()]);


			if (($versions === true) || (is_callable($versions) && $versions($entity, $options))) {
				$version_id = Versions::add($entity, $options);
				if ($version_id) {
					$entity->set(compact('version_id'));
				}
			}
		}
		$result = parent::save($entity, null, $options);
		if ($result && isset($field) && $field == 'created') {

			if (($versions === true) || (is_callable($versions) && $versions($entity, $options))) {
				$version_id = Versions::add($entity, ['force' => true]);
				if ($version_id) {
					$entity->set(compact('version_id'));
					return $entity->save(null, ['callbacks' => false]);
				}
			}
		}
		return $result;
	}

	/**
	 * all types for current model
	 *
	 * @param string $type type to look for
	 * @return mixed all types with keys and their name, or value of `$type` if given
	 */
	public static function types($type = null) {
		return static::_group(__FUNCTION__, $type);
	}

	/**
	 * render layout for current model
	 *
	 * @param string $name ...
	 * @return mixed renderLayouts with keys and their name, or value of `$name` if given
	 */
	public static function renderLayout($name = null) {
		return static::_group(__FUNCTION__, $name);
	}

	/**
	 * all status for current model
	 *
	 * @param string $status status to look for
	 * @return mixed all status with keys and their name, or value of `$status` if given
	 */
	public static function status($status = null) {
		return static::_group(__FUNCTION__, $status);
	}

	/**
	 * all actions available for current model
	 *
	 * @see radium\extensions\helper\Scaffold::actions()
	 * @param string $type type to look for, i.e. `first` or `all`
	 * @return mixed all actions with their corresponding configuration, suitable for Scaffold->actions()
	 */
	public static function actions($type = null) {
		return static::_group(__FUNCTION__, $type);
	}

	/**
	 * finds and loads entities that match slug subpattern
	 *
	 * @see lithium\data\Model::find()
	 * @param string $slug short unique string to look for
	 * @param string $status status must have
	 * @param array $options additional options to be merged into find options
	 * @return object|boolean found results as collection or false, if none found
	 * @filter
	 */
	public static function search($slug, $status = 'active', array $options = []) {
		$params = compact('slug', 'status', 'options');
		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			extract($params);
			$options['conditions'] = [
				'slug' => ['like' => "/$slug/i"],
				'status' => $status,
				'deleted' => null, // only not deleted
			];
			return static::find('all', $options);
		});
	}

	/**
	 * finds and loads active entity for given id
	 *
	 * @param string $id id of entity to load
	 * @param string|array $status expected status of record, can be string or an array of strings
	 * @param array $options additional Options to be used for the query
	 *        - `key`: the field to use for lookup, if given `id` is not a valid mongo-id
	 *                   defaults to `slug`
	 * @return object|boolean entity if found and active, false otherwise
	 * @filter
	 */
	public static function load($id, $status = 'active', array $options = []) {
		$params = compact('id', 'status', 'options');
		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			extract($params);
			$defaults = ['key' => 'slug'];
			$options += $defaults;

			$key = ((strlen($id) == 24) && (ctype_xdigit($id)))
				? $self::key()
				: $options['key'];

			$options['conditions'] = ($key == $options['key'])
				? [$key => $id, 'status' => $status, 'deleted' => null]
				: [$key => $id];

			$options['order'] = ($key == $options['key'])
				? ['updated' => 'DESC']
				: null;

			unset($options['key']);
			$result = static::find('first', $options);
			if (!$result) {
				return false;
			}
			if (!in_array($result->status, (array) $status)) {
				return false;
			}
			if (!empty($result->deleted)) {
				return false;
			}
			return $result;
		});
	}

	/**
	 * fetches associated records
	 *
	 * {{{
	 *   $post->resolve('user'); // returns user, as defined in $post->user_id
	 * }}}
	 *
	 * @param object $entity current instance
	 * @param string|array $fields name of model to load
	 * @param array $options an array of options currently supported are
	 *              - `resolver` : closure that takes $name as parameter and returns full qualified
	 *                 model name.
	 *              - `slug` : true or false. If set to true, model is resolving by slug, not by ID.
	 *                 The slug has to be saved in a document schema key, named by the singular
	 *                 version of the model to resolve.
	 * @return array foreign object data
	 */
	public function resolve($entity, $fields = null, array $options = []) {
		$resolver = function($name) {
			$modelname = Inflector::pluralize(Inflector::classify($name));
			return Libraries::locate('models', $modelname);
		};
		$slug = false;
		$defaults = compact('resolver', 'slug');
		$options += $defaults;

		switch (true) {
			case is_string($fields) && $options['slug']:
				$fields = [$fields];
				break;
			case is_array($fields) && $options['slug']:
				break;
			case is_string($fields):
				$fields = [(stristr($fields, '_id')) ? $fields : "{$fields}_id"];
				break;
			case is_array($fields):
				$fields = array_map(function($field){
					return (stristr($field, '_id')) ? $field : "{$field}_id";
				}, $fields);
				break;
			case empty($fields):
				$fields = self::fields();
				break;
		}

		$result = [];
		foreach ($fields as $field) {
			if (!$options['slug']) {
				if (!preg_match('/^(.+)_id$/', $field, $matches)) {
					continue;
				}
				list($attribute, $name) = $matches;
			} else {
				$attribute = $field;
				$name = $field;
			}
			$model = $options['resolver']($name);
			if (empty($model)) {
				continue;
			}
			$foreign_id = (string) $entity->$attribute;
			if (!$foreign_id) {
				continue;
			}
			$result[$name] = $model::load($foreign_id);
		}
		return (count($fields) > 1) ? $result : array_shift($result);
	}

	/**
	 * returns a properly processed item as rss-item
	 *
	 * @param object $entity instance of current Record
	 * @param array $fields an array of additional fields to generate
	 * @param array $options an array of additional options
	 *              - `merge`: set to false, to process only given fields
	 * @return array an array containing relevant rss data as keys and their corresponding values
	 */
	public function rssItem($entity, $fields = [], array $options = []) {
		$defaults = ['merge' => true];
		$options += $defaults;
		static::$_rss['pubDate'] = function($object) {
			return date('D, d M Y g:i:s O', $object->created->sec);
		};
		$fields = ($options['merge']) ? array_merge(static::$_rss, $fields) : $fields;

		$item = [];
		foreach ($fields as $field => $source) {
			switch(true) {
				case is_callable($source):
					$item[$field] = $source($entity);
					break;
				case stristr($source, '{:'):
					$replace = array_merge(
						Environment::get('scaffold'),
						Set::flatten($entity->data()),
						[
							'host' => $_SERVER['HTTP_HOST'],
						]
					);
					$item[$field] = Text::insert($source, $replace);
					break;
				case isset($entity->$source):
					$item[$field] = $entity->$source;
					break;
			}
		}
		return $item;
	}

	/**
	 * return entity data, filtered by top-level keys
	 *
	 * return only subset of data, that is requested, as in $keys or as fallback taken from
	 * a static property of the corresponding model, named `$_publicFields`.
	 *
	 * @todo allow filtering with sub-keys, i.e. parent.sub
	 * @param object $entity instance of current Record
	 * @param string $key an array with all keys to be preserved, everything else is removed
	 * @return array only data, that is left after filtering everything, that is not in $keys
	 */
	public function publicData($entity, $keys = []) {
		$keys = (empty($keys) && isset(static::$_publicFields))
			? static::$_publicFields
			: (array) $keys;
		$data = $entity->data();
		foreach ($data as $key => $item) {
			if (!in_array($key, $keys)) {
				unset($data[$key]);
			}
		}
		return $data;
	}

	/**
	 * counts distinct values regarding a specific field
	 *
	 * @param string $field name of the field to count distinct values against
	 * @param array $options an array of additional options
	 *              - `group`: set to $field, overwrite here
	 *              - `fields`: what fields to retrieve, useful if you overwrite the reduce code
	 *              - `initial`: initial object to aggregate data in, defaults to StdObject
	 *              - `reduce`: reduce method to be used within mongodb, must be of type `MongoCode`
	 * @return array an array containing relevant rss data as keys and their corresponding values
	 */
	public static function distinctCount($field = 'type', $options = []) {
		$defaults = [
			'group' => $field,
			'fields' => ['_id', $field],
			'initial' => new \stdClass,
			'reduce' => new \MongoCode(
				"function(doc, prev) { ".
					"if(typeof(prev[doc.$field]) == 'undefined') {".
						"prev[doc.$field] = 0;".
					"}".
					"prev[doc.$field] += 1;".
				"}"
			),
		];
		$options += $defaults;

		$method = Inflector::pluralize($field);
		$result = (method_exists(__CLASS__, $method))
			? array_fill_keys(array_keys(static::$method()), 0)
			: [];

		$res = static::find('all', $options);
		if (!$res) {
			return $result;
		}

		$keys = $res->map(function($item) use ($field) {
			return $item->$field;
		});
		$values = $res->map(function($item) use ($field) {
			return $item->{$item->$field};
		});
		return array_merge($result, array_combine($keys->data(), $values->data()));
	}

	/**
	 * allows easy output of IniFormat into a property
	 *
	 * @param object $entity instance of current Record
	 * @param string $field name of property to retrieve data for
	 * @return array an empty array in case of errors or the saved data decoded
	 * @filter
	 */
	public function _ini($entity, $field) {
		$params = compact('entity', 'field');
		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			extract($params);
			if (empty($entity->$field)) {
				return [];
			}
			$data = IniFormat::parse($entity->$field);
			if (!is_array($data)) {
				return [];
			}
			return $data;
		});
	}

	/**
	 * Exports an array of custom finders which use the filter system to wrap around `find()`.
	 *
	 * @return void
	 */
	protected static function _findFilters() {
		$self = static::_object();
		$_query = $self->_query;

		$default = parent::_findFilters();
		$custom = [
			'list' => function($self, $params, $chain) {
				$result = [];
				$meta = $self::meta();
				$name = $meta['key'];

				$options = &$params['options'];
				if (isset($options['field'])) {
					$options['fields'] = (array) $options['field'];
				}
				if ($options['fields'] === null || empty($options['fields'])) {
					list($name, $value) = [$self::meta('key'), null];
				} elseif (count($options['fields']) > 2) {
					list($name, $value) = array_slice($options['fields'], 0, 2);
				} elseif (count($options['fields']) > 1) {
					list($name, $value) = array_slice($options['fields'], 0, 2);
				} elseif (count($options['fields']) == 1) {
					$name = $meta['key'];
					$value = is_array($options['fields'])
						? $options['fields'][0]
						: $options['fields'];
				}
				foreach ($chain->next($self, $params, $chain) as $entity) {
					$key = $entity->{$name};
					$key = is_scalar($key) ? $key : (string) $key;
					$result[$key] = (is_null($value))
						? $entity->title()
						: $entity->{$value};
				}
				return $result;
			},
			'random' => function($self, $params, $chain){
				$amount = (int) $self::find('count', $params['options']);
				$offset = rand(0, $amount-1);
				$params['options']['offset'] = $offset;
				return $self::find('first', $params['options']);
			}
		];
		return array_merge($default, $custom);
	}

}

?>