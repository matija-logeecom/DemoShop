<?php

require_once __DIR__ . '/vendor/autoload.php';

use DemoShop\Infrastructure\Core;

const VIEWS_PATH = __DIR__ . '/resources/pages';

$app = new Core();
$app->run();
