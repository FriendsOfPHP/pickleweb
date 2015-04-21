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
    protected $vendor;

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
            $io->write('package: No composer.json or package.xml found for '.($identifier ? $identifier : 'master'));
            throw new \RuntimeException('Master or default branch must have composer.json');
        }

        if ($informationRoot['type'] != 'extension') {
            throw new \RuntimeException($info['name'].' is not an extension package');
        }

        $this->name = $packageName = $informationRoot['name'];

        list($vendorName, $repository) = explode('/', $packageName);
        $this->vendor = $vendorName;
        $this->repositoryName = $repository;

        if (empty($vendorName) || empty($repository)) {
            throw new \RuntimeException($info['name'].' is not a valid name. vendor/repository required as name');
        }

        $packages = [];
        $tmpPackage = new \PickleWeb\Package();

        $tmpPackage->setName($packageName);
        $tmpPackage->setTag('dev-master');
        $tmpPackage->setFromArray($informationRoot);
        $packages['dev-master'] = $tmpPackage;

        $tags    = $driver->getReleaseTags();
        foreach ($tags as $tag) {
            $io->write('package: looking for composer.json for tag '.$tag['version']);

            $information = $driver->getComposerInformation($tag['id']);
            if (!$information) {
                $io->write('package: no composer.json found for tag '.$tag['version'].'ref: '.$tag['id']);
            }

            $information['version_normalized'] = $tag['version'];
            $information['source'] = $tag['source'];

            $tmpPackage = new \PickleWeb\Package();
            $tmpPackage->setName($packageName);
            $tmpPackage->setTag($tag['id']);
            $tmpPackage->setFromArray($information);

            $packages[$tag['tag']] = $tmpPackage;
        }
        $this->data = $packages;
    }

    /**
     * return extension name (vendor/repo)
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
        return $this->vendor;
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
    public function getPackages()
    {
        return $this->data;
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
}
