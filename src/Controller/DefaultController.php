<?php

namespace PickleWeb\Controller;

use PickleWeb\Entity\Extension as Extension;

/**
 * Class DefaultController.
 */
class DefaultController extends ControllerAbstract
{
    /**
     * 
     * @return bool
     */
    protected function getTrendExtensions()
    {
        $es = $this->app->container->get('elastica.client');

		$elasticaQueryString  = new \Elastica\Query\QueryString();
		$elasticaQueryString->setQuery('*');

		$elasticaQuery = new \Elastica\Query();
		$elasticaQuery->setQuery($elasticaQueryString);
		$elasticaQuery->setSort(["stars" => 'desc']);

        $elasticaIndex = $es->getIndex('packages');
		$results = $elasticaIndex->search($elasticaQuery);

        $hits = [];
        foreach ($results->getResults() as $result) {
            $hits[] = $result->getHit();
        }
        return $hits;
    }

    /**
     * GET /.
     */
    public function indexAction()
    {
        $extensionRepository = $this->app->container->get('extension.repository');
        $extensions = (array) $extensionRepository->getAll();

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

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
