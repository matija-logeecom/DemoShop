<?php

namespace DemoShop\src\Infrastructure;

use DemoShop\src\Business\Interfaces\Encryption\EncryptorInterface;
use DemoShop\src\Business\Interfaces\Repository\AdminAuthTokenRepositoryInterface;
use DemoShop\src\Business\Interfaces\Repository\AdminRepositoryInterface;
use DemoShop\src\Business\Interfaces\Repository\CategoryRepositoryInterface;
use DemoShop\src\Business\Interfaces\Service\AuthServiceInterface;
use DemoShop\src\Business\Interfaces\Service\CategoryServiceInterface;
use DemoShop\src\Business\Interfaces\Service\DashboardServiceInterface;
use DemoShop\src\Business\Service\AuthService;
use DemoShop\src\Business\Service\CategoryService;
use DemoShop\src\Business\Service\DashboardService;
use DemoShop\src\Data\Encryption\Encryptor;
use DemoShop\src\Data\Repository\AdminAuthTokenRepository;
use DemoShop\src\Data\Repository\AdminRepository;
use DemoShop\src\Data\Repository\CategoryRepository;
use DemoShop\src\Infrastructure\DI\ServiceRegistry;
use DemoShop\src\Infrastructure\Middleware\Authorize\AlreadyLoggedInMiddleware;
use DemoShop\src\Infrastructure\Middleware\Authorize\AuthorizeMiddleware;
use DemoShop\src\Infrastructure\Middleware\Authorize\ValidateMiddleware;
use DemoShop\src\Infrastructure\Request\Request;
use DemoShop\src\Infrastructure\Response\HtmlResponse;
use DemoShop\src\Infrastructure\Router\RouteConfig;
use DemoShop\src\Infrastructure\Router\RouteDispatcher;
use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use RuntimeException;

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
        try {
            self::initEncryption();
            self::initEloquent();

            self::registerRepositories();
            self::registerServices();

            self::registerMiddlewareServices();

            self::registerRoutes();

            self::registerRequest();

            ServiceRegistry::set(RouteDispatcher::class, new RouteDispatcher());
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
     * Registers all possible routes
     *
     * @return void
     */
    private static function registerRoutes(): void
    {
        try {
            RouteConfig::registerRoutes();
        } catch (\InvalidArgumentException $e) {
            error_log("Error adding route via RouteConfig - Invalid argument: " . $e->getMessage());
            throw new \RuntimeException("Failed to define application routes due to invalid configuration.", 0, $e);
        } catch (\Exception $e) {
            error_log("Unexpected error during route registration via RouteConfig: " . $e->getMessage());
            throw new \RuntimeException("An unexpected error occurred while defining application routes.", 0, $e);
        }
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
     * Registers all middleware
     *
     * @return void
     */
    private static function registerMiddlewareServices(): void
    {
        try {
            ServiceRegistry::set(ValidateMiddleware::class, new ValidateMiddleware());
            ServiceRegistry::set(AlreadyLoggedInMiddleware::class, new AlreadyLoggedInMiddleware());
            ServiceRegistry::set(AuthorizeMiddleware::class, new AuthorizeMiddleware());
        } catch (RuntimeException $e) {
            error_log("Failed to register an individual Middleware service in Bootstrap: " . $e->getMessage());
            throw new RuntimeException("Critical middleware service registration failed.", 0, $e);
        }
    }
}
