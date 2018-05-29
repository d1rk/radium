<?php
/**
 * radium: lithium application framework
 *
 * @copyright     Copyright 2017, Dirk Brünsicke (http://bruensicke.com)
 * @license       http://opensource.org/licenses/BSD-3-Clause The BSD License
 */

namespace radium\models;

use lithium\aop\Filters;
use lithium\util\Set;
use radium\data\Converter;

class Revisions extends \radium\models\DataModel {

	/**
	 * Custom type options
	 *
	 * @var array
	 */
	public static $_status = [
		'active' => 'Active',
		'outdated' => 'Outdated',
		'review' => 'Review',
		'approved' => 'Approved',
	];

	/**
	 * Custom type options
	 *
	 * @var array
	 */
	public static $_types = [
		'auto' => 'Automatic Snapshot',
		'manual' => 'Manually saved Snapshot'
	];

	/**
	 * Stores the data schema.
	 *
	 * @see lithium\data\source\MongoDb::$_schema
	 * @var array
	 */
	protected $_schema = [
		'_id' => ['type' => 'id'],

		'name' => ['type' => 'string', 'default' => '', 'null' => false],
		'type' => ['type' => 'string', 'default' => 'auto'],
		'status' => ['type' => 'string', 'default' => 'active', 'null' => false],
		'notes' => ['type' => 'string', 'default' => '', 'null' => false],

		'model' => ['type' => 'string', 'default' => '', 'null' => false],
		'foreign_id' => ['type' => 'string', 'default' => '', 'null' => false],
		'data' => ['type' => 'string'],
		'modified' => ['type' => 'object'],
		'approved' => ['type' => 'datetime'],

		'created' => ['type' => 'datetime', 'default' => '', 'null' => false],
		'updated' => ['type' => 'datetime'],
		'deleted' => ['type' => 'datetime'],
	];

	/**
	 * Validation rules
	 *
	 * @var array
	 */
	public $validates = [
		'_id' => [
			['notEmpty', 'message' => 'a unique _id is required.', 'last' => true, 'on' => 'update'],
		],
		'name' => [
			['notEmpty', 'message' => 'a name is required.', 'last' => true],
		],
		'type' => [
			['notEmpty', 'message' => 'Please specify a type'],
			['type', 'message' => 'Type is unknown. Please adjust.'],
		],
		'status' => [
			['notEmpty', 'message' => 'Please specify a status'],
			['status', 'message' => 'Status is unknown. Please adjust.'],
		],

		'model' => [
			['notEmpty', 'message' => 'a model is required.'],
		],
		'foreign_id' => [
			['notEmpty', 'message' => 'a foreign id is required.'],
		],
		'data' => [
			['notEmpty', 'message' => 'data is required.'],
		],
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
			'edit' => ['icon' => 'pencil2', 'class' => ''],
			'restore' => ['icon' => 'spinner7', 'class' => 'hover-success', 'data-confirm' => 'Do you  want to restore to this revision?'],
		],
		'all' => [
			'import' => ['icon' => 'upload'],
			'export' => ['icon' => 'download'],
		]
	];

	/**
	 * Default query parameters.
	 *
	 * @var array
	 */
	protected $_query = [
		'limit' => 200,
		'order' => [
			'created' => 'DESC',
		],
	];

	/**
	 * Returns list of available Versions for a given model.
	 *
	 * @param string $model full-namespaced class-name to search for Versions
	 * @param string $id optional, to only show Versions for objects with this `$id`
	 * @param array $options additional find-options
	 * @return object A Collection object of all found Versions
	 */
	public static function available($model, $id = null, array $options = []) {
		$conditions = compact('model');
		if (!empty($id)) {
			$key = $model::meta('key');
			$conditions[$key] = $id;
		}
		$options['conditions'] = $conditions;
		$versions = static::find('all', $options);
		return $versions;
	}


	/**
	 * This method generates a new revision.
	 *
	 * It creates a duplication of the object, to allow restoring. It marks all prior
	 * versions as `outdated` and the new one as `active`.
	 *
	 * @param object $entity the instance, that needs to created a new version for
	 * @param array $modified The modified data of this record as difference to prior revision
	 * @param array $options additional options
	 * @filter
	 */
	public static function add($entity, array $modified = [], array $options = []) {
		$defaults = ['force' => false];
		$options += $defaults;
		$params = compact('entity', 'modified', 'options');
		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			$entity = $params['entity'];
			$modified = $params['modified'];
			$options = $params['options'];
			$model = $entity->model();

			if ($model == 'radium\models\Versions') {
				return false;
			}
			$key = $model::meta('key');
			$foreign_id = (string) $entity->$key;

			if (empty($modified)) {
				if (!$options['force']) {
					return false;
				}
				$modified = $entity->data();
			}

			static::update(['status' => 'outdated'], compact('model', 'foreign_id'));

			$data = [
				'model' => $model,
				'foreign_id' => $foreign_id,
				'status' => 'active',
				'name' => (string) $entity->title(),
				'modified' => $modified,
				'data' => json_encode($entity->data()),
				'created' => time(),
			];

			$version = static::create($data);
			if (!$version->save(null, ['validate' => false])) {
				return false;
			}
			return $version->id();
		});
	}

	/**
	 * Restores a version from history and updates the corresponding record with stored data.
	 *
	 * All versions will be marked as `outdated` with the new version becoming `active`.
	 *
	 * @see radium\models\Versions::add()
	 * @param string $id Id of version to restore
	 * @param array $options additional options to be passed into $model::save()
	 * @return true on success, false otherwise
	 * @filter
	 */
	public static function restore($id, array $options = []) {
		$defaults = ['validate' => false, 'callbacks' => false];
		$options += $defaults;
		$params = compact('id', 'options');
		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) use ($defaults) {
			extract($params);
			$version = static::first($id);
			if (!$version) {
				return false;
			}
			$model = $version->model;
			$foreign_id = $version->foreign_id;
			$data = json_decode($version->data, true);
			$data['version_id'] = $version->id();

			$entity = $model::first($foreign_id);
			if (!$entity) {
				$entity = $model::create($data);
			}

			if(!$entity->save($data, $options)) {
				return false;
			}

			static::update(['status' => 'outdated'], compact('model', 'foreign_id'));
			return $version->save(['status' => 'active'], $defaults);
		});
	}

	/**
	 * only use data of objects, in case they are contained within data
	 *
	 * @param array $data passed in data
	 * @return array returns data, without continaing objects
	 */
	public static function cleanData(array $data = []) {
		foreach($data as $key => $item) {
			if (is_array($item)) {
				$data[$key] = static::cleanData($item);
			}
			if ($item instanceof \lithium\data\Entity) {
				$data[$key] = $item->data();
			}
		}
		return $data;
	}

}

?>