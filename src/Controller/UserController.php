<?php
namespace PickleWeb\Controller;

/**
 * Class UserController
 *
 * @package PickleWeb\Controller
 */
class UserController extends ControllerAbstract
{
    /**
     * GET /profile
     */
    public function profileAction()
    {
        $this->app
            ->setViewData(
                [
                    'account' => $this->app->user()
                ]
            )
            ->render('account.html');
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
            ->setViewData(
                [
                    'account' => json_decode(file_get_contents($jsonPath), true)
                ]
            )
            ->render('account.html');
    }
}