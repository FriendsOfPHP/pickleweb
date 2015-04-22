<?php

use Buzz\Browser;
use League\OAuth1\Client\Server\Bitbucket;
use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Provider\Google;
use PickleWeb\Auth\BitbucketProvider;
use PickleWeb\Auth\GithubProvider;
use PickleWeb\Auth\GoogleProvider;
use PickleWeb\Entity\UserRepository;
use Slim\Helper\Set;

require __DIR__ . '/../vendor/autoload.php';

function check_or_create_json_dir(\PickleWeb\Application $app)
{
    if (is_dir($app->config('json_path')) === false) {
        mkdir($app->config('json_path'), 0777, true);
        mkdir($app->config('json_path') . 'users/github', 0777, true);
        mkdir($app->config('json_path') . 'extensions', 0777, true);
    }
}

$app = new \PickleWeb\Application(
    [
        'view'      => new \PickleWeb\View\Twig(),
        'json_path' => __DIR__ . '/json/',
        'cache_dir' => __DIR__ . '/../cache-dir/',
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
        return json_decode(file_get_contents(__DIR__ . '/../src/config.json'), true);
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
                    'redirectUri'  => 'http://127.0.0.1:8080/login/google',
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
                    'callback_uri' => 'http://127.0.0.1:8080/login/bitbucket',
                ]
            ),
            $container->get('redis.client'),
            $container->get('http.client')
        );
    }
);

/*
 * Declare controllers if you need to inject dependancies in it
 *
 * example :
 *
 * $app->container->singleton('PickleWeb\Controller\AuthController', function ($container) {
 *     return new PickleWeb\Controller\AuthController($container['authorization.oauth2.github']);
 * });
 */

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
$app->get('/package/:vendor/:package', 'PickleWeb\Controller\PackageController:viewPackageAction');

// Users
$app->getSecured('/profile', 'PickleWeb\Controller\UserController:profileAction');
$app->getSecured('/profile/remove', 'PickleWeb\Controller\UserController:removeConfirmAction');
$app->postSecured('/profile/remove', 'PickleWeb\Controller\UserController:removeAction');
$app->get('/account(/:name)', 'PickleWeb\Controller\UserController:viewAccountAction');

// Hooks
$app->post('/github/hooks/:username', 'PickleWeb\Controller\GithubController:hookAction');

/*
 * Run application
 */
$app->run();
