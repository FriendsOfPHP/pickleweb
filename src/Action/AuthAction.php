<?php

namespace PickleWeb\Action;

use League\OAuth2\Client\Provider as Provider;
use PickleWeb\Application;
use PickleWeb\Auth\GithubProvider;

/**
 * Class AuthAction
 *
 * @package PickleWeb\Action
 */
class AuthAction
{

    /**
     * @var \League\OAuth2\Client\Provider\AbstractProvider
     */
    protected $provider;

    /**
     * @param string $provider
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($provider = 'github')
    {
        if ($provider == 'github') {
            $this->provider = new GithubProvider(
                [
                    'clientId'     => getenv('GITHUB_CLIENT_ID'),
                    'clientSecret' => getenv('GITHUB_CLIENT_SECRET'),
                    'scopes'       => ['user:email', 'read:repo_hook'],
                ]
            );
        } else {
            throw new \InvalidArgumentException('Provider <' . $provider . '> not supported');
        }
    }

    /**
     * @param \PickleWeb\Application $app
     */
    public function getCode(Application $app)
    {
        // If we don't have an authorization code then get one
        $authUrl                 = $this->provider->getAuthorizationUrl();
        $_SESSION['oauth2state'] = $this->provider->state;

        $app->redirect($authUrl);
    }

    /**
     * @throws \RuntimeException
     */
    protected function checkState()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
                unset($_SESSION['oauth2state']);

                throw new \RuntimeException('Invalid state');
            }
        }
    }

    /**
     * @param string $code
     *
     * @throws \RuntimeException
     *
     * @return mixed
     */
    public function getToken($code)
    {
        $this->checkState();

        // Try to get an access token (using the authorization code grant)
        $token = $this->provider->getAccessToken(
            'authorization_code',
            [
                'code' => $code,
            ]
        );

        // Optional: Now you have a token you can look up a users profile data
        try {
            // We got an access token, let's now get the user's details
            $userDetails = $this->provider->getUserDetails($token);
            $userDetails->exchangeArray(
                [
                    'email'    => $this->provider->getUserEmails($token),
                    'homepage' => $this->provider->domain . '/' . $userDetails->nickname,
                ]
            );
        } catch (\Exception $e) {
            throw new \RuntimeException('cannot fetch account details');
        }

        /* TODOS: implement handling of:
         * $token->refreshToken;
         * $token->expires;
         */

        return $token;
    }

    /**
     * @return GithubProvider
     */
    public function getProvider()
    {
        return $this->provider;
    }
}
