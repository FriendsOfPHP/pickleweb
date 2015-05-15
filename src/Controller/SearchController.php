<?php

namespace PickleWeb\Controller;

use PickleWeb\Entity\Extension as Extension;
use PickleWeb\Indexer as Indexer;
use Elastica\Search;

/**
 * Class SearchController.
 */
class SearchController extends ControllerAbstract
{
    /**
     * @var Elastica\Client
     */
    protected $client;

    /**
     * @param string $query
     *
     * @return bool
     */
    public function search()
    {
        $q = $this->app->request()->get('q');
        if (!$q) {
            echo json_encode(null);
            exit();
        }

        $es = $this->app->container->get('elastica.client');
        $search = new Search($es);
        $results = $search->search($q);

        $hits = [];
        foreach ($results->getResults() as $result) {
            $hits[] = $result->getHit();
        }
        echo json_encode($hits);

        return true;
    }

    public function createIndex()
    {
        $es = $this->app->container->get('elastica.client');
        $indexer = new Indexer($es);
        $indexer->createIndex(true);

        return true;
    }

    public function index(Extension $extension)
    {
        $es = $this->app->container->get('elastica.client');
        $indexer = new Indexer($es);
        $indexer->index($extension);

        return true;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
