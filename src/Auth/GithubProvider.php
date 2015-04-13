<?php

namespace PickleWeb\Auth;

use League\OAuth2\Client\Entity\User;
use League\OAuth2\Client\Provider;
use League\OAuth2\Client\Token\AccessToken;

class GithubProvider extends Provider\Github
{
    /**
     * @param object                                  $response
     * @param \League\OAuth2\Client\Token\AccessToken $token
     *
     * @return \League\OAuth2\Client\Entity\User
     */
    public function userDetails($response, AccessToken $token)
    {
        $user = new User();

        $name = (isset($response->name)) ? $response->name : null;
        $email = (isset($response->email)) ? $response->email : null;

        return $user->exchangeArray([
                'uid' => $response->id,
                'nickname' => $response->login,
                'name' => $name,
                'email' => $email,
                'imageurl' => $response->avatar_url,
                'location' => $response->location,
                'urls'  => [
                    'GitHub' => $this->domain.'/'.$response->login,
                ],
            ]
        );
    }
}
