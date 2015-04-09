<?php

require __DIR__.'/../vendor/autoload.php';

function redirect_login()
{
    header('Location: /login');
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
            'title' => 'Pickle Packagist, repository index for PHP, HHVM and co extensions',
            'user'  => $user,
        ]);
        $app->render('home.html');
    }
);

$app->get('/package/:package', function ($package) use ($app, $user) {
        $app->view()->setData([
            'name' => $package,
        ]);
        $app->render('package.html');
    }
);

$app->get('/profile/', function () use ($app, $user) {
        if (!$user) {
            redirect_login();
        }
        $app->view()->setData([
                'title' => 'Profile: '.$user->nickname,
                'user' => $user,
            ]);
        $app->render('account.html');
});

$app->get('/account/(:name)', function ($name = '') use ($app, $user) {
        $jsonPath = $app->config('json_path').'users/github/'.$name.'.json';
        if (file_exists($jsonPath)) {
            $user = json_decode(file_get_contents($jsonPath), true);
        }
        $app->view()->setData([
                'title' => 'Package: '.$name,
                'user' => $user,
            ]);
        $app->render('account.html');
        exit();
    });

$app->get('/login', function () use ($app) {
        $app->view()->setData([
                'title' => 'Login',
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

        $_SESSION['user'] = $user;
        header('Location: /profile/');
        exit();
        $app->view()->setData([
                'title' => 'Register as Github user',
                'user'  => $user,
            ]);
        $app->render('registergithub.html');
        exit();
    });

$app->run();
