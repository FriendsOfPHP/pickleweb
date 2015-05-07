<?php

namespace PickleWeb\Controller;

use Composer\IO\BufferIO as BufferIO;
use PickleWeb\Entity\Extension as Extension;
use PickleWeb\Entity\Package as Package;
use PickleWeb\Entity\UserRepository as UserRepository;
use PickleWeb\Rest as Rest;

/**
 * Class PackageController.
 */
class PackageController extends ControllerAbstract
{
    protected $showKey = false;

    /**
     * @param Extension $extension
     */
    protected function checkOwnerShip(Extension $extension)
    {
        if (!in_array($extension->getName(), $this->app->user()->getExtensions())) {
            $this->app->flash('error', 'You do now mange this '.$extension->getName());
            $this->app->redirect('/profile');
            exit();
        }
    }

    /**
     * @param string $name
     *
     * @return Extension
     */
    protected function getExtension($name)
    {
        $extensionRepository = $this->app->container->get('extension.repository');
        $extension = $extensionRepository->find($name);

        if (!$extension) {
            $this->app->flash('error', 'Extension '.$name.' does not exist');
            $this->app->redirect('/profile');
            exit();
        }

        return $extension;
    }

    /**
     * GET /package/:vendor/:package/getapikey.
     *
     * @param string $vendor
     * @param string $package
     */
    public function getApiKey($vendor, $extension)
    {
        $name = $vendor.'/'.$extension;
        $extension = $this->getExtension($name);
        $this->checkOwnerShip($extension);
        $redis = $this->app->container->get('redis.client');
        if (!$extension->getApiKey($this->app)) {
            $this->app->flash('error', 'Failed to generate key for '.$name);
        } else {
            $this->app->flash('warning', 'key for '.$name.'has been generated');
        }
        $this->app->redirect('/package/'.$name);
    }

    /**
     * GET /package/:vendor/:package/showapikey.
     *
     * @param string $vendor
     * @param string $extension
     */
    public function showApiKey($vendor, $extension)
    {
        $this->showKey = true;
        $this->viewPackageAction($vendor, $extension);
    }

    /**
     * POST /package/:vendor/:package/remove.
     *
     * @param string $vendor
     * @param string $extension
     */
    public function removeAction($vendor, $extension)
    {
        $name = $vendor.'/'.$extension;

        $redis = $this->app->container->get('redis.client');
        $extension = $this->getExtension($name);

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
        $extensionRepository = $this->app->container->get('extension.repository');
        $extensionRepository->remove($extension);

        $jsonPathBase = $this->app->config('json_path').'/'.$name;
        $shaFile = readlink($jsonPathBase.'.json');

        unlink($shaFile);
        unlink($jsonPathBase.'.json');

        $this->app->flash('werning', 'Extension '.$name.' has been removed');
        $this->app->redirect('/profile');
    }

    /**
     * GET /package/:vendor/:package/remove.
     *
     * @param string $vendor
     * @param string $package
     */
    public function removeConfirmAction($vendor, $package)
    {
        $name = $vendor.'/'.$package;
        $redis = $this->app->container->get('redis.client');
        $extension = $this->getExtension($name);
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
        if (!$this->app->request()->get('confirm')) {
            $this->app
                ->render(
                    'extension/register.html',
                    [
                        'repository' => $this->app->request()->get('repository'),
                    ]
                );

            return;
        }

        $transaction = $this->app->request()->get('id');
        $pathTransaction = $this->app->config('cache_dir').'/'.$transaction.'.json';
        $pathMetaTransaction = $this->app->config('cache_dir').'/'.$transaction.'_meta.json';
        if (!file_exists($pathTransaction)) {
            $this->app->flash('error', 'No active registration process');
            $this->app->redirect('/package/register');
            exit();
        }

        $serializeExtension = file_get_contents($pathTransaction);
        unlink($pathTransaction);
        $extension = new Extension();
        $extension->unserialize($serializeExtension);

        $serializeExtensionMeta = file_get_contents($pathMetaTransaction);
        unlink($pathMetaTransaction);

        $extensionMeta = json_decode($serializeExtensionMeta, true);
        $extension->setWatchers($extensionMeta['watchers']);
        $extension->setStars($extensionMeta['stars']);

        $packageName = $extension->getName();
        $vendorName = $extension->getVendor();
        $extensionName = $extension->getRepositoryName();

        $pathTransactionLog = $transaction.'log';
        if (file_exists($pathTransactionLog)) {
            unlink($pathTransactionLog);
        }

        $user = $this->app->user();
        $user->addExtension($packageName);
        $userRepository = $this->app->container->get('user.repository');
        $userRepository->persist($user);

        $redis = $this->app->container->get('redis.client');
        $extensionRepository = $this->app->container->get('extension.repository');
        $extensionRepository->persist($extension, $user);

        $rest = new Rest($extension, $this->app);
        $rest->update();

        $this->app->flash('warning', $packageName.' has been registred');
        $this->app->redirect('/package/'.$packageName);
    }

    /**
     * POST /package/register.
     */
    public function registerPackageAction()
    {
        $token = $_SESSION['github.token'];
        $repo = $this->app->request()->post('repository');
        $log = new BufferIO();

        try {
            $driver = new \PickleWeb\Repository\Github($repo, $token, $this->app->config('cache_dir'), $log);

            if ($driver->getOwnerId() != $this->app->user()->getGithubId()) {
                $this->app->flash('error', 'You are not the owner of this repository. Please request the owner to register.');
                $this->app->redirect('/package/register?repository='.$repo);
            }

            $extension = new \PickleWeb\Entity\Extension();
            $extension->setFromRepository($driver, $log);

            $redis = $this->app->container->get('redis.client');
            $extensionRepository = $this->app->container->get('extension.repository');
            if ($extensionRepository->find($extension->getName())) {
                $this->app->flash('error', $extension->getName().' is already registred');
                $this->app->redirect('/package/register?repository='.$repo);

                return;
            }
            $extension->setStars($driver->getStars());
            $extension->setWatchers($driver->getWatchers());
            $vendorName = $extension->getVendor();
            $repository = $extension->getRepositoryName();
            $extensionName = $extension->getName();
            $extensionMeta = [
                'watchers' => $extension->getStars(),
                'stars' => $extension->getWatchers(),
            ];
        } catch (\RuntimeException $exception) {
            /* todo: handle bad data in a better way =) */
            $this->app->flash('error', 'An error occurred while retrieving extension data. Please veriry your tag and try again later.'.$exception->getMessage());
            $this->app->redirect('/package/register?repository='.$repo);
        }

        $serializedExtension = $extension->serialize();
        $transaction = hash('sha256', $serializedExtension);
        file_put_contents($this->app->config('cache_dir').'/'.$transaction.'.json', $serializedExtension);

        $serializedMeta = json_encode($extensionMeta);
        file_put_contents($this->app->config('cache_dir').'/'.$transaction.'_meta.json', $serializedMeta);

        $latest = $extension->getPackages()['dev-master'];

        $this->app
                ->render(
                    'extension/confirm.html',
                    [
                        'log' => $log->getOutput(),
                        'transaction' => $transaction,
                        'latest' => $latest,
                        'tags' => $extension->getPackages(),
                        'vcs' => $repo,
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
        $extension = $this->getExtension($name);
        $this->app->notFoundIf($extension == null);

        if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) {
            $http = 'https://';
        } else {
            $http = 'http://';
        }
        $hookUrl = $http.$_SERVER['HTTP_HOST'].'/github/hooks/'.$name;

        $this->app->render(
            'extension/info.html',
            [
                'name' => $name,
                'extension' => $extension->getPackages('dev-master'),
                'meta' => ['watchers' => $extension->getWatchers(), 'stars' => $extension->getStars()],
                'versions' => $extension->getPackages(),
                'apikey' => $extension->getApiKey($this->app),
                'showkey' => $this->showKey,
                'hookurl' => $hookUrl,
            ]
        );
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
