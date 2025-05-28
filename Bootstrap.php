<?php

namespace DemoShop;

use DemoShop\Business\Interfaces\Encryption\EncryptorInterface;
use DemoShop\Business\Interfaces\Repository\AdminAuthTokenRepositoryInterface;
use DemoShop\Business\Interfaces\Repository\AdminRepositoryInterface;
use DemoShop\Business\Interfaces\Repository\CategoryRepositoryInterface;
use DemoShop\Business\Interfaces\Service\AuthServiceInterface;
use DemoShop\Business\Interfaces\Service\CategoryServiceInterface;
use DemoShop\Business\Interfaces\Service\DashboardServiceInterface;
use DemoShop\Business\Service\AuthService;
use DemoShop\Business\Service\CategoryService;
use DemoShop\Business\Service\DashboardService;
use DemoShop\Data\Encryption\Encryptor;
use DemoShop\Data\Repository\AdminAuthTokenRepository;
use DemoShop\Data\Repository\AdminRepository;
use DemoShop\Data\Repository\CategoryRepository;
use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Infrastructure\Middleware\Authorize\AlreadyLoggedInMiddleware;
use DemoShop\Infrastructure\Middleware\Authorize\AuthorizeMiddleware;
use DemoShop\Infrastructure\Middleware\Authorize\ValidateMiddleware;
use DemoShop\Infrastructure\Request\Request;
use DemoShop\Infrastructure\Response\HtmlResponse;
use DemoShop\Infrastructure\Router\Router;
use DemoShop\Presentation\Controller\AdminController;
use DemoShop\Presentation\Controller\AuthController;
use DemoShop\Presentation\Controller\CategoryController;
use DemoShop\Presentation\Controller\ViewController;
use Dotenv\Dotenv;
use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;

/*
 * Responsible for initializing dependencies
 */

class Bootstrap
{
    /**
     * Initializes dependencies
     *
     * @throws Exception
     */
    public static function init(): void
    {
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();

        try {
            self::initEncryption();
            self::initEloquent();

            self::registerRepositories();
            self::registerServices();
            self::registerControllers();

            self::registerLoginMiddleware();
            self::registerAuthorizeMiddleware();
            self::registerAlreadyLoggedInMiddleware();

            self::registerRoutes();

            self::registerRequest();
        } catch (\Throwable $e) {
            error_log("CRITICAL BOOTSTRAP FAILURE in init(): " . $e->getMessage() . "\n" . $e->getTraceAsString());
            if (!headers_sent()) {
                HtmlResponse::createInternalServerError("A critical error occurred during application startup.")->view();
            }

            exit;
        }
    }

    /**
     * Registers repositories
     *
     * @throws Exception
     */
    private static function registerRepositories(): void
    {
        try {
            ServiceRegistry::set(AdminRepositoryInterface::class, new AdminRepository());
            ServiceRegistry::set(CategoryRepositoryInterface::class, new CategoryRepository());
            ServiceRegistry::set(AdminAuthTokenRepositoryInterface::class, new AdminAuthTokenRepository());
        } catch (\RuntimeException $e) {
            error_log("Failed to register Repository in Bootstrap: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Registers services
     *
     * @throws Exception
     */
    private static function registerServices(): void
    {
        try {
            ServiceRegistry::set(AuthServiceInterface::class, new AuthService());
            ServiceRegistry::set(CategoryServiceInterface::class, new CategoryService());
            ServiceRegistry::set(DashboardServiceInterface::class, new DashboardService());
        } catch (\RuntimeException $e) {
            error_log("Failed to register Service in Bootstrap: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Registers controllers
     */
    private static function registerControllers(): void
    {
        try {
            ServiceRegistry::set(ViewController::class, new ViewController());
            ServiceRegistry::set(AdminController::class, new AdminController());
            ServiceRegistry::set(AuthController::class, new AuthController());
            ServiceRegistry::set(CategoryController::class, new CategoryController());
        } catch (\RuntimeException $e) {
            error_log("Failed to register Controller in Bootstrap: " . $e->getMessage());
            throw $e;
        }

    }

    /**
     * Registers router and adds routes to it
     *
     * @return void
     */
    private static function registerRoutes(): void
    {
        $router = new Router();

        try {
            $viewController = ServiceRegistry::get(ViewController::class);
            $adminController = ServiceRegistry::get(AdminController::class);
            $authController = ServiceRegistry::get(AuthController::class);
            $categoryController = ServiceRegistry::get(CategoryController::class);

            $router->add('GET', '/', [$viewController, 'landingPage']);
            $router->add(
                'GET',
                '/login',
                [$authController, 'loginPage'],
                'adminCheckLoginPipeline'
            );
            $router->add(
                'GET',
                '/admin',
                [$adminController, 'adminPage'],
                'adminAuthorizePipeline'
            );
            $router->add(
                'POST',
                '/login',
                [$authController, 'sendLoginInfo'],
                'adminLoginPipeline'
            );
            $router->add(
                'GET',
                '/logout',
                [$authController, 'logout']
            );
            $router->add(
                'GET',
                '/api/dashboard',
                [$adminController, 'dashboardData'],
                'adminAuthorizePipeline'
            );
            $router->add(
                'POST',
                '/api/createCategory',
                [$categoryController, 'createCategory'],
                'adminAuthorizePipeline'
            );
            $router->add(
                'GET',
                '/api/categories',
                [$categoryController, 'getCategories'],
                'adminAuthorizePipeline'
            );
            $router->add(
                'PUT',
                '/api/update/{id}',
                [$categoryController, 'updateCategory'],
                'adminAuthorizePipeline'
            );
            $router->add('DELETE',
                '/api/delete/{id}',
                [$categoryController, 'deleteCategory'],
                'adminAuthorizePipeline'
            );
        } catch (Exception $e) {
            HtmlResponse::createInternalServerError()->view();
        }
        ServiceRegistry::set(Router::class, $router);
    }

    /**
     * Registers request
     */
    private static function registerRequest(): void
    {
        ServiceRegistry::set(Request::class, new Request());
    }

    /**
     * Initializes Eloquent connection to database
     */
    private static function initEloquent(): void
    {
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver' => $_ENV['DB_DRIVER'],
            'host' => $_ENV['DB_HOST'],
            'port' => $_ENV['DB_PORT'],
            'database' => $_ENV['DB_NAME'],
            'username' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'],
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    /**
     * Initializes encryptor
     */
    private static function initEncryption(): void
    {
        $key = $_ENV['APP_KEY'];
        ServiceRegistry::set(EncryptorInterface::class, new Encryptor($key));
    }

    /**
     * Registers login middleware pipeline
     *
     * @throws Exception
     */
    private static function registerLoginMiddleware(): void
    {
        try {
            $middleware = new ValidateMiddleware();
            $middleware->linkWith(new AlreadyLoggedInMiddleware());
            ServiceRegistry::set('adminLoginPipeline', $middleware);
        } catch (\RuntimeException $e) {
            error_log("Failed to register Middleware in Bootstrap: " . $e->getMessage());
            throw $e;
        }

    }

    /**
     * Register authorize middleware pipeline
     *
     * @throws Exception
     */
    private static function registerAuthorizeMiddleware(): void
    {
        try {
            $middleware = new AuthorizeMiddleware();
            ServiceRegistry::set('adminAuthorizePipeline', $middleware);
        } catch (\RuntimeException $e) {
            error_log("Failed to register Middleware in Bootstrap: " . $e->getMessage());
            throw $e;
        }

    }

    /**
     * Register already logged in middleware pipeline
     *
     * @throws Exception
     */
    private static function registerAlreadyLoggedInMiddleware(): void
    {
        try {
            $middleware = new AlreadyLoggedInMiddleware();
            ServiceRegistry::set('adminCheckLoginPipeline', $middleware);
        } catch (\RuntimeException $e) {
            error_log("Failed to register Middleware in Bootstrap: " . $e->getMessage());
            throw $e;
        }

    }
}
