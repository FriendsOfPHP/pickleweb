<?php

namespace PickleWeb;

use Slim\Slim;

class Application
{
    /**
     * @var \Slim\Slim
     */
    private $application;

    public function __construct(Slim $application)
    {
        $this->application = $application;
    }

    public function __call($method, $arguments)
    {
        return call_user_func_array([$this->application, $method], $arguments);
    }

    public function redirectIf($condition, $url, $status = null)
    {
        if ((bool) $condition) {
            $this->application->redirect($url, $status ?: 302);
        }

        return $this;
    }

    public function redirectUnless($condition, $url, $status = null)
    {
        if ((bool) $condition === false) {
            $this->application->redirect($url, $status ?: 302);
        }

        return $this;
    }

    public function notFoundIf($condition)
    {
        if ((bool) $condition === true) {
            $this->application->notFound();
        }

        return $this;
    }

    public function renderError($code)
    {
        $this->application->render('errors/'.$code.'.html');
        $this->application->response()->status($code);
        $this->application->stop();

        return $this;
    }

    public function setViewData(array $data)
    {
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
}
