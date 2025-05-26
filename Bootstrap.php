<?php

namespace DemoShop;

use DemoShop\Data\Repository\CategoryRepository;
use DemoShop\Infrastructure\Session\SessionManager;
use DemoShop\Data\Encryption\Encryptor;
use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Infrastructure\Middleware\Authorize\AlreadyLoggedInMiddleware;
use DemoShop\Infrastructure\Middleware\Authorize\AuthorizeMiddleware;
use DemoShop\Infrastructure\Middleware\Authorize\ValidateMiddleware;
use DemoShop\Presentation\Controller\AdminController;
use DemoShop\Presentation\Controller\ViewController;
use DemoShop\Infrastructure\Router\Router;
use DemoShop\Infrastructure\Request\Request;
use DemoShop\Infrastructure\Response\HtmlResponse;
use DemoShop\Business\Service\AdminServiceInterface;
use DemoShop\Business\Service\AdminService;
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
//        if (!SessionManager::getInstance()->has('adminLoggedIn')) {
//            SessionManager::getInstance()->set('adminLoggedIn', false);
//        }

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
        ServiceRegistry::set(AdminRepository::class,
            new AdminRepository(ServiceRegistry::get(EncryptorInterface::class))
        );
        ServiceRegistry::set(CategoryRepository::class,
            new CategoryRepository()
        );
        ServiceRegistry::set(AdminAuthTokenRepository::class,
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
        ServiceRegistry::set(AdminServiceInterface::class,
            new AdminService(
                ServiceRegistry::get(AdminRepository::class),
                ServiceRegistry::get(CategoryRepository::class),
                ServiceRegistry::get(AdminAuthTokenRepository::class)
            ));
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
            new AdminController(
                ServiceRegistry::get(AdminServiceInterface::class),
            ));
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

            $router->add('GET', '/', [$viewController, 'landingPage']);
            $router->add(
                'GET',
                '/login',
                [$adminController, 'loginPage'],
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
                [$adminController, 'sendLoginInfo'],
                'adminLoginPipeline'
            );
            $router->add(
                'GET',
                '/logout',
                [$adminController, 'logout']
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
                [$adminController, 'createCategory'],
                'adminAuthorizePipeline'
            );
            $router->add(
                'GET',
                '/api/categories',
                [$adminController, 'getCategories'],
                'adminAuthorizePipeline'
            );
            $router->add(
                'PUT',
                '/api/update/{id}',
                [$adminController, 'updateCategory'],
                'adminAuthorizePipeline'
            );
            $router->add('DELETE',
                '/api/delete/{id}',
                [$adminController, 'deleteCategory'],
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
            ServiceRegistry::get(AdminServiceInterface::class)
        );

        $middleware
            ->linkWith(new AlreadyLoggedInMiddleware(
                ServiceRegistry::get(AdminServiceInterface::class)
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
            ServiceRegistry::get(AdminServiceInterface::class)
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
            ServiceRegistry::get(AdminServiceInterface::class)
        );
        ServiceRegistry::set('adminCheckLoginPipeline', $middleware);
    }
}
