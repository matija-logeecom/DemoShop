<?php

namespace DemoShop;

use DemoShop\Business\Repository\AdminAuthTokenRepositoryInterface;
use DemoShop\Business\Repository\AdminRepositoryInterface;
use DemoShop\Business\Service\AuthService;
use DemoShop\Business\Service\AuthServiceInterface;
use DemoShop\Business\Service\CategoryService;
use DemoShop\Business\Service\CategoryServiceInterface;
use DemoShop\Business\Service\DashboardService;
use DemoShop\Business\Service\DashboardServiceInterface;
use DemoShop\Data\Repository\CategoryRepository;
use DemoShop\Business\Repository\CategoryRepositoryInterface;
use DemoShop\Data\Encryption\Encryptor;
use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Infrastructure\Middleware\Authorize\AlreadyLoggedInMiddleware;
use DemoShop\Infrastructure\Middleware\Authorize\AuthorizeMiddleware;
use DemoShop\Infrastructure\Middleware\Authorize\ValidateMiddleware;
use DemoShop\Presentation\Controller\AdminController;
use DemoShop\Presentation\Controller\AuthController;
use DemoShop\Presentation\Controller\CategoryController;
use DemoShop\Presentation\Controller\ViewController;
use DemoShop\Infrastructure\Router\Router;
use DemoShop\Infrastructure\Request\Request;
use DemoShop\Infrastructure\Response\HtmlResponse;
use DemoShop\Business\Encryption\EncryptorInterface;
use DemoShop\Data\Repository\AdminRepository;
use DemoShop\Data\Repository\AdminAuthTokenRepository;
use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

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

    }

    /**
     * Registers repositories
     *
     * @throws Exception
     */
    private static function registerRepositories(): void
    {
        ServiceRegistry::set(AdminRepositoryInterface::class,
            new AdminRepository(ServiceRegistry::get(EncryptorInterface::class))
        );
        ServiceRegistry::set(CategoryRepositoryInterface::class,
            new CategoryRepository()
        );
        ServiceRegistry::set(AdminAuthTokenRepositoryInterface::class,
            new AdminAuthTokenRepository()
        );
    }

    /**
     * Registers services
     *
     * @throws Exception
     */
    private static function registerServices(): void
    {
        ServiceRegistry::set(AuthServiceInterface::class,
            new AuthService(
                ServiceRegistry::get(AdminRepositoryInterface::class),
                ServiceRegistry::get(AdminAuthTokenRepositoryInterface::class),
                ServiceRegistry::get(EncryptorInterface::class)),
        );
        ServiceRegistry::set(CategoryServiceInterface::class,
            new CategoryService(
                ServiceRegistry::get(CategoryRepositoryInterface::class),
            )
        );
        ServiceRegistry::set(DashboardServiceInterface::class,
            new DashboardService()
        );
    }

    /**
     * Registers controllers
     *
     * @throws Exception
     */
    private static function registerControllers(): void
    {
        ServiceRegistry::set(ViewController::class,
            new ViewController()
        );
        ServiceRegistry::set(AdminController::class,
            new AdminController(ServiceRegistry::get(DashboardServiceInterface::class)),
        );
        ServiceRegistry::set(AuthController::class,
            new AuthController(ServiceRegistry::get(AuthServiceInterface::class)),
        );
        ServiceRegistry::set(CategoryController::class,
            new CategoryController(ServiceRegistry::get(CategoryServiceInterface::class)),
        );
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
        $middleware = new ValidateMiddleware(
            ServiceRegistry::get(AuthServiceInterface::class)
        );

        $middleware
            ->linkWith(new AlreadyLoggedInMiddleware(
                ServiceRegistry::get(AuthServiceInterface::class)
            ));
        ServiceRegistry::set('adminLoginPipeline', $middleware);
    }

    /**
     * Register authorize middleware pipeline
     *
     * @throws Exception
     */
    private static function registerAuthorizeMiddleware(): void
    {
        $middleware = new AuthorizeMiddleware(
            ServiceRegistry::get(AuthServiceInterface::class)
        );
        ServiceRegistry::set('adminAuthorizePipeline', $middleware);
    }

    /**
     * Register already logged in middleware pipeline
     *
     * @throws Exception
     */
    private static function registerAlreadyLoggedInMiddleware(): void
    {
        $middleware = new AlreadyLoggedInMiddleware(
            ServiceRegistry::get(AuthServiceInterface::class)
        );
        ServiceRegistry::set('adminCheckLoginPipeline', $middleware);
    }
}
