<?php
/**
 * radium: lithium application framework
 *
 * @copyright     Copyright 2017, Dirk Brünsicke (http://bruensicke.com)
 * @license       http://opensource.org/licenses/BSD-3-Clause The BSD License
 */

define('RADIUM_PATH', dirname(__DIR__));

use lithium\core\Libraries;
use radium\util\File;
use Handlebars\Autoloader;

if (!Libraries::get('li3_behaviors')) {
	Libraries::add('li3_behaviors');
}

if (!Libraries::get('Parsedown')) {
	Libraries::add('Parsedown', [
		'path' => RADIUM_PATH . '/libraries/Parsedown',
		'includePath' => true,
		'prefix' => false,
	]);
}

Libraries::paths(File::paths());



require RADIUM_PATH . '/libraries/Handlebars/Autoloader.php';
Autoloader::register();


require __DIR__ . '/bootstrap/media.php';
require __DIR__ . '/bootstrap/validators.php';


// use radium\models\BaseModel;

// if (!BaseModel::finder('random')) {
// 	BaseModel::finder('random', function($self, $params, $chain){
// 		$amount = $self::find('count', $params['options']);
// 		$offset = rand(0, $amount-1);
// 		$params['options']['offset'] = $offset;
// 		return $self::find('first', $params['options']);
// 	});
// }

?>