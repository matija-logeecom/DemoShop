<?php

require_once __DIR__ . '/vendor/autoload.php';

use DemoShop\Bootstrap;
use DemoShop\Infrastructure\Router\Router;
use DemoShop\Infrastructure\DI\ServiceRegistry;

const VIEWS_PATH = __DIR__ . '/Presentation/pages';

try {
    BootStrap::init();

    ServiceRegistry::get(Router::class)->route();

//    $controller = ServiceRegistry::get(Controller::class);
//    $router = ServiceRegistry::get(Router::class);
//    $router->dispatch('/something/blabla/somethingelse/999', 'GET');
} catch (Exception $e) {
    echo $e->getMessage();
}


