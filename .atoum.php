<?php

use PickleWeb\Tests\Atoum\Report\Fields\Runner\Pickle;

require_once __DIR__ . '/vendor/autoload.php';

$script->noCodeCoverageForNamespaces('Slim');

$script
    ->addDefaultReport()
    ->addField(new Pickle());
