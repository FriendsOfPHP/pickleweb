<?php

require __DIR__ . '/../vendor/autoload.php';
use \OAuth\Common\Storage\Session as Session;

// use PickleWeb\View\Twig;

$app = new \Slim\Slim([
		'view' => new PickleWeb\View\Twig(),
		'json_path' => __DIR__ . '/json/'
	]);
session_start();

$app->get('/', function () use ($app) {
	$user = isset($_SESSION['user']) ? $_SESSION['user']: false;
	$app->view()->setData([
		'title' => 'Pickle Packagist, repository index for PHP, HHVM and co extensions',
		'user'  => $user
	]);
	$app->render('index.html');
}
);
$app->get('/account/:name', function ($name) use ($app) {
		$jsonPath = $app->config('json_path') . 'users/github/' . $name . '.json';
		if (file_exists($jsonPath)) {
			$user = json_decode(file_get_contents($jsonPath), true);
		}
		$app->view()->setData([
				'title' => 'Package: ' . $name,
				'user' => $user
			]);
		$app->render('account.html');
		exit();
	});

$app->get('/user/register', function () use ($app) {
		$app->view()->setData([
				'title' => 'Register', 
			]);
		$app->render('register.html');
		exit();
	});

$app->get('/login', function () use ($app) {
		$app->view()->setData([
				'title' => 'Login'
			]);
		$app->render('register.html');
		exit();
	});

$app->get('/login/github', function () use ($app) {
		$code = $app->request->get('code');
		$auth = new PickleWeb\Action\AuthAction('github', $app);
		if (!$code) {			
			$auth->GetCode();
		} else {
			$state = $app->request->get('state');
			$token = $auth->getToken($code, $state);
			$user = $auth->getProvider()->getUserDetails($token);
			$emails = $auth->getProvider()->getUserEmails($token);
			foreach ($emails as $email) {
				if ($email->primary) {
					$user->exchangeArray(['email' => $email->email]);
					break;
				}
			}			
		}
		$app->view()->setData([
				'title' => 'Register as Github user',
				'user'  => $user
			]);
		$app->render('registergithub.html');
		exit();
	});

$app->run();
