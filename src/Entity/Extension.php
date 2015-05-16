<?php

namespace PickleWeb\Entity;

/**
 * Class Extension.
 */
class Extension
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $vendorName;

    /**
     * @var string
     */
    protected $packageName;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var string
     */
    protected $repositoryUrl;

    /**
     * @var int
     */
    protected $starsCount;

    /**
     * @var int
     */
    protected $watchersCount;

    protected function convertTime($time)
    {
        return date('Y-m-d H:i:s', strtotime($time));
    }

    /**
     * @param Repository\Vcs\GitHubDriver $driver
     * @param BufferIO                    $io
     *
     * @throws \Exception
     */
    public function setFromRepository($driver, $io)
    {
        if (!($driver instanceof \PickleWeb\Repository\Github)) {
            throw new \RuntimeException(get_class($driver).' not supported');
        }
        $informationRoot = $driver->getComposerInformation();
        $informationRoot['time'] = $this->convertTime($informationRoot['time']);
        if (!$informationRoot) {
            $io->write('package: No composer.json or package.xml found for master');
            throw new \RuntimeException('Master or default branch must have composer.json');
        }

        if ($informationRoot['type'] != 'extension') {
            throw new \RuntimeException($informationRoot['name'].' is not an extension package');
        }

        $this->name = $name = $informationRoot['name'];

        list($this->vendorName, $this->packageName) = explode('/', $name);

        $this->starCount = $driver->getStars();
        $this->watcherCount = $driver->getWatchers();

        if (empty($this->vendorName) || empty($this->packageName)) {
            throw new \RuntimeException($info['name'].' is not a valid name. vendor/repository required as name');
        }

        $this->data = [];

        $tmpPackage = new Package();
        $tmpPackage->setName($name);
        $tmpPackage->setTag('dev-master');
        $tmpPackage->setFromArray($informationRoot);
        $this->data['dev-master'] = $tmpPackage;

        $tags = $driver->getReleaseTags();
        foreach ($tags as $tag) {
            $io->write('package: looking for composer.json for tag '.$tag['version'].'/'.$tag['tag']);
            try {
                $information = $driver->getComposerInformation($tag['id']);
            } catch (\Exception $e) {
                throw new \RuntimeException('Error importing '.$tag['version'].' '.$e->getMessage());
            }

            if (!$information) {
                $io->write('package: no composer.json found for tag '.$tag['version'].'ref: '.$tag['id']);
                continue;
            } else {
                $io->write('...found');
            }

            $information['version_normalized'] = $tag['version'];
            $information['source'] = $tag['source'];
            $information['time'] = $this->convertTime($information['time']);
            $tmpPackage = new Package();
            $tmpPackage->setName($name);
            $tmpPackage->setTag($tag['id']);
            $tmpPackage->setFromArray($information);
            $this->data[$tag['tag']] = $tmpPackage;
        }
    }

    /**
     * return extension name (vendor/repo).
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getVendor()
    {
        return $this->vendorName;
    }

    /**
     * @return array
     */
    public function getKeywords()
    {
        reset($this->data);

        return current($this->data)->getKeywords();
    }

    public function getDescription()
    {
        reset($this->data);

        return current($this->data)->getDescription();
    }

    /**
     * @return int
     */
    public function getStars()
    {
        return $this->starsCount;
    }

    public function setStars($count)
    {
        $this->starsCount = $count > 0 ? $count : 0;
    }

    public function getWatchers()
    {
        return $this->watchersCount;
    }

    public function setWatchers($count)
    {
        $this->watchersCount = $count > 0 ? $count : 0;
    }

    /**
     * @return string
     */
    public function getPackageName()
    {
        return $this->packageName;
    }

    /**
     * @return array
     */
    public function getVersions()
    {
        return array_keys($this->data);
    }

    /**
     * @return array
     */
    public function getPackages($tag = false)
    {
        if ($tag) {
            return $this->data[$tag];
        } else {
            return $this->data;
        }
    }

    /**
     * @return string
     */
    public function serialize()
    {
        $exportData = [
            'packages' => [$this->name => []],
        ];

        $extension = &$exportData['packages'][$this->name];

        foreach ($this->data as $version => $info) {
            $extension[$version] = $info->getAsArray();
        }

        return json_encode($exportData);
    }

    /**
     * @param array $data
     */
    public function unserialize($data)
    {
        $data = json_decode($data, true);
        $packageName = key($data['packages']);
        $packages = &$data['packages'][$packageName];

        foreach ($packages as $version => $info) {
            $tmpPackage = new Package();
            $tmpPackage->setName($packageName);
            $tmpPackage->setTag($version);
            $tmpPackage->setFromArray($info);
            $this->data[$version] = $tmpPackage;
        }
        $this->name = $packageName;
        list($this->vendorName, $this->packageName) = explode('/', $packageName);
    }

    /**
     * @param Predis\Client $redis
     */
    public function getApiKey(\PickleWeb\Application $app)
    {
        $redis = $app->container->get('redis.client');
        $key = $redis->hget('extension_apikey', $this->getName());
        if (!$key) {
            $key = bin2hex(openssl_random_pseudo_bytes(32));
            $key .= $app->config('apiSecret');
            $key = hash('sha256', $key);
            $res = $redis->hset('extension_apikey', $this->getName(), $key);
        }

        return $key;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
