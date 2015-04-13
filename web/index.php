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
        $app->setViewData()->render('home.html');
    }
);

$app->getSecured('/package/register', function () use ($app) {
        if ($app->request()->get('confirm')) {
            $transaction = $app->request()->get('id');
            $pathTransaction = $app->config('cache_dir').'/'.$transaction.'.json';
            if (!file_exists($pathTransaction)) {
                $app->redirect('/package/register');
                exit();
            }

            $token = $_SESSION['token'];
            $transaction = json_decode(file_get_contents($pathTransaction));

            $driver = new PickleWeb\Repository\Github($transaction->extension->vcs, $token->accessToken, $app->config('cache_dir'));
            $info = $driver->getInformation();

            echo '<pre>';
            var_dump($transaction->extension->support->source);
            print_r($transaction);
            print_r($info);
            echo '</pre>';
        } else {
            $app
                ->setViewData([
                        'repository' => $app->request()->get('repository')
                    ]
                )
                ->render('extension/register.html')
            ;
        }
    }
);

$app->postSecured('/package/register', function () use ($app) {
        $token = $_SESSION['token'];
        $repo = $app->request()->post('repository');

        try {
            $driver = new PickleWeb\Repository\Github($repo, $token->accessToken, $app->config('cache_dir'));
            $info = $driver->getInformation();

            if ($info === null) {
                $app->flash('error', 'No valid composer.json found.');
                $app->redirect('/package/register');
            }

            $info['vcs'] = $repo;

            if ($info['type'] != 'extension') {
                $app->flash('error', $info['name'] . ' is not an extension package');
                $app->redirect('/package/register');
            }

            $tags = $driver->getReleaseTags();

            $package = [
                'extension' => $info,
                'tags'      => $tags,
                'user'      => $app->user()
            ];

            $jsonPackage = json_encode($package, JSON_PRETTY_PRINT);
            $transaction = hash('sha256', $jsonPackage);

            file_put_contents($app->config('cache_dir').'/'.$transaction.'.json', $jsonPackage);

            $app
                ->setViewData([
                        'transaction' => $transaction,
                        'extension' => $info,
                        'tags'      => $tags
                    ]
                )
                ->render('extension/confirm.html')
            ;
        } catch (\RuntimeException $exception) {
            $app
                ->flash('error', 'An error occurred while retrieving extension data. Please try again later.')
                ->redirect('/package/register?repository=' . $repo)
            ;
        }
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
                    'extension' => $package,
                ]
            )
            ->render('extension/info.html')
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
        ;
    }
);

$app->run();
