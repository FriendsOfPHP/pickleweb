<?php

namespace PickleWeb\Controller;

/**
 * Class UserController.
 */
class UserController extends ControllerAbstract
{
    /**
     * GET /profile
     */
    public function profileAction()
    {
        $this->app
            ->render(
                'account.html',
                [
                    'account' => $this->app->user(),
                ]
            );
    }

    /**
     * GET /profile/remove
     */
    public function removeConfirmAction()
    {
        $this->app->render('removeAccount.html');
    }

    /**
     * POST /profile/remove
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
     * GET /account(/:name)
     *
     * @param null|string $name
     */
    public function viewAccountAction($name = null)
    {
        $jsonPath = $this->app->config('json_path') . 'users/github/' . $name . '.json';

        $this->app
            ->notFoundIf(file_exists($jsonPath) === false)
            ->redirectUnless($name, '/profile')
            ->render(
                'account.html',
                [
                    'account' => json_decode(file_get_contents($jsonPath), true),
                ]
            );
    }
}
