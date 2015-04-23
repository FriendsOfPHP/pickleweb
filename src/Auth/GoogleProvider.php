<?php

namespace PickleWeb\Auth;

use Buzz\Browser;
use League\OAuth2\Client\Provider;
use PickleWeb\Application;
use Predis\Client;

/**
 * Class GoogleProvider.
 */
class GoogleProvider implements ProviderInterface
{
    /**
     * @var Provider\Google
     */
    protected $oauth2Provider;

    /**
     * @var Client
     */
    protected $redisClient;

    /**
     * @var Browser
     */
    protected $httpClient;

    /**
     * @param Provider\Google $oauth2Provider
     */
    public function __construct(Provider\Google $oauth2Provider, Client $redisClient, Browser $httpClient)
    {
        $this->oauth2Provider = $oauth2Provider;
        $this->redisClient    = $redisClient;
        $this->httpClient     = $httpClient;
    }

    /**
     * @param Application $app
     *
     * @return string token
     */
    public function handleAuth(Application $app)
    {
        $code         = $app->request()->get('code');
        $state        = $app->request()->get('state');
        $key          = sprintf('google.oauth2state.%s', session_id());
        $sessionState = $this->redisClient->get($key);

        if (is_null($code)) {
            // If we don't have an authorization code then get one

            $url = $this->oauth2Provider->getAuthorizationUrl();
            $this->redisClient->setex($key, 300, $this->oauth2Provider->state);
            $app->redirect($url);
        } elseif (empty($state) || (isset($sessionState) && $state !== $sessionState)) {
            // Check given state against previously stored one to mitigate CSRF attack

            $this->redisClient->del($key);
            throw new \RuntimeException('Invalid state');
        }

        // clean session
        $this->redisClient->del($key);

        // Try to get an access token (using the authorization code grant)
        return $this->oauth2Provider->getAccessToken(
            'authorization_code',
            [
                'code' => $code,
            ]
        )->accessToken;
    }

    /**
     * @param string $token
     *
     * @return array
     */
    public function getUserDetails($token)
    {
        $data = json_decode($this->httpClient->get('https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token='.$token)->getContent(), true);

        if (empty($data) || json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('cannot fetch account details');
        }

        return [
            'uid'            => $data['id'],
            'nickname'       => $data['given_name'],
            'realname'       => $data['name'],
            'email'          => $data['email'],
            'profilepicture' => $data['picture'],
            'homepage'       => $data['link'],
        ];
    }
}
