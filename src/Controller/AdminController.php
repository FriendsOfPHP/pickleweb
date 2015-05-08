<?php

namespace PickleWeb\Controller;

class AdminController extends ControllerAbstract
{
    /**
     * @var Github\Client
     */
    protected $client;

    protected $ignoreExt = [
        'standard',
        'ext_skel',
        'ext_skel_win32.php',
    ];

    protected function fetchExtensionsFromRepository()
    {
        $token = $_SESSION['github.token'];
        if (!$token) {
            throw new \Exception('no github token setup');
        }
        $client = new \Github\Client(new \Github\HttpClient\CachedHttpClient(array('cache_dir' => $this->app->config('cache_dir').'githubapi-cache')));
        $this->client = $client;
        $client->authenticate($token, null, \GitHub\Client::AUTH_URL_TOKEN);
        $pager = new \Github\ResultPager($client);
        $api = $client->api('repo');
        $method = 'branches';
        $params = ['php', 'php-src'];
        $branches = $pager->fetchAll($api, $method, $params);

        $branchesToFetch = [];
        foreach ($branches as $branch) {
            $currentBranchName = $branch['name'];
            if (strlen($currentBranchName) == 7) {
                if (preg_match('$PHP-([0-9])\.([0-9])$', $currentBranchName, $matches)) {
                    if ((int) $matches[1] < 5 || (int) $matches[2] < 4) {
                        continue;
                    }
                    $branchesToFetch[$matches[0]] = $branch;
                }
            }
        }
        $this->branches = $branchesToFetch;
        $extensions = $this->fetchExtensionPerBranch();

        return $extensions;
    }

    /**
     * GET /.
     */
    public function updateBundleExtensions()
    {
        $extensions = $this->fetchExtensionsFromRepository();
        $this->app->render('bundleExtensionEdit.html',
            [
            'extensions' => $extensions,
            ]);

        $this->generateBundledExtJson($extensions);
    }

    /**
     * POST /.
     */
    public function saveBundleExtensions()
    {
        $extensionsRepository = $this->fetchExtensionsFromRepository();

        $extensionsPostMapping = $this->app->request()->post('externalName');
        if (!$extensionsPostMapping) {
            $this->app->flash('error', 'Invalid request arguements.');
            $this->app->redirect('/');
            exit();
        }

        foreach ($extensionsPostMapping as $branch => $extensions) {
            foreach ($extensions as $extensionName => $externalName) {
                if (!isset($extensionsRepository[$branch][$extensionName])) {
                    $this->app->flash('error', 'Invalid request arguments.');
                    $this->app->redirect('/');
                    exit();
                }
                $extensionsRepository[$branch][$extensionName] = $externalName;
            }
        }

        $this->generateBundledExtJson($extensionsRepository);
        $this->app->flash('warning', 'bundled extension mapping against external repository saved.');
        $this->app->redirect('/profile');
        exit();
    }

    protected function fetchExtensionPerBranch()
    {
        $path = 'ext';
        $extensionsPerBranch = [];
        foreach ($this->branches as $name => $branch) {
            $fileInfo = $this->client->api('repo')->contents()->show('php', 'php-src', $path, $branch['commit']['sha']);
            $extensionsPerBranch[$name] = $this->getBundledExtensions($fileInfo);
        }

        return $extensionsPerBranch;
    }

    protected function getBundledExtensions($fileInfo)
    {
        $extensions = [];
        foreach ($fileInfo as $extDir) {
            if (!in_array($extDir['name'], $this->ignoreExt)) {
                $extensions[$extDir['name']] = false;
            }
        }

        return $extensions;
    }

    protected function generateBundledExtJson($extensions)
    {
        $jsonPathBase = $this->app->config('json_path').'/';
        foreach ($extensions as $branch => $extensionsBundled) {
            $jsonPath = $jsonPathBase.$branch.'.json';
            $json = json_encode($extensionsBundled);
            file_put_contents($jsonPath, $json);
        }
    }
}
