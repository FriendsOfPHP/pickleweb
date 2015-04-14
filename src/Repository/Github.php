<?php

namespace PickleWeb\Repository;

use Composer\Repository as Repository;
use Composer\IO\NullIO;
use Composer\Factory as Factory;
use Composer\Package\Version\VersionParser as VersionParser;

class Github
{
    protected $repository;

    protected $driver;

    protected $information;

    protected $io;

    public function __construct($uri, $token = '', $cacheDir = false)
    {
        $io = new NullIO();
        $this->io = $io;
        $config = Factory::createConfig();
        if ($cacheDir) {
            $config->merge([
                    'config' => [
                        'cache-dir' => $cacheDir,
                        'github-oauth' => ['github.com' => $token],
                        ],
                ]);
        }

        $io->loadConfiguration($config);

        $this->repository = new Repository\VcsRepository(['url' => $uri, 'no-api' => false], $io, $config);
        $driver = $this->vcsDriver = $this->repository->getDriver();
        if (!$driver) {
            throw new \Exception('No driver found for <'.$uri.'>');
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
        uksort($tags, function ($a, $b) {
                    $aVersion = $a;
                    $bVersion = $b;
                    if ($aVersion === '9999999-dev' || 'dev-' === substr($aVersion, 0, 4)) {
                        $aVersion = 'dev';
                    }
                    if ($bVersion === '9999999-dev' || 'dev-' === substr($bVersion, 0, 4)) {
                        $bVersion = 'dev';
                    }
                    $aIsDev = $aVersion === 'dev' || substr($aVersion, -4) === '-dev';
                    $bIsDev = $bVersion === 'dev' || substr($bVersion, -4) === '-dev';
                    // push dev versions to the end
                    if ($aIsDev !== $bIsDev) {
                        return $aIsDev ? 1 : -1;
                    }
                    // equal versions are sorted by date
                    if ($aVersion === $bVersion) {
                        return $a->getReleaseDate() > $b->getReleaseDate() ? 1 : -1;
                    }
                    // the rest is sorted by version
                    return version_compare($aVersion, $bVersion);
                });
        $normalizedTags = [];
        foreach ($tags as $version => $id) {
            try {
                $normalizedVersion = VersionParser::Normalize($version);
                $normalizedTags[] = [
                    'version' => $normalizedVersion,
                    'tag'     => $version,
                    'id'      => $id,
                ];
            } catch (\Exception $e) {
                continue;
            }
        }

        return $normalizedTags;
    }
}
