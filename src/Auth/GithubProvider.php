<?php

namespace PickleWeb\Auth;

use League\OAuth2\Client\Entity\User;
use League\OAuth2\Client\Provider;
use League\OAuth2\Client\Token\AccessToken;

/**
 * Class GithubProvider
 *
 * @package PickleWeb\Auth
 */
class GithubProvider extends Provider\Github
{
    /**
     * @param object      $response
     * @param AccessToken $token
     *
     * @return User
     */
    public function userDetails($response, AccessToken $token)
    {
        $user = new User();

        $name  = (isset($response->name)) ? $response->name : null;
        $email = (isset($response->email)) ? $response->email : null;

        return $user->exchangeArray(
            [
                'uid'      => $response->id,
                'nickname' => $response->login,
                'name'     => $name,
                'email'    => $email,
                // @codingStandardsIgnoreStart
                'imageurl' => $response->avatar_url,
                // @codingStandardsIgnoreEnd
                'location' => $response->location,
                'urls'     => [
                    'GitHub' => $this->domain . '/' . $response->login,
                ],
            ]
        );
    }
}
