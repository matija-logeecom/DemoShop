<?php

namespace DemoShop\Infrastructure\Router;

use DemoShop\Infrastructure\Middleware\Authorize\AlreadyLoggedInMiddleware;
use DemoShop\Infrastructure\Middleware\Authorize\AuthorizeMiddleware;
use DemoShop\Infrastructure\Middleware\Authorize\ValidateMiddleware;
use DemoShop\Presentation\Controller\AdminController;
use DemoShop\Presentation\Controller\AuthController;
use DemoShop\Presentation\Controller\CategoryController;
use DemoShop\Presentation\Controller\ProductController;
use DemoShop\Presentation\Controller\ViewController;

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

        // Category
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

        // Products
        RouteRegistry::add(
            'POST',
            '/api/products',
            [ProductController::class, 'createProduct']
        );
        RouteRegistry::add(
            'GET',
            '/api/products',
            [ProductController::class, 'getProducts'],
            [AuthorizeMiddleware::class]
        );
        RouteRegistry::add(
            'DELETE',
            '/api/products',
            [ProductController::class, 'deleteProducts'],
            [AuthorizeMiddleware::class]
        );
        RouteRegistry::add(
            'PUT',
            '/api/products/status-batch',
            [ProductController::class, 'batchUpdateProductStatus'],
            [AuthorizeMiddleware::class]
        );
    }
}