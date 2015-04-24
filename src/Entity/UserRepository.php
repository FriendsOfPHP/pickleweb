<?php

namespace PickleWeb\Entity;

use Predis\Client;
use Predis\Transaction\MultiExec;

/**
 * Class UserRepository.
 */
class UserRepository
{
    const USER_HASH_STORE = 'users';

    /**
     * @var Client
     */
    protected $redicClient;

    /**
     * @param Client $redicClient
     */
    public function __construct(Client $redicClient)
    {
        $this->redicClient = $redicClient;
    }

    /**
     * @param User $user
     */
    public function persist(User $user)
    {
        $this->redicClient->transaction(
            function (MultiExec $tx) use ($user) {
                $id = $user->getId();
                $tx->hset(self::USER_HASH_STORE, $id, serialize($user));

                if (!empty($user->getGithubId())) {
                    $tx->hset('github_'.self::USER_HASH_STORE, $user->getGithubId(), $id);
                }

                if (!empty($user->getGoogleId())) {
                    $tx->hset('google_'.self::USER_HASH_STORE, $user->getGoogleId(), $id);
                }

                if (!empty($user->getBitbucketId())) {
                    $tx->hset('bitbucket_'.self::USER_HASH_STORE, $user->getBitbucketId(), $id);
                }
            }
        );
    }

    /**
     * @param User $user
     */
    public function remove(User $user)
    {
        $this->redicClient->transaction(
            function (MultiExec $tx) use ($user) {
                $id = $user->getId();
                $tx->hdel(self::USER_HASH_STORE, $id);

                if (!empty($user->getGithubId())) {
                    $tx->hdel('github_'.self::USER_HASH_STORE, $user->getGithubId());
                }

                if (!empty($user->getGoogleId())) {
                    $tx->hdel('google_'.self::USER_HASH_STORE, $user->getGoogleId());
                }

                if (!empty($user->getBitbucketId())) {
                    $tx->hdel('bitbucket_'.self::USER_HASH_STORE, $user->getBitbucketId());
                }
            }
        );
    }

    /**
     * @param string $email
     *
     * @return User|null
     */
    public function find($email)
    {
        $user = $this->redicClient->hget(self::USER_HASH_STORE, strtolower(trim($email)));

        return empty($user) ? null : unserialize($user);
    }

    /**
     * @param string $provider
     * @param string $id
     *
     * @return null|User
     */
    public function findByProviderId($provider, $id)
    {
        $email = $this->redicClient->hget($provider.'_'.self::USER_HASH_STORE, $id);

        return empty($email) ? null : $this->find($email);
    }

    /**
     * @param string $provider
     * @param string $id
     *
     * @return null|User
     */
    public function findByProviderApiKey($provider, $key)
    {
        $email = $this->redicClient->hget($provider.'_API_'.self::USER_HASH_STORE, $id);

        return empty($email) ? null : $this->find($email);
    }

    /**
     * @return null|array
     */
    public function getExtensions()
    {
        $email = $this->redicClient->hget('extension2users', $id);

        return empty($email) ? null : $this->find($email);
    }
}
