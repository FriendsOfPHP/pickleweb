<?php

namespace PickleWeb\Controller;

use PickleWeb\Auth\ProviderInterface;
use PickleWeb\Entity\User;
use PickleWeb\Entity\UserRepository;

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
     * @param string $provider
     *
     * @throws \ErrorException
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

        $token = $authorizationProvider->handleAuth($this->app);
        $_SESSION[$provider.'.token'] = $token;

        $providerMetadata = $authorizationProvider->getUserDetails($token);

        if (empty($providerMetadata->getEmail()) || empty($providerMetadata->getUid())) {
            $this->app->flash('error', 'User details incomplete. Unable to fetch user');
            $this->app->redirect('/login');
        }

        // Fetch or persist user from repository
        /* @var $userRepository UserRepository */
        $userRepository = $this->app->container->get('user.repository');
        $user = $userRepository->find($providerMetadata->getEmail());

        if (is_null($user)) {
            $user = $userRepository->findByProviderId($provider, $providerMetadata->getUid());
            if (is_null($user)) {
                $user = new User();
                $user->setEmail($providerMetadata->getEmail())
                    ->setNickname($providerMetadata->getNickName())
                    ->setName($providerMetadata->getRealName())
                    ->setPicture($providerMetadata->getProfilePicture())
                    ->setLocation($providerMetadata->getLocation())
                    ->addProviderMetadata($provider, $providerMetadata);

                $userRepository->persist($user);
            }
        }

        // Complete information if needed
        if (!$user->hasProviderMetadata($provider)) {
            $user->addProviderMetadata($provider, $providerMetadata);

            if (empty($user->getPicture())) {
                $user->setPicture($providerMetadata->getProfilePicture());
            }

            if (empty($user->getLocation())) {
                $user->setLocation($providerMetadata->getLocation());
            }

            $userRepository->persist($user);
        }

        // persist user in session
        $_SESSION['user'] = $user->getId();

        $this->app->redirect('/profile');
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
