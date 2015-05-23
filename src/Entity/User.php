<?php

namespace PickleWeb\Entity;

use PickleWeb\Auth\ProviderMetadata;

/**
 * Class User
 *
 * @package PickleWeb\Entity
 */
class User implements \Serializable
{

    /**
     * @var string
     */
    protected $email = '';

    /**
     * @var string
     */
    protected $nickname = '';

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var string
     */
    protected $picture = '';

    /**
     * @var string
     */
    protected $location = '';

    /**
     * @var array
     */
    protected $providerMetadata = [];

    /**
     * @var array
     */
    protected $extensions = [];

    /**
     * @return string
     */
    public function getId()
    {
        return strtolower(trim($this->email));
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getNickname()
    {
        return $this->nickname;
    }

    /**
     * @param string $nickname
     *
     * @return User
     */
    public function setNickname($nickname)
    {
        $this->nickname = $nickname;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return User
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getPicture()
    {
        return $this->picture;
    }

    /**
     * @param string $picture
     *
     * @return User
     */
    public function setPicture($picture)
    {
        $this->picture = $picture;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param string $location
     *
     * @return User
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @param string           $provider
     * @param ProviderMetadata $metadata
     *
     * @return $this
     */
    public function addProviderMetadata($provider, ProviderMetadata $metadata)
    {
        $this->providerMetadata[$provider] = $metadata->toArray();

        return $this;
    }

    /**
     * @param string $provider
     *
     * @return ProviderMetadata|null
     */
    public function getProviderMetadata($provider)
    {
        if (!isset($this->providerMetadata[$provider])) {
            return;
        }

        return new ProviderMetadata($this->providerMetadata[$provider]);
    }

    /**
     * @param string $provider
     *
     * @return bool
     */
    public function hasProviderMetadata($provider)
    {
        return array_key_exists($provider, $this->providerMetadata);
    }

    /**
     * @return string
     */
    public function getGithubId()
    {
        $githubMetadata = $this->getProviderMetadata('github');

        return is_null($githubMetadata) ? null : $githubMetadata->getUid();
    }

    /**
     * @return string
     */
    public function getGithubHomepage()
    {
        $githubMetadata = $this->getProviderMetadata('github');

        return is_null($githubMetadata) ? null : $githubMetadata->getHomepage();
    }

    /**
     * @return string
     */
    public function getGoogleId()
    {
        $googleMetadata = $this->getProviderMetadata('google');

        return is_null($googleMetadata) ? null : $googleMetadata->getUid();
    }

    /**
     * @return string
     */
    public function getGoogleHomepage()
    {
        $googleMetadata = $this->getProviderMetadata('google');

        return is_null($googleMetadata) ? null : $googleMetadata->getHomepage();
    }

    /**
     * @return string
     */
    public function getBitbucketId()
    {
        $bitbucketMetadata = $this->getProviderMetadata('bitbucket');

        return is_null($bitbucketMetadata) ? null : $bitbucketMetadata->getUid();
    }

    /**
     * @return string
     */
    public function getBitbucketHomepage()
    {
        $bitbucketMetadata = $this->getProviderMetadata('bitbucket');

        return is_null($bitbucketMetadata) ? null : $bitbucketMetadata->getHomepage();
    }

    /**
     * @param $extensionName
     *
     * @return $this
     */
    public function removeExtension($extensionName)
    {
        $this->extensions = array_filter(
            $this->extensions,
            function ($ext) use ($extensionName) {
                return $ext != $extensionName;
            }
        );

        return $this;
    }

    /**
     * @param $extensionName
     *
     * @return $this
     */
    public function addExtension($extensionName)
    {
        $this->extensions[] = $extensionName;

        return $this;
    }

    /**
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return json_encode(get_object_vars($this));
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $data = json_decode($serialized, true);

        $fields = get_object_vars($this);
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $fields)) {
                $this->$key = $value;
            }
        }
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
