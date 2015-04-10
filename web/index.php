<?php

require __DIR__.'/../vendor/autoload.php';

function redirect_login(\Slim\Slim $app) {
    $app->redirect('/login');
}

function render_error(\Slim\Slim $app, $code) {
    $app->render('errors/' . $code . '.html');
    $app->stop();
}

session_start();
$user = isset($_SESSION['user']) ? $_SESSION['user'] : false;

$app = new \Slim\Slim([
        'view' => new PickleWeb\View\Twig(),
        'json_path' => __DIR__ . '/../json/',
    ]
);

$app->get('/logout', function () use ($app) {
        session_destroy();

        $app->redirect('/');
    }
);

$app->get('/', function () use ($app, $user) {
        $app->view()->setData([
            'user'  => $user,
        ]);

        $app->render('home.html');
    }
);

$app->get('/package/register', function () use ($app, $user) {
        if (!$user) {
            redirect_login($app);
        }

        $app->view()->setData([
            'user' => $user,
        ]);

        $app->render('registerextension.html');
    }
);

$app->post('/package/register', function () use ($app, $user) {
        if (!$user) {
            redirect_login($app);
        }

        $repositoryUri = $app->request->post('package_repository');
        $repository = new PickleWeb\Repository\Github($repositoryUri);
        $info = $repository->getInformation();

        $app->view()->setData([
            'extension' => $info,
            'user' => $user,
        ]);

        $app->render('extension_register_info.html');

    }
);

$app->get('/package/:package', function ($package) use ($app, $user) {
        $app->view()->setData([
            'name' => $package,
            'user' => $user,
        ]);

        $app->render('package.html');
    }
);

$app->get('/profile', function () use ($app, $user) {
        if (!$user) {
            redirect_login($app);
        }

        $app->view()->setData([
            'account' => $user,
            'user' => $user,
        ]);

        $app->render('account.html');
    }
);

$app->get('/account/(:name)', function ($name = '') use ($app, $user) {
        $jsonPath = $app->config('json_path').'users/github/'.$name.'.json';

        if (file_exists($jsonPath)) {
            $account = json_decode(file_get_contents($jsonPath), true);

            $app->view()->setData([
                'account' => $account,
                'user' => $user,
            ]);

            $app->render('account.html');
        } else {
            render_error($app, 404);
        }
    }
);

$app->get('/login', function () use ($app) {
        $app->render('register.html');
    }
);

$app->get('/login/github', function () use ($app, $user) {
        if ($user) {
            $app->redirect('/profile');
        }

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

            $jsonPath = $app->config('json_path') . 'users/github/' . $user->nickname . '.json';
            file_put_contents($jsonPath, json_encode($user->getArrayCopy(), JSON_PRETTY_PRINT));

            $_SESSION['user'] = $user;
            $app->redirect('/profile');
        }
    }
);

if (is_dir($app->config('json_path')) === false) {
    mkdir($app->config('json_path'), 0777, true);
    mkdir($app->config('json_path') . 'users/github', 0777, true);
}

$app->run();
