<?php

namespace PickleWeb;

use Slim\Slim;

class Application
{
    /**
     * @var \Slim\Slim
     */
    private $application;

    /**
     * @var callable
     */
    private $authentication;

    public function __construct(Slim $application)
    {
        session_start();

        $this->application = $application;

        $this->authentication = function() {
            if ($this->user() === null) {
                $this->application->redirect('/login');
            }
        };
    }

    public function user()
    {
        return isset($_SESSION['user']) ? $_SESSION['user'] : null;
    }

    public function config($name, $value = null)
    {
        if (func_num_args() == 1) {
            return $this->application->config($name);
        }

        return $this->application->config($name, $value);
    }

    public function request()
    {
        return $this->application->request();
    }

    public function redirect($url, $status = null)
    {
        $this->application->redirect($url, $status ?: 302);

        return $this;
    }

    public function redirectIf($condition, $url, $status = null)
    {
        if ((bool) $condition) {
            $this->redirect($url, $status ?: 302);
        }

        return $this;
    }

    public function redirectUnless($condition, $url, $status = null)
    {
        if ((bool) $condition === false) {
            $this->redirect($url, $status ?: 302);
        }

        return $this;
    }

    public function notFound(callable $callable = null)
    {
        $this->application->notFound($callable);

        return $this;
    }

    public function notFoundIf($condition)
    {
        if ((bool) $condition === true) {
            $this->application->notFound();
        }

        return $this;
    }

    public function render($template, array $data = [], $status = null)
    {
        $this->application->render($template, $data, $status);

        return $this;
    }

    public function renderError($code)
    {
        $this->setViewData()->render('errors/'.$code.'.html');
        $this->application->response()->status($code);
        $this->application->stop();

        return $this;
    }

    public function setViewData(array $data = [])
    {
        $data = array_merge(
            [
                'user' => $this->user()
            ],
            $data
        );

        $this->application->view()->setData($data);

        return $this;
    }

    public function then(callable $callback)
    {
        $callback($this);

        return $this;
    }

    public function otherwise(callable $callback)
    {
        return $this->then($callback);
    }

    public function run()
    {
        $this->application->error(function () {
                $this->renderError(500);
            }
        );

        $this->application->notFound(function () {
                $this->renderError(404);
            }
        );

        $this->application->run();

        return $this;
    }

    public function get($route, callable $callable)
    {
        return $this->application->get($route, $callable);
    }

    public function getSecured($route, callable $callable)
    {
        return $this->application->get($route, $this->authentication, $callable);
    }

    public function post($route, callable $callable)
    {
        return $this->application->get($route, $callable);
    }

    public function postSecured($route, callable $callable)
    {
        return $this->application->post($route, $this->authentication, $callable);
    }

    public function flash($key, $value)
    {
        $this->application->flash($key, $value);

        return $this;
    }
}
