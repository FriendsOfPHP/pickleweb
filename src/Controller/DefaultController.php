<?php

namespace PickleWeb\Controller;

use PickleWeb\Entity\Extension as Extension;

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
        $extensionRepository = $this->app->container->get('extension.repository');
        $extensions = $extensionRepository->getAll();

        $list = [];
        foreach ($extensions as $name => $extension) {
            $list[] = [
            'name' => $name,
            'description' => $extension->getDescription(),
            'stars' => $extension->getStars(),
            'watchers' => $extension->getWatchers(),
            ];
        }

        $this->app->render('home.html',
            [
            'packages' => $list,
            ]);
    }
}
