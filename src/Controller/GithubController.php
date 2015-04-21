<?php

namespace PickleWeb\Controller;

use Composer\IO\BufferIO as BufferIO;

/**
 * Class GithubController.
 */
class GithubController extends ControllerAbstract
{
    /**
     * @param string $name
     *
     * @return bool
     */
    protected function findRegisteredExension($name)
    {
        list($vendorName, $repoName) = explode('/', $name);

        $vendorDir = $this->app->config('json_path').'/'.$vendorName;

        if (!(is_dir($vendorDir) && file_exists($vendorDir.'/'.$repoName.'.json'))) {
            return false;
        }

        return true;
    }

    /**
     * @param string $username
     * 
     * @return string
     */
    protected function findUser($username)
    {
        if (!$username) {
            return false;
        }

        return $username;
    }

    /**
     * valid Payload using API key.
     */
    protected function validPayload()
    {
        /* will be user specific later */
        $secret = getenv('GITHUB_HOOK_KEY');
        $hubSignature = $this->app->request()->headers()->get('X-Hub-Signature');

        if (!$hubSignature) {
            die('come back with what I need');
        }

        list($algo, $hash) = explode('=', $hubSignature, 2);

        $payload = file_get_contents('php://input');
        $payloadHash = hash_hmac($algo, $payload, $secret);

        /* not from github, no need to be nice */
        if ($hash !== $payloadHash) {
            die('come back with what I need');
        }
    }

    /**
     * @param string $username
     * 
     * Hook for github hooks. Only release and tag are supported.
     */
    public function hookAction($username)
    {
        $username = $this->findUser($username);

        if (!$username) {
            /* not from github, no need to be nice */
            die('Who are you?');
        }

        $this->validPayload($username);

        $payloadPost = $this->app->request->getBody();

        $payload = json_decode($payloadPost);

        if (!$payload) {
            $this->app->jsonResponse([
                'status' => 'error',
                'message' => 'invalid Payload',
            ],
            200);
        }

        $path = $this->app->config('cache_dir').'/payload.json';

        if (!($payload->ref_type == 'tag' || $payload->ref_type == 'release')) {
            $this->app->jsonResponse(
            [
                'status' => 'error',
                'message' => 'Only tag/release hooks are supported',
            ],
            200
            );
        }

        $extensionName = $payload->repository->full_name;
        $tag = $payload->ref;
        $repository = $payload->repository->git_url;

        if (!$this->findRegisteredExension($extensionName)) {
            $this->app->jsonResponse([
            'status' => 'error',
            'message' => 'Package not found ('.$extensionName.')',
            ],
            200);

            return;
        }

        $log = new BufferIO();
/*
        // create a oauth session from user
        $token = $_SESSION['token'];

        try {
            $driver = new \PickleWeb\Repository\Github($repository, $token->accessToken, $this->app->config('cache_dir'), $log);
*/
        try {
            $driver = new \PickleWeb\Repository\Github($repository, false, $this->app->config('cache_dir'), $log);
            $extension = new \PickleWeb\Extension();
            $extension->setFromRepository($driver, $log);
        } catch (\Exception $e) {
            $this->app->jsonResponse([
                'status' => 'error',
                'message' => $extensionName.'-'.$tag.' error on import:'.$e->getMessage(),
            ],
            500);
        }

        $path = $this->app->config('cache_dir').'/new.json';
        file_put_contents($path, $extension->serialize());
        $this->app->jsonResponse([
            'status' => 'success',
            'message' => $extensionName.'-'.$tag.' imported',
            ],
            200);

        return;
    }
}
