<?php

namespace PickleWeb\Controller;

/**
 * Class UserController.
 */
class UserController extends ControllerAbstract
{
    public function setFromLocalJson($extensionName)
    {
        $this->vendorName = $vendorName;
        $this->repositoryName = $repositoryName;

        $this->data = $data;
    }

    /**
     * GET /profile.
     */
    public function profileAction()
    {
        $user = $this->app->user();
        $extensions = $user->getExtensions();
        $redis = $this->app->container->get('redis.client');
        $extensionRepository = $this->app->container->get('extension.repository');
        $shortList = [];
        foreach ($extensions as $extensionName) {
            $extension = $extensionRepository->find($extensionName);
            $latest = $extension->getPackages('dev-master');

            $shortList[] = [
            'name' => $latest->getName(),
            'description' => $latest->getDescription(),

            ];
        }

        $this->app
            ->render(
                'account.html',
                [
                    'account' => $user,
                    'extensions' => $shortList,
                ]
            );
    }

    /**
     * GET /profile/remove.
     */
    public function removeConfirmAction()
    {
        $this->app->render('removeAccount.html');
    }

    /**
     * POST /profile/remove.
     */
    public function removeAction()
    {
        $user = $this->app->user();
        $this->app->container->get('user.repository')->remove($user);
        session_destroy();
        $this->app->flash('info', sprintf('The account %s was successfully removed', $user->getId()));

        $this->app->redirect('/');
    }

    /**
     * GET /account(/:name).
     *
     * @param null|string $name
     */
    public function viewAccountAction($name = null)
    {
        $redis = $this->app->container->get('redis.client');
        $userRepository = new UserRepository($redis);
        $user = $userRepository->find($name);

        $jsonPath = $this->app->config('json_path').'users/github/'.$name.'.json';

        $this->app
            ->notFoundIf(file_exists($jsonPath) === false)
            ->redirectUnless($name, '/profile')
            ->render(
                'account.html',
                [
                    'account' => $user,
                ]
            );
    }
}
