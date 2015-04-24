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

        $token                          = $authorizationProvider->handleAuth($this->app);
        $_SESSION[$provider.'.token'] = $token;

        $userDetails = $authorizationProvider->getUserDetails($token);

        if (empty($userDetails['email']) || empty($userDetails['uid'])) {
            throw new \ErrorException('User details incomplete. Unable to fetch user');
        }

        // Fetch or persist user from repository
        /* @var $userRepository UserRepository */
        $userRepository = $this->app->container->get('user.repository');
        $user           = $userRepository->find($userDetails['email']);
        var_dump($user);
        if (is_null($user)) {
            $user = $userRepository->findByProviderId($provider, $userDetails['uid']);
            if (is_null($user)) {
                $user = new User();
                $user->setEmail($userDetails['email']);
                $user->setNickname($userDetails['nickname']);
                $user->setName($userDetails['realname']);
                $user->setPicture($userDetails['profilepicture']);

                $userRepository->persist($user);
            }
        }

        // Complete information if needed
        if ('github' == $provider && empty($user->getGithubId())) {
            $user->setGithubId($userDetails['uid']);
            $user->setGithubHomepage($userDetails['homepage']);
            if (empty($user->getPicture())) {
                $user->setPicture($userDetails['profilepicture']);
            }

            $userRepository->persist($user);
        } elseif ('google' == $provider && empty($user->getGoogleId())) {
            $user->setGoogleId($userDetails['uid']);
            $user->setGoogleHomepage($userDetails['homepage']);
            if (empty($user->getPicture())) {
                $user->setPicture($userDetails['profilepicture']);
            }

            $userRepository->persist($user);
        } elseif ('bitbucket' == $provider && empty($user->getBitbucketId())) {
            $user->setBitbucketId($userDetails['uid']);
            $user->setBitbucketHomepage($userDetails['homepage']);
            if (empty($user->getPicture())) {
                $user->setPicture($userDetails['profilepicture']);
            }

            $userRepository->persist($user);
        }

        // persist user in session
        $_SESSION['user'] = $user->getId();

        $this->app->redirect('/profile');
    }
}
