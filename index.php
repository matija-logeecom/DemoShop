<?php

require_once __DIR__ . '/vendor/autoload.php';

use DemoShop\Core;
const VIEWS_PATH = __DIR__ . '/Presentation/pages';

$app = new Core();
$app->run();
