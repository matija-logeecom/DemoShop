<?php

namespace DemoShop\src\Infrastructure\Router;

use DemoShop\src\Infrastructure\Middleware\Authorize\AlreadyLoggedInMiddleware;
use DemoShop\src\Infrastructure\Middleware\Authorize\AuthorizeMiddleware;
use DemoShop\src\Infrastructure\Middleware\Authorize\ValidateMiddleware;
use DemoShop\src\Presentation\Controller\AdminController;
use DemoShop\src\Presentation\Controller\AuthController;
use DemoShop\src\Presentation\Controller\CategoryController;
use DemoShop\src\Presentation\Controller\ViewController;

class RouteConfig
{
    public static function registerRoutes(): void
    {
        // Web/Page Routes
        RouteRegistry::add('GET', '/', [ViewController::class, 'landingPage']);

        RouteRegistry::add(
            'GET',
            '/login',
            [AuthController::class, 'loginPage'],
            [AlreadyLoggedInMiddleware::class]
        );

        RouteRegistry::add(
            'GET',
            '/admin',
            [AdminController::class, 'adminPage'],
            [AuthorizeMiddleware::class]
        );

        RouteRegistry::add(
            'POST',
            '/login',
            [AuthController::class, 'sendLoginInfo'],
            [ValidateMiddleware::class, AlreadyLoggedInMiddleware::class]
        );

        RouteRegistry::add(
            'GET',
            '/logout',
            [AuthController::class, 'logout']
        );

        // API Routes
        RouteRegistry::add(
            'GET',
            '/api/dashboard',
            [AdminController::class, 'dashboardData'],
            [AuthorizeMiddleware::class]
        );

        // Category API Routes (all require authorization)
        RouteRegistry::add(
            'POST',
            '/api/createCategory',
            [CategoryController::class, 'createCategory'],
            [AuthorizeMiddleware::class]
        );
        RouteRegistry::add(
            'GET',
            '/api/categories',
            [CategoryController::class, 'getCategories'],
            [AuthorizeMiddleware::class]
        );
        RouteRegistry::add(
            'PUT',
            '/api/update/{id}',
            [CategoryController::class, 'updateCategory'],
            [AuthorizeMiddleware::class]
        );
        RouteRegistry::add(
            'DELETE',
            '/api/delete/{id}',
            [CategoryController::class, 'deleteCategory'],
            [AuthorizeMiddleware::class]
        );
    }
}