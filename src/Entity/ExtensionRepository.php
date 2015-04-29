<?php

namespace PickleWeb\Entity;

use Predis\Client;

/**
 * Class ExtensionRepository.
 */
class ExtensionRepository
{
    const EXTENSION_HASH_STORE = 'extensions';
    const EXTENSION2USER_HASH_STORE = 'extension2user';

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
     * @param Extension $extension
     * @param User      $extension
     */
    public function persist(Extension $extension, User $user)
    {
        $extensionJson = serialize($extension);
        $this->redicClient->hset(self::EXTENSION_HASH_STORE, $extension->getName(), $extensionJson);
        $this->redicClient->hset(self::EXTENSION2USER_HASH_STORE, $extension->getName(), $user->getName());
    }

    /**
     * @param Extension $extension
     */
    public function remove(Extension $extension)
    {
        $id = $extension->getName();
        $this->redicClient->hdel(self::EXTENSION2USER_HASH_STORE, $id);
        $this->redicClient->hdel(self::EXTENSION_HASH_STORE, $id);
    }

    /**
     * @param string $name
     *
     * @return Extension|null
     */
    public function find($name)
    {
        $extension = $this->redicClient->hget(self::EXTENSION_HASH_STORE, strtolower(trim($name)));

        return empty($extension) ? null : unserialize($extension);
    }
}
