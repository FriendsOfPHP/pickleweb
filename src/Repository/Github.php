<?php

namespace PickleWeb\Repository;

use Composer\Repository as Repository;
use Composer\IO\NullIO;
use Composer\Factory as Factory;
use Composer\Repository\Vcs\GitHubDriver as Github;
use Composer\Package\Version\VersionParser as VersionParser;
use Pickle\Package\JSON\Dumper;
use Pickle\Package;
use Pickle\Package\PHP\Util\ConvertChangelog;

class Github
{
    protected $repository;

    protected $driver;

    protected $information;

    protected $io;
    
    protected $cacheDir;

	protected $url;

    public function __construct($url, $token = '', $cacheDir = false)
    {
		$this->url = $url;
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
		$this->cacheDir = $cacheDir;
        $io->loadConfiguration($config);

		$this->repository = new Repository\VcsRepository(['url' => $url, 'no-api' => false], $io, $config);
		$driver = $this->vcsDriver = $this->repository->getDriver();
		if (!$driver) {
			throw new \Exception('No driver found for <'.$url.'>');
		}
		$this->driver = $driver;

		$client = new \Github\Client();
		$client->authenticate($token, null, \GitHub\Client::AUTH_URL_TOKEN);
		$this->client = $client;
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
                    'source'  => $this->driver->getSource($id)
                ];
            } catch (\Exception $e) {
                continue;
            }
        }

        return $normalizedTags;
    }

    public function getComposerInformation($identifier = null)
    {
		$composerInfo = $this->driver->getComposerInformation($identifier ? $identifier : $this->driver->getRootIdentifier());
		if (!$composerInfo) {
			$composerInfo = $this->convertPackageXml($identifier);
			if (!$composerInfo) {
				return false;
			}
		}

		$composerInfo['source'] = $this->driver->getSource($identifier);
		$composerInfo['dist']   = $this->driver->getDist($identifier);

        return $composerInfo;
    }
    
    protected function convertPackageXml($identifier)
    {
		preg_match('#^(?:(?:https?|git)://([^/]+)/|git@([^:]+):)([^/]+)/(.+?)(?:\.git|/)?$#', $this->url, $match);
        $owner = $match[3];
        $repository = $match[4];

		try {
			$contents = $this->client->api('repo')->contents()->download($owner, $repository, 'package.xml', $identifier);
		} catch (\RuntimeException $e) {
			if ($e->getCode() == 404) {
				return false;
			}
		}

		$packagexmlPath = $this->cacheDir . DIRECTORY_SEPARATOR . 'package.xml';
		file_put_contents($packagexmlPath, $contents);

        $loader = new \Pickle\Package\XML\Loader(new Package\Loader());
        $package = $loader->load($packagexmlPath);
        $package->setRootDir($this->cacheDir);
		$dumper = new \Pickle\Package\Dumper();
		$info = $dumper->dump($package);
		$info['name'] = $owner . '/' . $repository;
		$info['type'] = 'extension';
		return $info;
	}
}
