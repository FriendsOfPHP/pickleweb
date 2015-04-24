<?php

namespace PickleWeb;

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
    protected $repositoryName;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var string
     */
    protected $repositoryUrl;

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

        if (!$informationRoot) {
            $io->write('package: No composer.json or package.xml found for master');
            throw new \RuntimeException('Master or default branch must have composer.json');
        }

        if ($informationRoot['type'] != 'extension') {
            throw new \RuntimeException($info['name'].' is not an extension package');
        }

        $this->name = $packageName = $informationRoot['name'];

        list($vendorName, $repository) = explode('/', $packageName);
        $this->vendorName = $vendorName;
        $this->repositoryName = $repository;

        if (empty($vendorName) || empty($repository)) {
            throw new \RuntimeException($info['name'].' is not a valid name. vendor/repository required as name');
        }

        $this->data = [];

        $tmpPackage = new \PickleWeb\Package();
        $tmpPackage->setName($packageName);
        $tmpPackage->setTag('dev-master');
        $tmpPackage->setFromArray($informationRoot);
        $this->data['dev-master'] = $tmpPackage;

        $tags    = $driver->getReleaseTags();

        foreach ($tags as $tag) {
            $io->write('package: looking for composer.json for tag '.$tag['version'].'/'.$tag['tag']);
            $information = $driver->getComposerInformation($tag['id']);
            if (!$information) {
                $io->write('package: no composer.json found for tag '.$tag['version'].'ref: '.$tag['id']);
            } else {
                $io->write('...found');
            }

            $information['version_normalized'] = $tag['version'];
            $information['source'] = $tag['source'];

            $tmpPackage = new \PickleWeb\Package();
            $tmpPackage->setName($packageName);
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
     * @return string
     */
    public function getRepositoryName()
    {
        return $this->repositoryName;
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
        $exportData =  [
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
            $tmpPackage = new \PickleWeb\Package();
            $tmpPackage->setName($packageName);
            $tmpPackage->setTag($version);
            $tmpPackage->setFromArray($info);
            $this->data[$version] = $tmpPackage;
        }
        $this->name = $packageName;
    }

    public function getApiKey($app)
    {
        $redis = $app->container->get('redis.client');
        $key = $redis->hget('extension_apikey', $this->vendorName.'_'.$this->repositoryName);
        if (!$key) {
            $key = bin2hex(openssl_random_pseudo_bytes(32));
            $key .= $app->config('apiSecret');
            $key = hash('sha256', $key);
            $redis->hset('extension_apikey', $this->vendorName.'_'.$this->repositoryName, $key);
        }

        return $key;
    }
}
