<?php

namespace PickleWeb\Action;

use League\OAuth2\Client\Provider as Provider;
use PickleWeb\Auth\GithubProvider;

class AuthAction
{
    protected $provider;

    protected $app;

    public function __construct($provider = 'github', \Slim\Slim $app, $url = 'http://127.0.0.1:8080/login/')
    {
        if ($provider == 'github') {
            $this->provider = new GithubProvider([
            'clientId'      => getenv('GITHUB_CLIENT_ID'),
            'clientSecret'  => getenv('GITHUB_CLIENT_SECRET'),
            //'redirectUri'   => $url . '/github',
            'scopes'        => ['user:email', 'read:repo_hook'],
        ]);
            $this->app = $app;
        } else {
            throw new \InvalidArgumentException('Provider <'.$provider.'> not supported');
        }
    }

    public function getCode()
    {
        // If we don't have an authorization code then get one
        $authUrl = $this->provider->getAuthorizationUrl();
        $_SESSION['oauth2state'] = $this->provider->state;
        header('Location: '.$authUrl);
        exit;
    }

    protected function checkState()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
                unset($_SESSION['oauth2state']);
                exit('Invalid state');
            }
        }
    }

    public function getToken($code, $state)
    {
        $this->checkState();

     // Try to get an access token (using the authorization code grant)
        $token = $this->provider->getAccessToken('authorization_code', [
            'code' => $code,
        ]);

        // Optional: Now you have a token you can look up a users profile data
        try {

            // We got an access token, let's now get the user's details
            $userDetails = $this->provider->getUserDetails($token);
            $userDetails->exchangeArray([
                    'email' => $this->provider->getUserEmails($token),
                    'homepage' => $this->provider->domain.'/'.$userDetails->nickname,
                ]);
        } catch (\Exception $e) {
            throw new Exception('cannot fetch account details');
        }
        /* TODOS: implement handling of:
         * $token->refreshToken;
         * $token->expires;
         */
        return $token;
    }

    public function getProvider()
    {
        return $this->provider;
    }
}
