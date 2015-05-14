<?php

namespace PickleWeb;

use Elastica\Client as Client;
use PickleWeb\Entity\Extension as Extension;

class Indexer
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function createIndex($delete = true)
    {
        $elasticaIndex = $this->client->getIndex('packages');

        $res = $elasticaIndex->create(
            array(
                'number_of_shards' => 4,
                'number_of_replicas' => 1,
                'analysis' => array(
                    'analyzer' => array(
                        'indexAnalyzer' => array(
                            'type' => 'custom',
                            'tokenizer' => 'standard',
                            'filter' => array('lowercase'),
                        ),
                        'searchAnalyzer' => array(
                            'type' => 'custom',
                            'tokenizer' => 'standard',
                            'filter' => array('standard', 'lowercase'),
                        ),
                    ),
                ),
            ),
            $delete
        );
    }

    /**
     * @var Extension
     * @var bool
     */
    public function indexExtension(Extension $extension, $update = false)
    {
        $id = $extension->getName();
        $packageDocument = new \Elastica\Document($id,
                                [
                                    'id' => $id,
                                    'name' => $id,
                                    'descrription' => $extension->getDescription(),
                                    'tags' => $extension->getVersions(),
                                    'keywords' => $extension->getKeywords(),
                                ]
                            );

        $elasticaIndex = $this->client->getIndex('packages');
        $elasticaType = $elasticaIndex->getType('packages');
        if ($update) {
            $elasticaType->updateDocument($packageDocument);
        } else {
            $elasticaType->addDocument($packageDocument);
        }
        $elasticaType->getIndex()->refresh();
    }
}
