<?php

namespace DemoShop;

use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Presentation\Controller\AdminController;
use DemoShop\Presentation\Controller\ViewController;
use DemoShop\Infrastructure\Router\Router;
use DemoShop\Infrastructure\Request\Request;
use DemoShop\Infrastructure\Response\HtmlResponse;
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
        ServiceRegistry::set(ViewController::class, new ViewController());
        ServiceRegistry::set(AdminController::class, new AdminController());
    }

    private static function registerRoutes(): void
    {
        $router = new Router();

        try {
            $viewController = ServiceRegistry::get(ViewController::class);
            $adminController = ServiceRegistry::get(AdminController::class);
            $router->add('GET', '/', [$viewController, 'landingPage']);
            $router->add('GET', '/login', [$adminController, 'loginPage']);
            $router->add('POST', '/login', [$adminController, 'sendLoginInfo']);
        } catch (Exception $e) {
            HtmlResponse::createInternalServerError()->view();
        }

        ServiceRegistry::set(Router::class, $router);
    }

    private static function registerRequest(): void
    {
        ServiceRegistry::set(Request::class, new Request());
    }

}