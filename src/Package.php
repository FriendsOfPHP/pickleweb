<?php

namespace PickleWeb;

class Package implements \Serializable
{
    protected $name;
    protected $tag;

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
        'time',
        'support',
        'source',
        'dist',
    ];
    protected $values = [];

    public function __construct()
    {
        foreach ($this->keysExport as $key) {
            $this->values[$key] = '';
        }
    }

    public function setFromArray($data)
    {
        foreach ($data as $key => $value) {
            if (isset($this->values[$key])) {
                $this->values[$key] = $value;
            }
        }
    }

    public function getAsArray()
    {
        return $this->values;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    public function getTag()
    {
        return $this->tag;
    }

    public function getDescription()
    {
        return $this->values['description'];
    }

    public function getLicense()
    {
        return $this->values['license'];
    }

    public function getAuthors()
    {
        return $this->values['authors'];
    }

    public function getVersion()
    {
        return $this->values['version'];
    }

    public function getVersionNormalized()
    {
        return $this->values['version_normalized'];
    }

    public function getTime()
    {
        return $this->values['time'];
    }

    public function getId()
    {
        return $this->values['source']['reference'];
    }

    public function serialize()
    {
        $data = [
            [
                $this->tag => $this->values,
            ],
        ];

        return json_encode($data);
    }

    public function unserialize($serialized)
    {
        $data = json_decode($serialized, true);
        $this->setFromArray($data);
    }
}
