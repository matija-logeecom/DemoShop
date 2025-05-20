<?php

namespace DemoShop;

use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Presentation\Controller\Controller;
use DemoShop\Infrastructure\Router\Router;
use DemoShop\Infrastructure\Request\Request;

class Bootstrap
{
    public static function init(): void
    {
        self::registerControllers();
        
        self::registerRoutes();

        self::registerRequest();

    }

    private static function registerControllers(): void
    {
        ServiceRegistry::set(Controller::class, new Controller());
    }

    private static function registerRoutes(): void
    {
        $router = new Router();

        $controller = ServiceRegistry::get(Controller::class);

        $router->add('GET', '/', [$controller, 'home']);
        $router->add('GET', '/number/{num}', [$controller, 'test']);
        $router->add('GET', '/something/{cat}/somethingelse/{id}', [$controller, 'something']);

        ServiceRegistry::set(Router::class, $router);
    }

    private static function registerRequest(): void
    {
        ServiceRegistry::set(Request::class, new Request());
    }

}