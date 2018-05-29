<?php
/**
 * radium: lithium application framework
 *
 * @copyright     Copyright 2018, Dirk Brünsicke (http://bruensicke.com)
 * @license       http://opensource.org/licenses/BSD-3-Clause The BSD License
 */

namespace radium\extensions\data\behavior;

use li3_behaviors\data\model\Behavior;
use lithium\aop\Filters;

class Softdeletable extends Behavior {

	/**
	 * default configuration for this behavior.
	 *
	 * @see li3_behaviors\data\model\Behavior::_config()
	 * @var array
	 */
	protected static $_defaults = [
		'field' => 'deleted',
	];

	/**
	 * returns if current record is marked as deleted
	 *
	 * @param string $model Class name of the model.
	 * @param object $behavior Instance of the behavior.
	 * @param object $entity current instance
	 * @return boolean true if record is deleted, false otherwise
	 */
	public function deleted($model, Behavior $behavior, $entity) {
		$field = $behavior->config('field');
		return (bool) is_null($entity->$field);
	}

	/**
	 * undeletes a record, in case it was marked as deleted
	 *
	 * @param string $model Class name of the model.
	 * @param object $behavior Instance of the behavior.
	 * @param object $entity current instance
	 * @return boolean true on success, false otherwise
	 */
	public function undelete($model, Behavior $behavior, $entity) {
		$field = $behavior->config('field');
		unset($entity->$field);
		return is_null($entity->$field) && $entity->save();
	}

	/**
	 * Applies filters on $model. Automatically called during initialization
	 * of behavior and model.
	 * 
	 * This adds the Filter to the Model::delete() method, resulting in
	 * changing its behavior in a way, that instead of physically removing
	 * this record from the database it will be marked as deleted. This is
	 * done with setting a timestamp on a field, as defined in `$_defaults`
	 * or Behaviors `config('field')` respectively.
	 *
	 * @see lithium\core\StaticObject::applyFilter()
	 * @param string $model Class name of the model.
	 * @param object $behavior Instance of the behavior.
	 */
	protected static function _filters($model, Behavior $behavior) {
		Filters::apply($model, 'delete', function($params, $next) use ($behavior) {
			$params['options'] += ['force' => false];

			$field = $behavior->config('field');
			$deleted = $params['entity']->schema($field);

			if (is_null($deleted) || $params['options']['force']) {
				unset($params['options']['force']);
				return $next($params);
			}
			$params['entity']->set([$field => time()]);
			return $params['entity']->save();
		});
	}

	/**
	 * Allows for dyamically adding instance methods to the model. The
	 * methods to be added must be returned as an array, where the key
	 * is the name of the concrete method on the model and the value
	 * an anonymous function.
	 * 
	 * In this case, we add a method called `deleted` or whatever the
	 * field is called, to support asking, if this record is marked
	 * as deleted.
	 *
	 * @param string $model Class name of the model.
	 * @param object $behavior Instance of the behavior.
	 * @return array Methods to be added to the model instance.
	 */
	protected static function _methods($model, Behavior $behavior) {
		$field = $behavior->config('field');
		return [
			$field => function($entity) use ($field) {
				return (bool) is_null($entity->$field);
			}
		];
	}

	/**
	 * Add finders to $model. Automatically called during initialization
	 * of behavior and model.
	 * 
	 * In this case, we added a finder, called `deleted` and according to
	 * the configuration, whatever that field is called. This finder allows
	 * easier retrieval of deleted records, like that:
	 * 
	 * ```
	 * Model::find('deleted'); // returns all deleted records
	 * ```
	 *
	 * @see lithium\data\Model::finder()
	 * @param string $model Class name of the model.
	 * @param object $behavior Instance of the behavior.
	 */
	protected static function _finders($model, Behavior $behavior) {
		$field = $behavior->config('field');
		$query = ['conditions' => [$field => ['>=' => 1]]];
		$model::finder('deleted', $query);
		$model::finder($field, $query);
	}

}