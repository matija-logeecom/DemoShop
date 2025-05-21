<?php

require_once __DIR__ . '/vendor/autoload.php';

use DemoShop\Bootstrap;
use DemoShop\Infrastructure\Router\Router;
use DemoShop\Infrastructure\DI\ServiceRegistry;

const VIEWS_PATH = __DIR__ . '/Presentation/pages';

try {
    Bootstrap::init();
    ServiceRegistry::get(Router::class)->route();
} catch (Exception $e) {
    echo $e->getMessage();
}
