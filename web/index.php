<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/twig/twig/lib/Twig/Autoloader.php';

// use PickleWeb\View\Twig;

\Twig_Autoloader::register();

$app = new \Slim\Slim(
		['view' => new PickleWeb\View\Twig()
	]);

$view = $app->view();
$app->get('/package/:name', function ($name) use ($app) {
		$app->view()->setData(array('title' => 'Package: ' . $name, 'packagename' => $name));
		$app->render('index.html');
	});
if (0) {
echo "<pre>";
var_dump($view->getInstance());
echo "</pre>";
}
$app->run();
