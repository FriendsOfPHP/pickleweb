<?php

namespace PickleWeb\Controller;

use PickleWeb\Auth\ProviderInterface;
use PickleWeb\Entity\User;

/**
 * Class AuthController.
 */
class AuthController extends ControllerAbstract
{
    /**
     * GET /login.
     */
    public function loginAction()
    {
        $this->app
            ->redirectIf($this->app->user(), '/profile')
            ->render('register.html');
    }

    /**
     * GET /logout.
     */
    public function logoutAction()
    {
        session_destroy();

        $this->app->redirect('/');
    }

    /**
     * GET /login/:provider.
     *
     * @param string $provider
     */
    public function loginWithProviderAction($provider)
    {
        $providerKey = 'authentication.provider.'.$provider;

        // Check if provider exist
        if (!$this->app->container->has($providerKey)) {
            $this->app->notFound();
        }

        /* @var $authorizationProvider ProviderInterface */
        $authorizationProvider = $this->app->container->get($providerKey);

        $token             = $authorizationProvider->handleAuth($this->app);
        $_SESSION['token'] = $token;

        $userDetails = $authorizationProvider->getUserDetails($token);

        // Create each time a new user while a proper persister is done
        $user = new User();
        $user->setEmail($userDetails['email']);
        $user->setNickname($userDetails['nickname']);
        $user->setName($userDetails['realname']);
        $user->setPicture($userDetails['profilepicture']);
        $user->setGithubId($userDetails['uid']);
        $user->setGithubHomepage($userDetails['homepage']);

        $_SESSION['user'] = serialize($user);

        $this->app->redirect('/profile');
    }
}
