<?php

namespace PickleWeb\Auth;

use League\OAuth2\Client\Provider;
use League\OAuth2\Client\Token\AccessToken;
use PickleWeb\Application;

/**
 * Class GoogleProvider.
 */
class GoogleProvider implements ProviderInterface
{
    const STATE_SESSION_NAME = 'google.oauth2state';

    /**
     * @var Provider\Google
     */
    protected $oauth2Provider;

    /**
     * @param Provider\Google $oauth2Provider
     */
    public function __construct(Provider\Google $oauth2Provider)
    {
        $this->oauth2Provider = $oauth2Provider;
    }

    /**
     * @param Application $app
     *
     * @return string token
     */
    public function handleAuth(Application $app)
    {
        $code  = $app->request()->get('code');
        $state = $app->request()->get('state');

        if (is_null($code)) {
            // If we don't have an authorization code then get one

            $url                                = $this->oauth2Provider->getAuthorizationUrl();
            $_SESSION[self::STATE_SESSION_NAME] = $this->oauth2Provider->state;
            $app->redirect($url);
        } elseif (empty($state) || $state !== $_SESSION[self::STATE_SESSION_NAME]) {
            // Check given state against previously stored one to mitigate CSRF attack

            unset($_SESSION[self::STATE_SESSION_NAME]);
            throw new \RuntimeException('Invalid state '.$_SESSION[self::STATE_SESSION_NAME]);
        }

        // clean session
        unset($_SESSION[self::STATE_SESSION_NAME]);

        // Try to get an access token (using the authorization code grant)
        return $this->oauth2Provider->getAccessToken(
            'authorization_code',
            [
                'code' => $code,
            ]
        );
    }

    /**
     * @param AccessToken $token
     *
     * @return array
     */
    public function getUserDetails(AccessToken $token)
    {
        $data = json_decode(file_get_contents('https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token='.$token->accessToken), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
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
