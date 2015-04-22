<?php


namespace PickleWeb\Auth;

use Buzz\Browser;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Server\Bitbucket;
use PickleWeb\Application;
use Predis\Client;

/**
 * Class BitbucketProvider.
 */
class BitbucketProvider implements ProviderInterface
{

    /**
     * @var Bitbucket
     */
    protected $oauthProvider;

    /**
     * @var Client
     */
    protected $redisClient;

    /**
     * @var Browser
     */
    protected $httpClient;

    /**
     * @param Bitbucket $oauthProvider
     */
    public function __construct(Bitbucket $oauthProvider, Client $redisClient, Browser $httpClient)
    {
        $this->oauthProvider = $oauthProvider;
        $this->redisClient   = $redisClient;
        $this->httpClient    = $httpClient;
    }

    /**
     * @param Application $app
     *
     * @return string token
     */
    public function handleAuth(Application $app)
    {
        $oauthToken          = $app->request()->get('oauth_token');
        $oauthVerifier       = $app->request()->get('oauth_verifier');
        $key                 = sprintf('bitbucket.oauthCredential.%s', session_id());
        $temporaryCredential = $this->redisClient->get($key);

        if (!empty($temporaryCredential)) {
            $temporaryCredential = unserialize($temporaryCredential);
        }

        if (empty($temporaryCredential)) {
            // If we don't have an authorization code then get one

            $temporaryCredential = $this->oauthProvider->getTemporaryCredentials();
            $this->redisClient->setex($key, 300, serialize($temporaryCredential));
            $app->redirect($this->oauthProvider->getAuthorizationUrl($temporaryCredential));
        } elseif (empty($oauthVerifier) || empty($oauthToken)) {
            // Check callback

            $this->redisClient->del($key);
            throw new \RuntimeException('Invalid state');
        }

        // clean session
        $this->redisClient->del($key);

        $tokenCredentials = $this->oauthProvider->getTokenCredentials($temporaryCredential, $oauthToken, $oauthVerifier);

        return $tokenCredentials->getIdentifier() . '@' . $tokenCredentials->getSecret();
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
            list($identifier, $secret) = explode('@', $token);
            $tokenObject = new TokenCredentials();
            $tokenObject->setIdentifier($identifier);
            $tokenObject->setSecret($secret);

            $url      = 'https://api.bitbucket.org/2.0/user';
            $headers  = $this->oauthProvider->getHeaders($tokenObject, 'GET', $url);
            $response = $this->httpClient->get($url, $headers);
            $data     = json_decode($response->getContent(), true);

            if (empty($data) || json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Json error');
            }

            // Fetch email

            $url      = sprintf('https://api.bitbucket.org/1.0/users/%s/emails', $data['username']);
            $headers  = $this->oauthProvider->getHeaders($tokenObject, 'GET', $url);
            $response = $this->httpClient->get($url, $headers);
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

            $data['email'] = empty($emails) ? '' : current($emails)['email'];

            return [
                'uid'            => $data['uuid'],
                'nickname'       => $data['username'],
                'realname'       => $data['display_name'],
                'email'          => $data['email'],
                'profilepicture' => $data['links']['avatar']['href'],
                'homepage'       => $data['links']['html']['href'],
            ];

        } catch (\Exception $e) {
            throw new \RuntimeException('cannot fetch account details', 0, $e);
        }
    }
}
