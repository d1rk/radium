<?php
/**
 * radium: lithium application framework
 *
 * @copyright     Copyright 2017, Dirk Brünsicke (http://bruensicke.com)
 * @license       http://opensource.org/licenses/BSD-3-Clause The BSD License
 */

namespace radium\tests\mocks\data;

use lithium\data\collection\DocumentSet;
use lithium\data\entity\Document;

class MockConfigurations extends \radium\models\Configurations {

	protected $_meta = array(
		'connection' => false
	);

	public static function find($type = 'all', array $options = array()) {
		$now = date('Y-m-d h:i:s');

		switch ($type) {
			case 'count':
				return 3;
			case 'first':
				return new Document(array('data' => array(
					'_id' => 1,
					'name' => 'foo',
					'slug' => 'foo',
					'status' => 'active',
					'created' => $now,
					'modified' => $now
				)));
				break;
			case 'all':
			default :
				return new DocumentSet(array('data' => array(
					array(
						'_id' => 1,
						'name' => 'first',
						'slug' => 'first',
						'status' => 'active',
						'created' => $now,
						'modified' => $now
					),
					array(
						'_id' => 2,
						'name' => 'second',
						'slug' => 'second',
						'status' => 'inactive',
						'created' => $now,
						'modified' => $now
					),
					array(
						'_id' => 3,
						'name' => 'third',
						'slug' => 'third',
						'status' => 'active',
						'created' => $now,
						'modified' => $now
					)
				)));
				break;
		}
	}
}

?>