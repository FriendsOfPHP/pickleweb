<?php

namespace PickleWeb\Entity;

/**
 * Class Github.
 */
class Package implements \Serializable
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $tag;

    /**
     * @var array
     *            Define public serializable keys
     */
    protected $keysExport = [
        'name',
        'version',
        'version_normalized',
        'type',
        'stability',
        'license',
        'homepage',
        'authors',
        'description',
        'keywords',
        'time',
        'support',
        'source',
        'dist',
    ];

    /**
     * @var array
     *            Store values for public serializable keys
     */
    protected $values = [];

    /**
     * initalize public values.
     */
    public function __construct()
    {
        foreach ($this->keysExport as $key) {
            $this->values[$key] = '';
        }
    }

    /**
     * @param string $data
     */
    public function setFromArray($data)
    {
        foreach ($data as $key => $value) {
            if (isset($this->values[$key])) {
                $this->values[$key] = $value;
            }
        }
    }

    /**
     * @return array
     */
    public function getAsArray()
    {
        return $this->values;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $tag
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    /**
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->values['description'];
    }

    /**
     * @return array
     */
    public function getLicense()
    {
        return $this->values['license'];
    }

    /**
     * @return array
     */
    public function getAuthors()
    {
        return $this->values['authors'];
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->values['version'];
    }

    /**
     * @return string
     */
    public function getVersionNormalized()
    {
        return $this->values['version_normalized'];
    }

    /**
     * @return null|array
     */
    public function getKeywords()
    {
        return $this->values['keywords'];
    }

    /**
     * @return string
     */
    public function getTime()
    {
        return $this->values['time'];
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->values['source']['reference'];
    }

    /**
     * @return string
     */
    public function serialize()
    {
        $data = [
            [
                $this->tag => $this->values,
            ],
        ];

        return json_encode($data);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = json_decode($serialized, true);
        $this->setFromArray($data);
    }
}
