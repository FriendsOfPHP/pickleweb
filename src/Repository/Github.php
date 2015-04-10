<?php

namespace PickleWeb\Repository;

use Composer\Repository as Repository;
use Composer\IO\NullIO;
use Composer\Factory as Factory;

class Github
{
    protected $repository;

    protected $driver;

    protected $information;

    public function __construct($uri)
    {
        $io = new NullIO();
        $config = Factory::createConfig();
        $io->loadConfiguration($config);

        $this->repository = new Repository\VcsRepository(['url' => $uri], $io, $config);
        $driver = $this->vcsDriver = $this->repository->getDriver();
        if (!$driver) {
            throw new Exception('No driver found for <'.$uri.'>');
        }
        $this->driver = $driver;
    }

    public function getInformation()
    {
        return ($this->information = $this->driver->getComposerInformation($this->driver->getRootIdentifier()));
    }

    public function getReleaseTags()
    {
    }
}
