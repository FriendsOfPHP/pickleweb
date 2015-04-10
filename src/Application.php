<?php

namespace PickleWeb;

use Slim\Slim;

class Application extends Slim
{
    public function __construct(array $userSettings = array())
    {
        return parent::__construct(
            array_merge(
                [
                    'view' => new View\Twig(),
                    'json_path' => __DIR__ . '/../json/'
                ],
                $userSettings
            )
        );
    }

    public function redirectIf($condition, $url, $status = null) {
        if ((bool) $condition) {
            $this->redirect($url, $status ?: 302);
        }

        return $this;
    }

    public function redirectUnless($condition, $url, $status = null) {
        if ((bool) $condition === false) {
            $this->redirect($url, $status ?: 302);
        }

        return $this;
    }

    public function renderError($code) {
        $this->render('errors/' . $code . '.html');
        $this->response->status($code);
        $this->stop($code);

        return $this;
    }

    public function notFoundIf($condition) {
        if ((bool) $condition === false) {
            $this->notFound();
        }

        return $this;
    }

    public function setViewData(array $data) {
        $this->view()->setData($data);

        return $this;
    }

    public function then(callable $callback) {
        $callback($this);

        return $this;
    }

    public function otherwise(callable $callback) {
        return $this->then($callback);
    }

    public function run()
    {
        $this->error(function () {
                $this->renderError(500);
            }
        );

        $this->notFound(function () {
                $this->renderError(404);
            }
        );

        parent::run();
    }
} 
