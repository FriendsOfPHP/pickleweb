<?php

namespace PickleWeb\Repository;

use Composer\Repository as Repository;
use Composer\IO\NullIO;
use Composer\IO\BufferIO;
use Composer\Factory as Factory;

class Github
{
    protected $repository;

    protected $driver;

    protected $information;

    protected $io;

    public function __construct($uri, $cacheDir = false)
    {
        $io = new NullIO();
        $io = new BufferIO();
        $this->io = $io;
        $config = Factory::createConfig();
        if ($cacheDir) {
            $config->merge(['config' => ['cache-dir' => $cacheDir]]);
        }

        $io->loadConfiguration($config);

        var_dump($config->get('cache-dir'));
        $this->repository = new Repository\VcsRepository(['url' => $uri, 'no-api' => false], $io, $config);
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
        $tags = $this->driver->getTags();

        return $tags;
    }
}
