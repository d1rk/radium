<?php
/**
 * radium: lithium application framework
 *
 * @copyright     Copyright 2017, Dirk Brünsicke (http://bruensicke.com)
 * @license       http://opensource.org/licenses/BSD-3-Clause The BSD License
 */

use lithium\aop\Filters;
use lithium\action\Dispatcher;
use lithium\action\Response;
use lithium\core\Environment;
use lithium\net\http\Media;
use lithium\util\Set;
use lithium\util\Text;
use Handlebars\Autoloader;

Media::type('default', null, array(
	'view' => 'lithium\template\View',
	'paths' => array(
		'template' => array(
			LITHIUM_APP_PATH . '/views/{:controller}/{:template}.{:type}.php',
			RADIUM_PATH . '/views/{:controller}/{:template}.{:type}.php',
			'{:library}/views/{:controller}/{:template}.{:type}.php',
		),
		'layout' => array(
			LITHIUM_APP_PATH . '/views/layouts/{:layout}.{:type}.php',
			RADIUM_PATH . '/views/layouts/{:layout}.{:type}.php',
			'{:library}/views/layouts/{:layout}.{:type}.php',
		),
		'element' => array(
			LITHIUM_APP_PATH . '/views/elements/{:template}.{:type}.php',
			RADIUM_PATH . '/views/elements/{:template}.{:type}.php',
			'{:library}/views/elements/{:template}.{:type}.php',
		),
		'widget' => array(
			LITHIUM_APP_PATH . '/views/widgets/{:template}.{:type}.php',
			RADIUM_PATH . '/views/widgets/{:template}.{:type}.php',
			'{:library}/views/widgets/{:template}.{:type}.php',
		),
    )
));

Media::type('rss', 'application/rss+xml');
Media::type('csv', 'application/csv', array('encode' => function($data) {
	$scaffold = Environment::get('scaffold');
	if ($scaffold) {
		$model = $scaffold['model'];
		$fields = $model::schema()->names();
	}

	ob_start();
	$out = fopen('php://output', 'w');

	if ($scaffold && isset($data['object'])) {
		$object = $data['object'] ? : array();
		$replace = Set::flatten(array_merge(compact('scaffold'), $object));
		$name = Text::insert('{:scaffold.human} - {:_id}: {:name}.csv', $replace);
		foreach($fields as $field) {
			fputcsv($out, array($field, isset($object[$field]) ? $object[$field] : ''));
		}
	}

	if ($scaffold && isset($data['objects'])) {
		$objects = $data['objects'] ? : array();
		$name = Text::insert('{:slug}.csv', $scaffold);
		fputcsv($out, array_values($fields));
		foreach($data['objects'] as $row) {
			fputcsv($out, Set::flatten($row));
		}
	}

	if (!$scaffold && $data) {
		$name = 'temp.csv';
		foreach($data as $row) {
			fputcsv($out, Set::flatten($row));
		}
	}

	fclose($out);
	header(sprintf('Content-Disposition: attachment; filename="%s"', $name));
	return ob_get_clean();
}));

/*
 * this filter allows automatic linking and loading of assets from `webroot` folder
 */
Filters::apply(Dispatcher::class, '_callable', function($params, $next) {
	$url = ltrim($params['request']->url, '/');
	list($library, $asset) = explode('/', $url, 2) + ["", ""];

	if ($asset && $library == 'radium' && ($path = Media::webroot($library)) && file_exists($file = "{$path}/{$asset}")) {
		return function() use ($file) {
			$info = pathinfo($file);
			$media = Media::type($info['extension']);
			$content = (array) $media['content'];

			return new Response([
				'headers' => ['Content-type' => reset($content)],
				'body' => file_get_contents($file)
			]);
		};
	}
	return $next($params);
});


