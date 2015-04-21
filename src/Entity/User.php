<?php

namespace PickleWeb\Entity;

/**
 * Class User.
 */
class User implements \Serializable
{
    /**
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $nickname;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $picture;

    /**
     * @var string
     */
    protected $githubId;

    /**
     * @var string
     */
    protected $githubHomepage;

    /**
     * @var string
     */
    protected $googleId;

    /**
     * @var string
     */
    protected $googleHomepage;

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
    public function getGithubId()
    {
        return $this->githubId;
    }

    /**
     * @param string $githubId
     *
     * @return User
     */
    public function setGithubId($githubId)
    {
        $this->githubId = $githubId;

        return $this;
    }

    /**
     * @return string
     */
    public function getGithubHomepage()
    {
        return $this->githubHomepage;
    }

    /**
     * @param string $githubHomepage
     *
     * @return User
     */
    public function setGithubHomepage($githubHomepage)
    {
        $this->githubHomepage = $githubHomepage;

        return $this;
    }

    /**
     * @return string
     */
    public function getGoogleId()
    {
        return $this->googleId;
    }

    /**
     * @param string $googleId
     *
     * @return User
     */
    public function setGoogleId($googleId)
    {
        $this->googleId = $googleId;

        return $this;
    }

    /**
     * @return string
     */
    public function getGoogleHomepage()
    {
        return $this->googleHomepage;
    }

    /**
     * @param string $googleHomepage
     *
     * @return User
     */
    public function setGoogleHomepage($googleHomepage)
    {
        $this->googleHomepage = $googleHomepage;

        return $this;
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
