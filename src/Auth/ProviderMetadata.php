<?php

namespace PickleWeb\Auth;

/**
 * Class ProviderMetadata.
 */
class ProviderMetadata
{
    /**
     * @var string
     */
    protected $uid = '';

    /**
     * @var string
     */
    protected $nickName = '';

    /**
     * @var string
     */
    protected $realName = '';

    /**
     * @var string
     */
    protected $email = '';

    /**
     * @var string
     */
    protected $profilePicture = '';

    /**
     * @var string
     */
    protected $homepage = '';

    /**
     * @var string
     */
    protected $location = '';

    /**
     * @param array $data
     */
    public function __construct(array $data = null)
    {
        if (!is_null($data)) {
            $this->fromArray($data);
        }
    }

    /**
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param string $uid
     *
     * @return ProviderMetadata
     */
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * @return string
     */
    public function getNickName()
    {
        return $this->nickName;
    }

    /**
     * @param string $nickName
     *
     * @return ProviderMetadata
     */
    public function setNickName($nickName)
    {
        $this->nickName = $nickName;

        return $this;
    }

    /**
     * @return string
     */
    public function getRealName()
    {
        return $this->realName;
    }

    /**
     * @param string $realName
     *
     * @return ProviderMetadata
     */
    public function setRealName($realName)
    {
        $this->realName = $realName;

        return $this;
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
     * @return ProviderMetadata
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getProfilePicture()
    {
        return $this->profilePicture;
    }

    /**
     * @param string $profilePicture
     *
     * @return ProviderMetadata
     */
    public function setProfilePicture($profilePicture)
    {
        $this->profilePicture = $profilePicture;

        return $this;
    }

    /**
     * @return string
     */
    public function getHomepage()
    {
        return $this->homepage;
    }

    /**
     * @param string $homepage
     *
     * @return ProviderMetadata
     */
    public function setHomepage($homepage)
    {
        $this->homepage = $homepage;

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
     * @return ProviderMetadata
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return get_object_vars($this);
    }

    /**
     * @param array $data
     */
    public function fromArray(array $data)
    {
        $fields = get_object_vars($this);
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $fields)) {
                $this->$key = $value;
            }
        }
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
