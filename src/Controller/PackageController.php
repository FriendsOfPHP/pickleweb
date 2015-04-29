<?php

namespace PickleWeb\Controller;

use Composer\IO\BufferIO as BufferIO;
use PickleWeb\Entity\ExtensionRepository as ExtensionRepository;
use PickleWeb\Entity\Extension as Extension;
use PickleWeb\Entity\UserRepository as UserRepository;

/**
 * Class PackageController.
 */
class PackageController extends ControllerAbstract
{
    protected function updateRootPackageJson($json)
    {
        $packages = [
            'packages'          => [],
            'notify'            => '/downloads/%package%',
            'notify-batch'      => '/downloads/',
            'providers-url'     => '/p/%package%$%hash%.json',
            'search'            => '/search.json?q=%query%',
            'provider-includes' => [],
        ];

        $packagesJsonPath = $this->app->config('web_root_dir').'/packages.json';
        $packages['provider-includes']['/json/providers.json'] = hash('sha256', $json);
        file_put_contents($packagesJsonPath, json_encode($packages));
    }

    protected function checkOwnerShip(Extension $extension)
    {
        if (!in_array($extension->getName(), $this->app->user()->getExtensions())) {
            $this->app->flash('error', 'You do now mange this '.$name);
            $this->app->redirect('/profile');
            exit();
        }
    }

    public function removeAction($vendor, $extension)
    {
        $name = $vendor.'/'.$extension;
        $jsonPathBase = $this->app->config('json_path').'/'.$name;

        $shaFile = readlink($jsonPathBase.'.json');

        $redis = $this->app->container->get('redis.client');

        $extensionRepository = new ExtensionRepository($redis);
        $extension = $extensionRepository->find($name);

        if (!$extension) {
            $this->app->flash('error', 'Extension '.$name.' does not exist');
            $this->app->redirect('/profile');
            exit();
        }

        $this->checkOwnerShip($extension);

        $userRepository = new UserRepository($redis);
        $user = $this->app->user();
        $user->removeExtension($name);
        $userRepository->persist($user);
        $extensionRepository->remove($extension);
        unlink($shaFile);
        unlink($jsonPathBase.'.json');

        $this->app->flash('werning', 'Extension '.$name.' has been removed');
        $this->app->redirect('/profile');
    }

    public function removeConfirmAction($vendor, $package)
    {
        $name = $vendor.'/'.$package;
        $redis = $this->app->container->get('redis.client');
        $extensionRepository = new ExtensionRepository($redis);
        $extension = $extensionRepository->find($name);
        $this->checkOwnerShip($extension);

        if (!$extension) {
            $this->app->flash('error', 'Extension '.$name.' does not exist');
            $this->app->redirect('/profile');
            exit();
        }

        $this->app
            ->render(
                'extension/removeConfirm.html',
                [
                    'name' => $name,
                ]
            );
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
            symlink($jsonPathSha, $jsonPathBase.'.json');

            $pathTransactionLog = substr($pathTransaction, 0, -4).'log';
            if (file_exists($pathTransactionLog)) {
                unlink($pathTransactionLog);
            }
            unlink($pathTransaction);

            $user = $this->app->user();
            $user->addExtension($packageName);
            $userRepository = $this->app->container->get('user.repository');
            $userRepository->persist($user);

            $redis = $this->app->container->get('redis.client');

            $extensionRepository = new ExtensionRepository($redis);
            $extension = new Extension();
            $extension->unserialize($transaction);
            $extensionRepository->persist($extension, $user);

            $providersJsonPath = $this->app->config('json_path').'/'.'/providers.json';

            if (file_exists($providersJsonPath)) {
                $providers = json_decode(file_get_contents($providersJsonPath), true);
            } else {
                $providers = [];
            }
            $providers['providers'][$packageName] = $sha;

            $json = json_encode($providers);
            file_put_contents($providersJsonPath, $json);

            $this->updateRootPackageJson($json);

            $this->app->flash('warning', $packageName.' has been registred');
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
            $driver = new \PickleWeb\Repository\Github($repo, $token, $this->app->config('cache_dir'), $log);

            if ($driver->getOwnerId() != $this->app->user()->getGithubId()) {
                $this->app->flash('error', 'You are not the owner of this repository. Please request the owner to register.');
                $this->app->redirect('/package/register?repository='.$repo);
            }

            $extension = new \PickleWeb\Entity\Extension();
            $extension->setFromRepository($driver, $log);

            $vendorName = $extension->getVendor();
            $repository = $extension->getRepositoryName();
            $extensionName = $extension->getName();

            $jsonPackage = $extension->serialize();
        } catch (\RuntimeException $exception) {
            /* todo: handle bad data in a better way =) */
            $this->app->flash('error', 'An error occurred while retrieving extension data. Please try again later.'.$exception->getMessage());
            $this->app->redirect('/package/register?repository='.$repo);
        }

        $vendorDir = $this->app->config('json_path').$vendorName;
        if (file_exists($vendorDir.'/'.$repository.'.json')) {
            $this->app->flash('error', $vendorName.'/'.$repository.' is already registred');
            $this->app->redirect('/package/'.$vendorName.'/'.$repository);
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
        $name = $vendor.'/'.$package;

        $redis = $this->app->container->get('redis.client');
        $extensionRepository = new ExtensionRepository($redis);
        $extension = $extensionRepository->find($name);

        $this->app->notFoundIf($extension == null);

        $this->app->render(
            'extension/info.html',
            [
                'name'      => $name,
                'extension' => $extension->getPackages('dev-master'),
                'versions'  => $extension->getPackages(),
            ]
        );
    }
}
