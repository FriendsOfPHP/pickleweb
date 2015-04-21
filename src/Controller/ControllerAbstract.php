<?php
namespace PickleWeb\Controller;

use PickleWeb\Application;

/**
 * Class ControllerAbstract
 *
 * @package PickleWeb\Controller
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
