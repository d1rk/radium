<?php
/**
 * radium: lithium application framework
 *
 * @copyright     Copyright 2018, Dirk Brünsicke (http://bruensicke.com)
 * @license       http://opensource.org/licenses/BSD-3-Clause The BSD License
 */

namespace radium\extensions\data\behavior;

use li3_behaviors\data\model\Behavior;
use lithium\analysis\Logger;
use lithium\aop\Filters;
use lithium\util\Set;

class Revisionable extends Behavior {

	/**
	 * default configuration for this behavior.
	 * 
	 * @see li3_behaviors\data\model\Behavior::_config()
	 * @var array
	 */
	protected static $_defaults = [
		'timestamps' => true,
		'revisions' => true,
		// 'revisions' => function($entity, $options, $behavior){ /* ... */ return true; },
		'class' => '\radium\models\Revisions',  // has to implement add() and restore()
		'fields' => [
			'revision' => 'revision_id',
			'created' => 'created',
			'updated' => 'updated',
		],
	];

	/**
	 * Applies filters on $model. Automatically called during initialization
	 * of behavior and model.
	 *
	 * @see lithium\core\StaticObject::applyFilter()
	 * @param string $model Class name of the model.
	 * @param object $behavior Instance of the behavior.
	 */
	protected static function _filters($model, Behavior $behavior) {
		Filters::apply($model, 'save', function($params, $next) use ($behavior) {
			$entity = $params['entity'];
			$options = $params['options'];
			$config = $behavior->config();

			if ($params['data']) {
				$entity->set($params['data']);
			}
			$updated = static::_updatedData($entity);

			$mode = ($entity->exists()) ? 'updated' : 'created';
			if ($config['timestamps'] && !empty($config['fields'][$mode])) {
				$field = $config['fields'][$mode];
				$entity->set([$field => time()]);
			}

			$result = $next(['entity' => $entity, 'data' => null, 'options' => $options]);
	
			if ($result && static::_revisionRequired($entity, $options, $behavior)) {

				$class = $config['class'];
				$revision_id = $class::add($entity, $updated);

				if (!$revision_id) {
					Logger::debug("Tried to save Revision, but $class::add failed");
					return $result;
				}

				$field = $config['fields']['revision'];
				$model = $entity->model();
				$foreign_id = (string) $entity->{$model::key()};

				return $entity->save([$field => $revision_id], ['callbacks' => false]);
			}
			return $result;
		});
	}

	/**
	 * Evaluates if a Revision needs to be saved, or not
	 * 
	 * If the revisions configuration value is a closure, it runs it, feeding it
	 * with the exact same params it receives and expects a return value, that
	 * can be evaluated as boolean: If the response is true, it will create a
	 * revision, while it will omit to do to so, if false is returned.
	 *
	 * @param object $entity current instance
	 * @param array $options save options parameters
	 * @param object $behavior Instance of the behavior.
	 * @return array returns data, without contained entity objects, 
	 */
	protected static function _revisionRequired($entity, array $options = [], Behavior $behavior) {
		$config = $behavior->config();

		if (is_callable($config['revisions'])) {
			return $config['revisions']($entity, $options, $behavior);
		}
		return (bool) $config['revisions'];
	}

	/**
	 * Generates a diff of data that has been updated on this entity
	 * 
	 * This is beeing used to generate an array of modified fields within an entity
	 * before save. This allows the Revisions storage to be directly informed
	 * about the changes, that happened to a record.
	 * 
	 * @param object $entity current instance
	 * @return array returns modified data of entity
	 */
	protected static function _updatedData($entity) {
		$export = $entity->export();
		return Set::diff(static::cleanData($export['update']), static::cleanData($export['data']));
	}

	/**
	 * This methods filters nested Entity-data into an array-only structure
	 *
	 * @param array $data passed in data
	 * @return array returns data, without contained entity objects, 
	 *    but their data as array instead.
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

	/**
	 * Add finders to $model. Automatically called during initialization
	 * of behavior and model.
	 *
	 * @see lithium\data\Model::finder()
	 * @param string $model Class name of the model.
	 * @param object $behavior Instance of the behavior.
	 */
	protected static function _finders($model, Behavior $behavior) {
		$model::finder('revisions', function() {
			// TODO: Versions::find();
		});
	}

}