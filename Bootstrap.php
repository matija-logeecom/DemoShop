<?php

namespace DemoShop;

use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Presentation\Controller\Controller;
use DemoShop\Infrastructure\Router\Router;
use DemoShop\Infrastructure\Request\Request;
use Exception;

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

        try {
            $controller = ServiceRegistry::get(Controller::class);
            $router->add('GET', '/', [$controller, 'landingPage']);
            $router->add('GET', '/login', [$controller, 'loginPage']);
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        ServiceRegistry::set(Router::class, $router);
    }

    private static function registerRequest(): void
    {
        ServiceRegistry::set(Request::class, new Request());
    }

}