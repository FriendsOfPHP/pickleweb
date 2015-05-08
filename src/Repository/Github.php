<?php

namespace PickleWeb\Repository;

use Composer\Factory as Factory;
use Composer\IO\BufferIO;
use Composer\IO\NullIO;
use Composer\Package\Version\VersionParser as VersionParser;
use Composer\Repository as Repository;
use Pickle\Package;

/**
 * Class Github.
 */
class Github
{
    /**
     * @var Repository\VcsRepository
     */
    protected $repository;

    /**
     * @var Repository\Vcs\GitHubDriver
     */
    protected $driver;

    /**
     * @var NullIO
     */
    protected $io;

    /**
     * @var bool
     */
    protected $cacheDir;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $vendorName;

    /**
     * @var string
     */
    protected $repositoryName;

    /**
     * @var string
     */
    protected $repositoryMeta = false;

    /**
     * @var null|BufferIO
     */
    protected $log;

    /**
     * @param string $url
     * @param string $token
     * @param bool   $cacheDir
     * @param null   $bufferIO
     *
     * @throws \Exception
     */
    public function __construct($url, $token = '', $cacheDir = false, $bufferIO = null)
    {
        $this->url = $url;
        $this->io = new NullIO();
        $this->log = $bufferIO ? $bufferIO : new BufferIO();

        $config = Factory::createConfig();
        $cfg = ['config' => []];
        if ($cacheDir) {
            $cfg['config']['cache-dir'] = $cacheDir;
        }
        if ($token) {
            $cfg['config']['github-oauth'] = ['github.com' => $token];
        }

        $config->merge($cfg);
        $this->cacheDir = $cacheDir;
        $this->io->loadConfiguration($config);

        $this->repository = new Repository\VcsRepository(['url' => $url, 'no-api' => false], $this->io, $config);
        $driver = $this->vcsDriver = $this->repository->getDriver();
        if (!$driver) {
            throw new \Exception('No driver found for <'.$url.'>');
        }
        $this->driver = $driver;

        $client = new \Github\Client();
        $client->authenticate($token, null, \GitHub\Client::AUTH_URL_TOKEN);
        $this->client = $client;

        preg_match('#^(?:(?:https?|git)://([^/]+)/|git@([^:]+):)([^/]+)/(.+?)(?:\.git|/)?$#', $url, $match);
        $this->vendorName = $match[3];
        $this->repositoryName = $match[4];
    }

    public function getOwnerId()
    {
        if (!$this->repositoryMeta) {
            $client = new \Github\Client();
            $meta = $client->api('repo')->show($this->vendorName, $this->repositoryName);
            $this->repositoryMeta = $meta;
        }

        return $this->repositoryMeta['owner']['id'];
    }

    public function getStars()
    {
        return $this->repositoryMeta['stargazers_count'];
    }

    public function getWatchers()
    {
        return $this->repositoryMeta['watchers_count'];
    }

    /**
     * @return array
     */
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
                    $res = version_compare($aVersion, $bVersion);
                    if ($res == 1) {
                        return -1;
                    }
                    if ($res == 0) {
                        return 0;
                    }
                    if ($res == -1) {
                        return 1;
                    }

                    return $res;
        });

        $versionParser = new VersionParser();
        $normalizedTags = [];
        foreach ($tags as $version => $id) {
            try {
                $normalizedVersion = $versionParser->normalize($version);
            } catch (\Exception $e) {
                /* We just continue, repo can have any tags, we just care about valid semver one */
                continue;
            }
            $tmp = [
                    'version' => $normalizedVersion,
                    'tag' => $version,
                    'id' => $id,
                    'source' => $this->driver->getSource($id),
                ];

            $normalizedTags[] = $tmp;
        }

        return $normalizedTags;
    }

    /**
     * @param string|null $identifier
     *
     * @return array|bool
     */
    public function getComposerInformation($identifier = null)
    {
        $composerInfo = $this->driver->getComposerInformation($identifier ? $identifier : $this->driver->getRootIdentifier());
        if (!$composerInfo) {
            $this->log->write('github driver: no composer.json found for '.($identifier ? $identifier : 'master'));
            $composerInfo = $this->convertPackageXml($identifier);
            if (!$composerInfo) {
                $this->log->write('github driver: no package2.xml or package.xml found for '.($identifier ? $identifier : 'master'));

                return false;
            }
        }

        $composerInfo['source'] = $this->driver->getSource($identifier);
        $composerInfo['dist'] = $this->driver->getDist($identifier);

        return $composerInfo;
    }

    /**
     * @param string $identifier
     *
     * @return array|bool
     */
    protected function convertPackageXml($identifier)
    {
        $packageXmlNames = [
            'package.xml',
            'package2.xml',
        ];
        $found = false;
        foreach ($packageXmlNames as $path) {
            try {
                $contents = $this->client->api('repo')->contents()->download($this->vendorName, $this->repositoryName, $path, $identifier);
            } catch (\RuntimeException $e) {
                if ($e->getCode() == 404) {
                    $this->log->write('github driver: no '.$path.' found for '.$identifier);
                    continue;
                }
            }
            $found = true;
            break;
        }

        if (!$found || !isset($contents)) {
            return false;
        }

        $packagexmlPath = $this->cacheDir.DIRECTORY_SEPARATOR.'package.xml';
        file_put_contents($packagexmlPath, $contents);

        $loader = new \Pickle\Package\XML\Loader(new Package\Loader());
        $package = $loader->load($packagexmlPath);
        $package->setRootDir($this->cacheDir);
        $dumper = new \Pickle\Package\Dumper();
        $xml = simplexml_load_file($packagexmlPath);

        $date = $xml->date;
        $time = $xml->time;

        $info = $dumper->dump($package);
        $info['name'] = $this->vendorName.'/'.$this->repositoryName;
        $info['type'] = 'extension';
        $info['time'] = date('Y-m-d H:i', strtotime($date.' '.$time));

        unlink($packagexmlPath);

        return $info;
    }

    protected function getRepositoryNameAndVendorName($url)
    {
        preg_match('#^(?:(?:https?|git)://([^/]+)/|git@([^:]+):)([^/]+)/(.+?)(?:\.git|/)?$#', $url, $match);
        $owner = $match[3];
        $repository = $match[4];

        return [
            'vendor' => $owner,
            '$repository' => $repository,
            ];
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
