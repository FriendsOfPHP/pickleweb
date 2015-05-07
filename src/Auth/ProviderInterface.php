<?php

namespace PickleWeb\Auth;

use League\OAuth2\Client\Token\AccessToken;
use PickleWeb\Application;

/**
 * Interface ProviderInterface.
 */
interface ProviderInterface
{
    /**
     * @param Application $app
     *
     * @return AccessToken token
     */
    public function handleAuth(Application $app);

    /**
     * @param string $token
     *
     * @return ProviderMetadata
     */
    public function getUserDetails($token);
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
