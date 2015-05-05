<?php

namespace PickleWeb;

use PickleWeb\Entity\Extension as Extension;
use Predis\Client;

class Rest
{
    /**
     * @var Extension
     */
    protected $extension;

    /**
     * @var string
     */
    protected $sha;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @param Extension   $extension
     * @param Application $app
     */
    public function __construct(Extension $extension, Application $app)
    {
        $this->app = $app;
        $this->redis = $this->app->container->get('redis.client');
        $this->extension = $extension;
    }

    /**
     * @param string $sha
     */
    protected function updateRootPackageJson($sha)
    {
        $packages = [
            'packages' => [],
            'notify' => '/downloads/%package%',
            'notify-batch' => '/downloads/',
            'providers-url' => '/p/%package%$%hash%.json',
            'search' => '/search.json?q=%query%',
            'provider-includes' => [],
        ];

        $packagesJsonPath = $this->app->config('web_root_dir').'/packages.json';
        $packages['provider-includes']['/json/providers.json'] = $sha;
        file_put_contents($packagesJsonPath, json_encode($packages));
    }

    /**
     * @return string
     */
    protected function updateProviders()
    {
        $providersJsonPath = $this->app->config('json_path').'/'.'/providers.json';

        if (file_exists($providersJsonPath)) {
            $providers = json_decode(file_get_contents($providersJsonPath), true);
        } else {
            $providers = [];
        }
        $providers['providers'][$this->extension->getName()] = $this->sha;

        $json = json_encode($providers);
        file_put_contents($providersJsonPath, $json);

        return hash('sha256', $json);
    }

    /**
     * @return
     */
    public function update()
    {
        $extensionRepository = $this->app->container->get('extension.repository');

        $vendorDir = $this->app->config('json_path').'/'.$this->extension->getVendor();
        if (!is_dir($vendorDir)) {
            mkdir($vendorDir);
        }

        $jsonPackage = $this->extension->serialize();
        $repositoryName = $this->extension->getRepositoryName();

        $this->sha = hash('sha256', $jsonPackage);

        $jsonPathSha = $vendorDir.'/'.$repositoryName.'$'.$this->sha.'.json';
        file_put_contents($jsonPathSha, $jsonPackage);

        $linkPath = $vendorDir.'/'.$repositoryName.'.json';
        if (file_exists($linkPath)) {
            $targetPath = readlink($linkPath);
            unlink($linkPath);
        }
        symlink($jsonPathSha, $vendorDir.'/'.$repositoryName.'.json');
        $shaProviders = $this->updateProviders();
        $this->updateRootPackageJson($shaProviders);
    }
}
