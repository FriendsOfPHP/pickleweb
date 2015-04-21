<?php

namespace PickleWeb\Auth;

use League\OAuth2\Client\Entity\User;
use League\OAuth2\Client\Provider;
use League\OAuth2\Client\Token\AccessToken;
use PickleWeb\Application;

/**
 * Class GithubProvider.
 */
class GithubProvider implements ProviderInterface
{
    const STATE_SESSION_NAME = 'github.oauth2state';

    /**
     * @var Provider\Github
     */
    protected $oauth2Provider;

    /**
     * @param Provider\Github $oauth2Provider
     */
    public function __construct(Provider\Github $oauth2Provider)
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
        try {
            /* @var $user User */
            $user = $this->oauth2Provider->getUserDetails($token);

            // Fix email
            if (empty($user->email)) {
                $emails = array_filter(
                    $this->oauth2Provider->getUserEmails($token),
                    function ($email) {
                        return $email->primary;
                    }
                );

                $user->exchangeArray(['email' => current($emails)->email]);
            }

            return [
                'uid'            => $user->uid,
                'nickname'       => $user->nickname,
                'realname'       => (isset($user->name)) ? $user->name : null,
                'email'          => (isset($user->email)) ? $user->email : null,
                'profilepicture' => $user->imageUrl,
                'homepage'       => $this->oauth2Provider->domain.'/'.$user->nickname,
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException('cannot fetch account details', 0, $e);
        }
    }
}
