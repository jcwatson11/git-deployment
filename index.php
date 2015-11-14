#!/usr/bin/env php
<?php

use Fh\Git\Deployment\Deploy;

require_once(__DIR__."/vendor/autoload.php");

$c = require_once(__DIR__."/config.php");

$d = new Deploy($c, $argv[1], $argv[2], $argv[3]);

try {
    $d->main();
} catch (\Exception $e) {
    echo "Exception encountered: " . $e->getMessage() . ' in file ' . $e->getFile() . ' on line ' . $e->getLine();
}
