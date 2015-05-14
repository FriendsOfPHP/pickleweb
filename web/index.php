<?php

use Buzz\Browser;
use League\OAuth1\Client\Server\Bitbucket;
use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Provider\Google;
use PickleWeb\Auth\BitbucketProvider;
use PickleWeb\Auth\GithubProvider;
use PickleWeb\Auth\GoogleProvider;
use PickleWeb\Entity\UserRepository;
use PickleWeb\Entity\ExtensionRepository;
use Slim\Helper\Set;
use \Elastica\Client;

require __DIR__.'/../vendor/autoload.php';

function check_or_create_json_dir(\PickleWeb\Application $app)
{
    if (is_dir($app->config('json_path')) === false) {
        mkdir($app->config('json_path'), 0777, true);
        mkdir($app->config('json_path').'users/github', 0777, true);
        mkdir($app->config('json_path').'extensions', 0777, true);
    }
}

$cacheDir = __DIR__.'/../cache-dir/';
$app = new \PickleWeb\Application(
    [
        'view'      => new \PickleWeb\View\Twig(['cache' => $cacheDir . 'twig']),
        'json_path' => __DIR__.'/json/',
        'cache_dir' => $cacheDr,
        'web_root_dir' => __DIR__,
    ]
);

/*
 * Declare service
 *
 * example :
 *
 * $app->container->singleton('authorization.oauth2.github', function ($container) {
 *     return new \PickleWeb\Action\AuthAction('github');
 * });
 */

// Config
$app->container->singleton(
    'app.config',
    function (Set $container) {
        return json_decode(file_get_contents(__DIR__.'/../src/config.json'), true);
    }
);

// Redis client
$app->container->singleton(
    'redis.client',
    function (Set $container) {
        $config = $container->get('app.config');

        $client = new Predis\Client(sprintf('tcp://%s:%s', $config['redis']['host'], $config['redis']['port']));
        $client->select($config['redis']['db']);

        return $client;
    }
);

// Http client
$app->container->singleton(
    'http.client',
    function (Set $container) {
        return new Browser();
    }
);

// User repository
$app->container->singleton(
    'user.repository',
    function (Set $container) {
        return new UserRepository($container->get('redis.client'));
    }
);

// User repository
$app->container->singleton(
    'extension.repository',
    function (Set $container) {
        return new ExtensionRepository($container->get('redis.client'));
    }
);

$app->container->singleton(
	'elastica.client',
	function (Set $container) {
		$configApp = $container->get('app.config');
		$client = new \Elastica\Client([
					'host' => $configApp['elasticsearch']['host'],
					'port'  => $configApp['elasticsearch']['port']
					]);

		return $client;
	}
);

// Github Authorization provider
$app->container->singleton(
    'authentication.provider.github',
    function (Set $container) {
        $config = $container->get('app.config');

        return new GithubProvider(
            new Github(
                [
                    'clientId'     => $config['oauth']['github']['clientId'],
                    'clientSecret' => $config['oauth']['github']['clientSecret'],
                    'scopes'       => ['user:email', 'read:repo_hook'],
                    'redirectUri' => sprintf('%s://%s/login/github', isset($_SERVER['HTTPS']) ? 'https' : 'http', $_SERVER['HTTP_HOST']),
                ]
            ),
            $container->get('redis.client'),
            $container->get('http.client')
        );
    }
);

// Google Authorization provider
$app->container->singleton(
    'authentication.provider.google',
    function (Set $container) {
        $config = $container->get('app.config');

        return new GoogleProvider(
            new Google(
                [
                    'clientId'     => $config['oauth']['google']['clientId'],
                    'clientSecret' => $config['oauth']['google']['clientSecret'],
                    'redirectUri' => sprintf('%s://%s/login/google', isset($_SERVER['HTTPS']) ? 'https' : 'http', $_SERVER['HTTP_HOST']),
                ]
            ),
            $container->get('redis.client'),
            $container->get('http.client')
        );
    }
);

// Bitbucket Authorization provider
$app->container->singleton(
    'authentication.provider.bitbucket',
    function (Set $container) {
        $config = $container->get('app.config');

        return new BitbucketProvider(
            new Bitbucket(
                [
                    'identifier'   => $config['oauth']['bitbucket']['clientId'],
                    'secret'       => $config['oauth']['bitbucket']['clientSecret'],
                    'callback_uri' => sprintf('%s://%s/login/bitbucket', isset($_SERVER['HTTPS']) ? 'https' : 'http', $_SERVER['HTTP_HOST']),
                ]
            ),
            $container->get('redis.client'),
            $container->get('http.client')
        );
    }
);

/*
 * Declare routes
 */

// Default
$app->get('/', 'PickleWeb\Controller\DefaultController:indexAction');

// Authorization
$app->get('/login', 'PickleWeb\Controller\AuthController:loginAction');
$app->get('/logout', 'PickleWeb\Controller\AuthController:logoutAction');
$app->get('/login/:provider', 'PickleWeb\Controller\AuthController:loginWithProviderAction');

// Packages
$app->getSecured('/package/register', 'PickleWeb\Controller\PackageController:registerAction');
$app->postSecured('/package/register', 'PickleWeb\Controller\PackageController:registerPackageAction');

$app->getSecured('/package/:vendor/:package/remove', 'PickleWeb\Controller\PackageController:removeConfirmAction');
$app->postSecured('/package/:vendor/:package/remove', 'PickleWeb\Controller\PackageController:removeAction');

$app->get('/package/:vendor/:package', 'PickleWeb\Controller\PackageController:viewPackageAction');
$app->get('/package/:vendor/:package/getapikey', 'PickleWeb\Controller\PackageController:getApiKey');
$app->get('/package/:vendor/:package/showapikey', 'PickleWeb\Controller\PackageController:showApiKey');

// Users
$app->getSecured('/profile', 'PickleWeb\Controller\UserController:profileAction');
$app->getSecured('/profile/remove', 'PickleWeb\Controller\UserController:removeConfirmAction');
$app->postSecured('/profile/remove', 'PickleWeb\Controller\UserController:removeAction');
$app->get('/account(/:name)', 'PickleWeb\Controller\UserController:viewAccountAction');

// Admin
$app->getSecured('/admin/updatephpext', 'PickleWeb\Controller\AdminController:updateBundleExtensions');
$app->postSecured('/admin/updatephpext', 'PickleWeb\Controller\AdminController:saveBundleExtensions');

// Hooks
$app->post('/github/hooks/:vendor/:repository', 'PickleWeb\Controller\GithubController:hookAction');

// Search
$app->get('/search/:query', 'PickleWeb\Controller\SearchController:search');

/*
 * Run application
 */
$app->run();

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
