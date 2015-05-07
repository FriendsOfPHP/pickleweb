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
    const EXTENSIONMETA_HASH_STORE = 'extensionmeta';

    /**
     * @var Client
     */
    protected $redisClient;

    /**
     * @param Client $redisClient
     */
    public function __construct(Client $redisClient)
    {
        $this->redisClient = $redisClient;
    }

    /**
     * @param Extension $extension
     * @param User      $extension
     */
    public function persist(Extension $extension, User $user)
    {
        $this->redisClient->hset(self::EXTENSION_HASH_STORE, $extension->getName(), $extension->serialize());
        $this->redisClient->hset(self::EXTENSION2USER_HASH_STORE, $extension->getName(), $user->getName());
        $meta = [
                'watchers' => $extension->getStars(),
                'stars' => $extension->getWatchers(),
            ];
        $this->redisClient->hset(self::EXTENSIONMETA_HASH_STORE, $extension->getName(),
            json_encode($meta)
        );
    }

    /**
     * @param Extension $extension
     */
    public function remove(Extension $extension)
    {
        $id = $extension->getName();
        $this->redisClient->hdel(self::EXTENSION2USER_HASH_STORE, $id);
        $this->redisClient->hdel(self::EXTENSION_HASH_STORE, $id);
        $this->redisClient->hdel(self::EXTENSIONMETA_HASH_STORE, $id);
    }

    /**
     * @param string $name
     *
     * @return Extension|null
     */
    public function find($name)
    {
        $extensionSerialize = $this->redisClient->hget(self::EXTENSION_HASH_STORE, strtolower(trim($name)));
        if (!$extensionSerialize) {
            return;
        }
        $extension = new Extension();
        $extension->unserialize($extensionSerialize);
        $meta = json_decode($this->redisClient->hget(self::EXTENSIONMETA_HASH_STORE, $extension->getName()), true);
        $extension->setWatchers($meta['watchers']);
        $extension->setStars($meta['stars']);

        return empty($extension) ? null : $extension;
    }

    /**
     * @return array|null
     */
    public function getAll()
    {
        $extensionsSerialize = $this->redisClient->hgetall(self::EXTENSION_HASH_STORE);
        if (!$extensionsSerialize) {
            return;
        }

        $result = [];
        foreach ($extensionsSerialize as $serialized) {
            $extension = new Extension();
            $extension->unserialize($serialized);
            $meta = json_decode($this->redisClient->hget(self::EXTENSIONMETA_HASH_STORE, $extension->getName()), true);
            $extension->setWatchers($meta['watchers']);
            $extension->setStars($meta['stars']);
            $result[$extension->getName()] = $extension;
        }

        return $result;
    }
}
