<?php

namespace PickleWeb\Auth;

use Buzz\Browser;
use League\OAuth2\Client\Provider;
use PickleWeb\Application;
use Predis\Client;

/**
 * Class GithubProvider.
 */
class GithubProvider implements ProviderInterface
{

    /**
     * @var Provider\Github
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
     * @param Provider\Github $oauth2Provider
     */
    public function __construct(Provider\Github $oauth2Provider, Client $redisClient, Browser $httpClient)
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
        $key          = sprintf('github.oauth2state.%s', session_id());
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
        try {

            // Fetch user data
            $response = $this->httpClient->get('https://api.github.com/user', ['Authorization' => sprintf('token %s', $token), 'User-Agent' => 'Pickleweb']);
            $data     = json_decode($response->getContent(), true);

            if (empty($data) || json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Json error');
            }

            // Fetch emails if needed
            if (empty($data['email'])) {
                $response = $this->httpClient->get('https://api.github.com/user/emails', ['Authorization' => sprintf('token %s', $token), 'User-Agent' => 'Pickleweb']);
                $emails   = json_decode($response->getContent(), true);

                if (empty($emails) || json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException('Json error');
                }

                $emails = array_filter(
                    $emails,
                    function ($emailData) {
                        return true === $emailData['primary'];
                    }
                );

                if (!empty($emails)) {
                    $data['email'] = current($emails)['email'];
                }

            }

            return [
                'uid'            => $data['id'],
                'nickname'       => $data['login'],
                'realname'       => $data['name'],
                'email'          => $data['email'],
                'profilepicture' => $data['avatar_url'],
                'homepage'       => $data['html_url'],
            ];

        } catch (\Exception $e) {
            throw new \RuntimeException('cannot fetch account details', 0, $e);
        }
    }
}
