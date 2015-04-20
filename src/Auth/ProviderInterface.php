<?php
namespace PickleWeb\Auth;

use League\OAuth2\Client\Token\AccessToken;
use PickleWeb\Application;

/**
 * Interface ProviderInterface
 *
 * @package PickleWeb\Auth
 */
interface ProviderInterface
{
    /**
     * @param Application $app
     *
     * @return AccessToken  token
     */
    public function handleAuth(Application $app);

    /**
     * @param AccessToken $token
     *
     * @return array
     */
    public function getUserDetails(AccessToken $token);
}