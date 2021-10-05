<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Mint\ApiEngine;

//tell the manager where are located the tasks
ApiEngine::start(__DIR__ . '/../tasks');

?>