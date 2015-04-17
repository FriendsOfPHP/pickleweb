<?php

namespace PickleWeb\Controller;

/**
 * Class DefaultController.
 */
class DefaultController extends ControllerAbstract
{
    /**
     * GET /.
     */
    public function indexAction()
    {
        $this->app->render('home.html');
    }
}
