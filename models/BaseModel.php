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
 *  class MyModel extends \radium\models\BaseModel {
 * }}}
 *
 * @see app\models
 * @see lithium\data\Model
 */
class BaseModel extends \lithium\data\Model {

	use \radium\models\Base;

	/**
	 * 
	 * TODO: Behavior!!
	 * 
	 * 
	 * 
	 * allows for data-retrieval of entities via file-based access
	 *
	 * In case you want to provide default file-data without inserting them into the database, you
	 * would need to create files based on model-name in a path like that
	 * `{:library}/data/{:class}/{:id}.neon` or `{:library}/data/{:class}/{:slug}.neon`
	 *
	 * In that case, an entity requested by id or slug would be loaded from file instead. Please pay
	 * attention, though that not all options are implemented, such as extended conditions, order,
	 * limit or page. This is meant to enable basic loading of id- or slug-based entity lookups.
	 *
	 * @see radium\util\Neon::file()
	 * @see radium\util\File::load()
	 * @param string $type The find type, which is looked up in `Model::$_finders`. By default it
	 *        accepts `all`, `first`, `list` and `count`,
	 * @param array $options Options for the query. By default, accepts:
	 *        - `conditions`: The conditional query elements, e.g.
	 *                 `'conditions' => array('published' => true)`
	 *        - `fields`: The fields that should be retrieved. When set to `null`, defaults to
	 *             all fields.
	 *        - `order`: The order in which the data will be returned, e.g. `'order' => 'ASC'`.
	 *        - `limit`: The maximum number of records to return.
	 *        - `page`: For pagination of data.
	 * @return mixed
	 */
	public static function find($type, array $options = []) {
		$result = parent::find($type, $options);
		$neon = static::meta('neon');
		if ($neon && (!$result || (!count($result)))) {
			return Neon::find(get_called_class(), $type, $options);
		}
		return $result;
	}

	/**
	 * mass-import datasets
	 *
	 * @param array $data data as array, keyed off by ids and value beeing an array with all values
	 * @param array $options additional options
	 *        - `dry`: make a dry-run of import
	 *        - `prune`: empty collection before import, defaults to false
	 *        - `overwrite`: overwrite existing records, defaults to true
	 *        - `validate`: validate data, before save, defaults to true
	 *        - `strict`: defines if only fields in schema will be imported, defaults to true
	 *        - `callbacks`: enable callbacks in save-method, defaults to false
	 * @return array
	 */
	public static function bulkImport($data, array $options = []) {
		$defaults = [
			'dry' => false,
			'prune' => false,
			'overwrite' => true,
			'validate' => true,
			'strict' => true,
			'callbacks' => false,
		];
		$options += $defaults;
		$result = [];

		if ($options['prune'] && !$options['dry']) {
			static::remove();
		}

		if (!$options['overwrite']) {
			$conditions = ['_id' => array_keys($data)];
			$fields = '_id';
			$present = static::find('all', compact('conditions', 'fields'));

			if($present) {
				$data = array_diff_key($data, $present->data());
				$skipped = array_keys(array_intersect_key($data, $present->data()));
				$result += array_fill_keys($skipped, 'skipped');
			}
		}
		if ($options['overwrite'] && !$options['dry']) {
			static::remove(['_id' => array_keys($data)]);
		}

		$callbacks = $options['callbacks'];
		$whitelist = ($options['strict']) ? static::schema()->names() : null;
		foreach ($data as $key => $item) {
			$entity = static::create();
			$entity->set($item);
			if ($options['validate'] || $options['dry']) {
				$result[$key] = (!$entity->validates())
					? $entity->errors()
					: 'valid';
				if ($result[$key] !== 'valid' || $options['dry']) {
					continue;
				}
			}
			if (!$options['dry']) {
				if ($options['overwrite']) {
					static::remove(['_id' => $key]);
				}
				$result[$key] = ($entity->save(null, compact('whitelist', 'callbacks')))
					? 'saved'
					: 'failed';
			}
		}
		return $result;
	}

	/**
	 * updates fields for multiple records, specified by key => value
	 *
	 * You can update the same field for more than on record with one call, like this:
	 *
	 * {{{
	 *   $data = array(
	 *     'id1' => 1,
	 *     'id2' => 2,
	 *   );
	 *   Model::multiUpdate('order', $data);
	 * }}}
	 *
	 * @param string $field name of field to update
	 * @param array $data array keys are primary keys, values will be set
	 * @param array $options Possible options are:
	 *     - `updated`: set to false to supress automatic updating of the `updated` field
	 * @return array an array containing all results
	 * @filter
	 */
	public static function multiUpdate($field, array $data, array $options = []) {
		$defaults = ['updated' => true];
		$options += $defaults;
		$params = compact('field', 'data', 'options');
		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			extract($params);
			$key = static::key();
			$result = [];
			foreach ($data as $id => $value) {
				$update = [$field => $value];
				if ($options['updated']) {
					$update['updated'] = time();
				}
				$result[$id] = static::update($update, [$key => $id]);
			}
			return $result;
		});
	}

	/**
	 * updates one or more fields per entity
	 *
	 * {{{$entity->updateFields(array('fieldname' => $value));}}}
	 *
	 * @see lithium\data\Model::update()
	 * @param object $entity current instance
	 * @param array $values an array of values to be changed
	 * @param array $options Possible options are:
	 *     - `updated`: set to false to supress automatic updating of the `updated` field
	 * @return true on success, false otherwise
	 * @filter
	 */
	public function updateFields($entity, array $values, array $options = []) {
		$defaults = ['updated' => true];
		$options += $defaults;
		$params = compact('entity', 'values', 'options');
		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			extract($params);
			$key = static::key();
			$conditions = [$key => $entity->id()];
			if ($options['updated']) {
				$values['updated'] = time();
			}
			$success = static::update($values, $conditions);
			if (!$success) {
				$model = $entity->model();
				$msg = sprintf('Update of %s [%s] returned false', $model, $entity->id());
				$data = compact('values', 'conditions', 'model');
				return false;
			}
			$entity->set($values);
			return true;
		});
	}

	/**
	 * fetches the associated configuration record
	 *
	 * If current record has a configuration id set, it will load the corresponding record,
	 * but if it is not set, it will try to load a configuration by slug, with the following
	 * format: `<modelname>.<slug>`.
	 *
	 * @param object $entity current instance
	 * @param string $field what field (in case of array) to return
	 * @param array $options an array of options currently supported are
	 *              - `raw`     : returns Configuration object directly
	 *              - `default` : what to return, if nothing is found
	 *              - `flat`    : to flatten the result, if object/array-ish, defaults to false
	 * @return mixed configuration value
	 */
	public function configuration($entity, $field = null, array $options = []) {
		$defaults = ['raw' => false];
		$options += $defaults;
		$load = (empty($entity->config_id))
			? sprintf('%s.%s', strtolower(static::meta('name')), $entity->slug)
			: $entity->config_id;
		$config = Configurations::load($load);
		if (!$config) {
			return null;
		}
		return ($options['raw']) ? $config : $config->val($field, $options);
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
	 *                 version of the model to reslove.
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