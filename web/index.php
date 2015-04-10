<?php

require __DIR__.'/../vendor/autoload.php';

function redirect_login()
{
    header('Location: /login');
    exit();
}

function render_error($app, $code) {
    $app->render($code . '.html');
    exit();
}

// use PickleWeb\View\Twig;

session_start();
$user = isset($_SESSION['user']) ? $_SESSION['user'] : false;

$app = new \Slim\Slim([
        'view' => new PickleWeb\View\Twig(),
        'json_path' => __DIR__.'/json/',
    ]);

$app->get('/logout', function () {
    session_destroy();
    header('Location: /');
    exit();
});

$app->get('/', function () use ($app, $user) {
        $app->view()->setData([
            'user'  => $user,
        ]);
        $app->render('home.html');
    }
);

$app->get('/package/register', function () use ($app, $user) {
        if (!$user) {
            redirect_login();
        }
        $app->view()->setData([
                'user' => $user,
            ]);
        $app->render('registerextension.html');
    }
);

$app->post('/package/register', function () use ($app, $user) {
        if (!$user) {
            redirect_login();
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

$app->get('/profile/', function () use ($app, $user) {
        if (!$user) {
            redirect_login();
        }
        $app->view()->setData([
                'account' => $user,
                'user' => $user,
            ]);
        $app->render('account.html');
});

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

        exit();
    });

$app->get('/login', function () use ($app) {
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

            $_SESSION['user'] = $user;
            header('Location: /profile/');
        }
        exit();
    });

$app->run();
