<?php

namespace PickleWeb\Controller;

use PickleWeb\Application;

/**
 * Class ControllerAbstract.
 */
abstract class ControllerAbstract
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @param Application $app
     */
    public function setApp(Application $app)
    {
        $this->app = $app;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
