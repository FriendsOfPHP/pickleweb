<?php

namespace PickleWeb\Controller;

use Composer\IO\BufferIO as BufferIO;

/**
 * Class PackageController.
 */
class PackageController extends ControllerAbstract
{
    protected function updateRootPackageJson()
    {
        $jsonPackages = [
            'packages'          => [],
            'notify'            => '/downloads/%package%',
            'notify-batch'      => '/downloads/',
            'providers-url'     => '/p/%package%$%hash%.json',
            'search'            => '/search.json?q=%query%',
            'provider-includes' => [],
        ];
    }

    /**
     * GET /package/register.
     */
    public function registerAction()
    {
        if ($this->app->request()->get('confirm')) {
            $transaction     = $this->app->request()->get('id');
            $pathTransaction = $this->app->config('cache_dir').'/'.$transaction.'.json';
            if (!file_exists($pathTransaction)) {
                $this->app->redirect('/package/register');
                exit();
            }

            $transaction = file_get_contents($pathTransaction);
            $data = json_decode($transaction, true);

            $packageName = key($data['packages']);

            list($vendorName, $extensionName) = explode('/', $packageName);

            $vendorDir = $this->app->config('json_path').'/'.$vendorName;
            if (!is_dir($vendorDir)) {
                mkdir($vendorDir);
            }

            $sha = hash('sha256', $transaction);

            $jsonPathBase = $vendorDir.'/'.$extensionName;
            $jsonPathSha  = $jsonPathBase.'$'.$sha.'.json';

            file_put_contents($jsonPathSha, $transaction);
            link($jsonPathSha, $jsonPathBase.'.json');

            $pathTransactionLog = substr($pathTransaction, 0, -4).'log';
            if (file_exists($pathTransactionLog)) {
                unlink($pathTransactionLog);
            }
            unlink($pathTransaction);

            $this->app->flash('warning', $packageName.'has been registred');
            $this->app->redirect('/package/'.$packageName);
        } else {
            $this->app
                ->render(
                    'extension/register.html',
                    [
                        'repository' => $this->app->request()->get('repository'),
                    ]
                );
        }
    }

    /**
     * POST /package/register.
     */
    public function registerPackageAction()
    {
        $token = $_SESSION['github.token'];
        $repo  = $this->app->request()->post('repository');

        $log = new BufferIO();

        try {
            $driver = new \PickleWeb\Repository\Github($repo, $token->accessToken, $this->app->config('cache_dir'), $log);

            if ($driver->getOwnerId() != $this->app->user()->getGithubId()) {
                $this->app->flash('error', 'You are not the owner of this repository. Please request the owner to register.');
                $this->app->redirect('/package/register?repository='.$repo);
            }

            $extension = new \PickleWeb\Extension();
            $extension->setFromRepository($driver, $log);

            $vendorName = $extension->getVendor();
            $repository = $extension->getRepositoryName();
            $extensionName = $extension->getName();

            $extension->unserialize($extension->serialize());
            $jsonPackage = $extension->serialize();
        } catch (\RuntimeException $exception) {
            /* todo: handle bad data in a better way =) */
            $this->app->flash('error', 'An error occurred while retrieving extension data. Please try again later.'.$exception->getMessage());
            $this->app->redirect('/package/register?repository='.$repo);
        }

        $vendorDir = $this->app->config('json_path').$vendorName;
        if (file_exists($vendorDir.'/'.$repository.'.json')) {
            $this->app->flash('error', $packageName.' is already registred');
            $this->app->redirect('/package/'.$packageName);
            exit();
        }

        $transaction = hash('sha256', $jsonPackage);

        file_put_contents($this->app->config('cache_dir').'/'.$transaction.'.json', $jsonPackage);
        file_put_contents($this->app->config('cache_dir').'/'.$transaction.'.log', $log->getOutput());
        $latest = $extension->getPackages()['dev-master'];

        $this->app
                ->render(
                    'extension/confirm.html',
                    [
                        'log'         => $log->getOutput(),
                        'transaction' => $transaction,
                        'latest'      => $latest,
                        'tags'        => $extension->getPackages(),
                        'vcs'         => $repo,
                    ]
                );
    }

    /**
     * GET /package/:vendor/:package.
     *
     * @param string $vendor
     * @param string $package
     */
    public function viewPackageAction($vendor, $package)
    {
        $jsonPath = $this->app->config('json_path').$vendor.'/'.$package.'.json';

        $this->app->notFoundIf(file_exists($jsonPath) === false);

        $name = $vendor.'/'.$package;
        $json = json_decode(file_get_contents($jsonPath), true);

        reset($json['packages'][$name]);
        $firstKey = key($json['packages'][$name]);

        $this->app->render(
            'extension/info.html',
            [
                'name'      => $name,
                'extension' => $json['packages'][$name][$firstKey],
                'versions'  => $json['packages'][$vendor.'/'.$package],
            ]
        );
    }
}
