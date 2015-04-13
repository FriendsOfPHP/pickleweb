<?php

use PickleWeb\Tests\Atoum\Report\Fields\Runner\Pickle;

require_once __DIR__ . '/vendor/autoload.php';

$script->addTestsFromDirectory(__DIR__ . '/tests/unit');
$script->noCodeCoverageForNamespaces('Slim');
$script->addDefaultReport()->addField(new Pickle());
