<?php
namespace PickleWeb\Controller;

/**
 * Class AuthController
 *
 * @package PickleWeb\Controller
 */
class AuthController extends ControllerAbstract
{
    /**
     * GET /login
     */
    public function loginAction()
    {
        $this->app
            ->redirectIf($this->app->user(), '/profile')
            ->render('register.html');
    }

    /**
     * GET /logout
     */
    public function logoutAction()
    {
        session_destroy();

        $this->app->redirect('/');
    }

    /**
     * GET /login/:provider
     *
     * @param string $provider
     */
    public function loginWithProviderAction($provider)
    {
        $this->app
            ->redirectIf($this->app->user(), '/profile')
            ->otherwise(
                function (\PickleWeb\Application $app) use (& $code, & $auth, $provider) {
                    $code = $app->request()->get('code');

                    try {
                        $auth = new \PickleWeb\Action\AuthAction($provider);

                        if (!$code) {
                            $auth->GetCode($app);
                        }
                    } catch (\InvalidArgumentException $exception) {
                        $app->notFound();
                    }
                }
            )
            ->then(
                function (\PickleWeb\Application $app) use (& $user, & $code, & $auth) {
                    /* @var $auth \PickleWeb\Action\AuthAction */
                    $state  = $app->request()->get('state');
                    $token  = $auth->getToken($code, $state);
                    $user   = $auth->getProvider()->getUserDetails($token);
                    $emails = array_filter(
                        $auth->getProvider()->getUserEmails($token),
                        function ($email) {
                            return $email->primary;
                        }
                    );

                    $user->exchangeArray(['email' => current($emails)->email]);

                    $jsonPath = $app->config('json_path') . 'users/github/' . $user->nickname . '.json';
                    check_or_create_json_dir($app);
                    if (!file_put_contents($jsonPath, json_encode($user->getArrayCopy(), JSON_PRETTY_PRINT))) {
                        $app->renderError(500);
                        exit();
                    }
                    $_SESSION['token'] = $token;
                    $_SESSION['user']  = $user;
                }
            )
            ->redirect('/profile');
    }
}