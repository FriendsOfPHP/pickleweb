<?php

namespace PickleWeb\Controller;

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

            $token       = $_SESSION['token'];
            $transaction = json_decode(file_get_contents($pathTransaction));
            $package_name = $transaction->extension->name;

            $driver = new \PickleWeb\Repository\Github($transaction->extension->vcs, $token->accessToken, $this->app->config('cache_dir'));
            $info   = $driver->getComposerInformation();
            /* move all that to previous step, store final files, match properties order too */

            $packages     = [
                'packages' => [
                    $package_name => [],
                ],
            ];

            $package      = &$packages['packages'][$package_name];
            foreach ($transaction->tags as $tag) {
                $extra = [
                    'version_normalized' => $tag->version,
                ];

                $package[$tag->tag] = array_merge($extra, $driver->getComposerInformation($tag->id));
            }
            $json = json_encode($packages);
            list($vendorName, $extensionName) = explode('/', $package_name);
            $vendorDir = $this->app->config('json_path').'/'.$vendorName;
            if (!is_dir($vendorDir)) {
                mkdir($vendorDir);
            }
            $sha = hash('sha256', $json);

            $jsonPathBase = $vendorDir.'/'.$extensionName;
            $jsonPathSha  = $jsonPathBase.'$'.$sha.'.json';
            file_put_contents($jsonPathSha, $json);
            link($jsonPathSha, $jsonPathBase.'.json');
            unlink($pathTransaction);
            $this->app->flash('warning', $transaction->extension->name.'has been registred');
            $this->app->redirect('/package/'.$package_name);
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
        $token = $_SESSION['token'];
        $repo  = $this->app->request()->post('repository');

        try {
            $driver = new \PickleWeb\Repository\Github($repo, $token->accessToken, $this->app->config('cache_dir'));
            $info   = $driver->getComposerInformation();

            if ($info === null) {
                $this->app->flash('Warning', 'No valid composer.json found.');
                $this->app->redirect('/package/register');
            }

            $info['vcs'] = $repo;

            if ($info['type'] != 'extension') {
                $this->app->flash('error', $info['name'].' is not an extension package');
                $this->app->redirect('/package/register');
            }

			$package_name = $info['name'];
			list($owner, $repository) = explode('/', $package_name);

            $packages     = [
                'packages' => [
                    $package_name => [],
                ],
            ];

            $package = &$packages['packages'][$package_name];
            $tags    = $driver->getReleaseTags();

            foreach ($tags as $tag) {
				$information = $driver->getComposerInformation($tag['id']);
				$information['version_normalized'] = $tag['version'];
				$information['source'] = $tag['source'];
				$package[$tag['tag']] = $information;
            }

            $jsonPackage = json_encode($packages, JSON_PRETTY_PRINT);
            $transaction = hash('sha256', $jsonPackage);

            file_put_contents($this->app->config('cache_dir').'/'.$transaction.'.json', $jsonPackage);

            $this->app
                ->render(
                    'extension/confirm.html',
                    [
                        'transaction' => $transaction,
                        'extension'   => $info,
                        'tags'        => $tags,
                    ]
                );
        } catch (\RuntimeException $exception) {
			/* todo: handle bad data in a better way =) */
            $this->app->flash('error', 'An error occurred while retrieving extension data. Please try again later.');
            $this->app->redirect('/package/register?repository='.$repo);
        }
    }

    /**
     * GET /package/:vendor/:package.
     *
     * @param string $package
     */
    public function viewPackageAction($vendor, $package)
    {
        $jsonPath = $this->app->config('json_path').$vendor.'/'.$package.'.json';

        $this->app->notFoundIf(file_exists($jsonPath) === false);

        $name = $vendor.'/'.$package;
        $json = json_decode(file_get_contents($jsonPath), true);
echo file_get_contents($jsonPath);exit();
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
