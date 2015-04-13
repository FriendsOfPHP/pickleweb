<?php

require __DIR__.'/../vendor/autoload.php';

function check_or_create_json_dir(\PickleWeb\Application $app)
{
    if (is_dir($app->config('json_path')) === false) {
        mkdir($app->config('json_path'), 0777, true);
        mkdir($app->config('json_path').'users/github', 0777, true);
        mkdir($app->config('json_path').'extensions', 0777, true);
    }
}

$app = new \PickleWeb\Application(
    new \Slim\Slim(
        [
            'view' => new \PickleWeb\View\Twig(),
            'json_path' => __DIR__.'/json/',
            'cache_dir' => __DIR__.'/../cache-dir/',
        ]
    )
);

$app->get('/logout', function () use ($app) {
        session_destroy();

        $app->redirect('/');
    }
);

$app->get('/', function () use ($app) {
        $app->render('home.html');
    }
);

$app->getSecured('/package/register', function () use ($app) {
        $app->render('registerextension.html');
    }
);

$app->postSecured('/package/register', function () use ($app) {
        $token = $_SESSION['token'];

        $driver = new PickleWeb\Repository\Github($app->request()->post('package_repository'), $token->accessToken, $app->config('cache_dir'));
        $info = $driver->getInformation();

        if ($info['type'] != 'extension') {
            $app->flash('error', $info['name'].' is not an extension package');
            $app->redirect('/package/register');
        }
        $tags = $driver->getReleaseTags();
print_r($tags);
        $app->setViewData([
                    'extension' => $info,
                    'tags'      => $tags,
                    'confirm'   => true,
                ]
            )
            ->render('extension_register_info.html')
        ;
    }
);

$app->get('/package/:package', function ($package) use ($app) {
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
                ]
            )
            ->render('package.html')
        ;
    }
);

$app->getSecured('/profile', function () use ($app) {
        $app
            ->setViewData([
                    'account' => $app->user()
                ]
            )
            ->render('account.html')
        ;
    }
);

$app->get('/account(/:name)', function ($name = null) use ($app) {
        $jsonPath = $app->config('json_path').'users/github/'.$name.'.json';

        $app
            ->notFoundIf(file_exists($jsonPath) === false)
            ->redirectUnless($name, '/profile')
            ->setViewData([
                    'account' => json_decode(file_get_contents($jsonPath), true)
                ]
            )
            ->render('account.html')
        ;
    }
);

$app->get('/login', function () use ($app) {
        $app
            ->redirectIf($app->user(), '/profile')
            ->render('register.html')
        ;
    }
);

$app->get('/login/:provider', function ($provider) use ($app) {
        $app
            ->redirectIf($app->user(), '/profile')
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
                    $_SESSION['token'] = $token;
                    $_SESSION['user'] = $user;
                }
            )
            ->redirect('/profile');
    }
);
$app->run();
