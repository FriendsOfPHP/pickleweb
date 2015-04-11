<?php

require __DIR__.'/../vendor/autoload.php';

function check_or_create_json_dir(\Slim\Slim $app)
{
    if (is_dir($app->config('json_path')) === false) {
        mkdir($app->config('json_path'), 0777, true);
        mkdir($app->config('json_path').'users/github', 0777, true);
        mkdir($app->config('json_path').'extensions', 0777, true);
    }
}

session_start();
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
$app = new \PickleWeb\Application(
    new \Slim\Slim(
        [
            'view' => new \PickleWeb\View\Twig(),
            'json_path' => __DIR__ . '/../json/'
        ]
    )
);

$app->get('/logout', function () use ($app) {
        session_destroy();

        $app->redirect('/');
    }
);

$app->get('/', function () use ($app, & $user) {
        $app
            ->setViewData([
                    'user'  => $user,
                ]
            )
            ->render('home.html')
        ;
    }
);

$app->get('/package/register', function () use ($app, & $user) {
        if ($app->request()->get('confirm')) {
            /* create registration and package handler, json&co*/
        } else {
            $app
                ->redirectUnless($user, '/login')
                ->setViewData([
                        'user' => $user,
                    ]
                )
                ->render('registerextension.html')
            ;
        }
    }
);

$app->post('/package/register', function () use ($app, & $user) {
        $driver = new PickleWeb\Repository\Github($app->request()->post('package_repository'));
        $info = $driver->getInformation();

        if ($info['type'] != 'extension') {
            $app->flash('error', $info['name'].' is not an extension package');
            $app->redirect('/package/register');
        }

        $app
            ->redirectUnless($user, '/login')
            ->setViewData([
                    'extension' => ($driver),
                    'user' => $user,
                    'confirm' => true,
                ]
            )
            ->render('extension_register_info.html')
        ;
    }
);

$app->get('/package/:package', function ($package) use ($app, & $user) {
        $jsonPath = $app->config('json_path').'extensions/'.$package.'.json';

        $app
            ->notFoundIf(file_exists($jsonPath) === false)
            ->otherwise(function () use (& $package, $jsonPath) {
                $json = json_decode(file_get_contents($jsonPath), true);

                array_map(
                    function ($version) {
                        $version['time'] = new \DateTime($version['time']);
                    },
                    $json['packages'][$package]
                );

                $latest = reset($json['packages'][$package]);

                $package = [
                    'name' => key($json['packages']),
                    'versions' => $json['packages'][$package],
                    'latest' => $latest,
                    'maintainer' => reset($latest['authors']),
                ];
            })
            ->setViewData([
                    'package' => $package,
                    'user' => $user,
                ]
            )
            ->render('package.html')
        ;
    }
);

$app->get('/profile', function () use ($app, & $user) {
        $app
            ->redirectUnless($user, '/login')
            ->setViewData([
                    'account' => $user,
                    'user' => $user,
                ]
            )
            ->render('account.html')
        ;
    }
);

$app->get('/account(/:name)', function ($name = null) use ($app, & $user) {
        $jsonPath = $app->config('json_path').'users/github/'.$name.'.json';

        $app
            ->notFoundIf(file_exists($jsonPath) === false)
            ->redirectUnless($name, '/profile')
            ->setViewData([
                    'account' => json_decode(file_get_contents($jsonPath), true),
                    'user' => $user,
                ]
            )
            ->render('account.html')
        ;
    }
);

$app->get('/login', function () use ($app, & $user) {
        $app
            ->redirectIf($user, '/profile')
            ->render('register.html')
        ;
    }
);

$app->get('/login/:provider', function ($provider) use ($app, & $user) {
        $app
            ->redirectIf($user, '/profile')
            ->otherwise(function (\PickleWeb\Application $app) use (& $code, & $auth, $provider) {
                    $code = $app->request()->get('code');

                    try {
                        $auth = new PickleWeb\Action\AuthAction($provider);

                        if (!$code) {
                            $auth->GetCode($app);
                        }
                    } catch (\InvalidArgumentException $exception) {
                        $app->notFound();
                    }
                }
            )
            ->then(function (\PickleWeb\Application $app) use (& $user, & $code, & $auth) {
                    $state = $app->request()->get('state');
                    $token = $auth->getToken($code, $state);
                    $user = $auth->getProvider()->getUserDetails($token);
                    $emails = array_filter(
                        $auth->getProvider()->getUserEmails($token),
                        function ($email) {
                            return $email->primary;
                        }
                    );

                    $user->exchangeArray(['email' => current($emails)->email]);

                    $jsonPath = $app->config('json_path').'users/github/'.$user->nickname.'.json';
                    check_or_create_json_dir($app);
                    if (!file_put_contents($jsonPath, json_encode($user->getArrayCopy(), JSON_PRETTY_PRINT))) {
                        $app->renderError(500);
                        exit();
                    }

                    $_SESSION['user'] = $user;
                }
            )
            ->redirect('/profile');
    }
);

$app->run();
