<?php

namespace PickleWeb;

use PickleWeb\Entity\User;
use RKA\Slim;

/**
 * Class Application.
 */
class Application extends Slim
{
    /**
     * @var callable
     */
    private $authentication;

    /**
     * @param array $userSettings
     */
    public function __construct(array $userSettings = array())
    {
        parent::__construct($userSettings);

        session_start();

        $this->authentication = function () {
            if ($this->user() === null) {
                $this->redirect('/login');
            }
        };
    }

    /**
     * @return User|null
     */
    public function user()
    {
        return isset($_SESSION['user']) ? unserialize($_SESSION['user']) : null;
    }

    /**
     * @param mixed    $condition
     * @param string   $url
     * @param int|null $status
     *
     * @return $this
     */
    public function redirectIf($condition, $url, $status = null)
    {
        if ((bool) $condition) {
            $this->redirect($url, $status ?: 302);
        }

        return $this;
    }

    /**
     * @param mixed    $condition
     * @param string   $url
     * @param int|null $status
     *
     * @return $this
     */
    public function redirectUnless($condition, $url, $status = null)
    {
        if ((bool) $condition === false) {
            $this->redirect($url, $status ?: 302);
        }

        return $this;
    }

    /**
     * @param mixed $condition
     *
     * @return $this
     */
    public function notFoundIf($condition)
    {
        if ((bool) $condition === true) {
            $this->notFound();
        }

        return $this;
    }

    /**
     * @param int $code
     *
     * @return $this
     */
    public function renderError($code)
    {
        $this->render('errors/'.$code.'.html');
        $this->response()->status($code);
        $this->stop();

        return $this;
    }

    /**
     * @param string $template
     * @param array  $data
     * @param null   $status
     */
    public function render($template, $data = array(), $status = null)
    {
        // add user in template data
        $data = array_merge(
            [
                'user' => $this->user(),
            ],
            $data ?: []
        );

        parent::render($template, $data, $status);
    }

    /**
     * @param callable $callback
     *
     * @return $this
     */
    public function then(callable $callback)
    {
        $callback($this);

        return $this;
    }

    /**
     * @param callable $callback
     *
     * @return $this
     */
    public function otherwise(callable $callback)
    {
        return $this->then($callback);
    }

    /**
     * Run application.
     */
    public function run()
    {
        $this->error(
            function () {
                $this->renderError(500);
            }
        );

        $this->notFound(
            function () {
                $this->renderError(404);
            }
        );

        parent::run();
    }

    /**
     * @param string          $route
     * @param callable|string $callable
     *
     * @return \Slim\Route
     */
    public function getSecured($route, $callable)
    {
        return $this->get($route, $this->authentication, $callable);
    }

    /**
     * @param string          $route
     * @param callable|string $callable
     *
     * @return \Slim\Route
     */
    public function postSecured($route, $callable)
    {
        return $this->post($route, $this->authentication, $callable);
    }
}
