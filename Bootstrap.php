<?php

namespace DemoShop;

use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Presentation\Controller\AdminController;
use DemoShop\Presentation\Controller\ViewController;
use DemoShop\Infrastructure\Router\Router;
use DemoShop\Infrastructure\Request\Request;
use DemoShop\Infrastructure\Response\HtmlResponse;
use DemoShop\Business\Service\UserServiceInterface;
use DemoShop\Business\Service\UserService;
use DemoShop\Data\Repository\UserRepository;
use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class Bootstrap
{
    /**
     * @throws Exception
     */
    public static function init(): void
    {
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();

        self::registerRepositories();
        self::registerServices();
        self::registerControllers();

        self::initEloquent();

        self::registerRoutes();

        self::registerRequest();

    }

    private static function registerRepositories(): void
    {
        ServiceRegistry::set(UserRepository::class, new UserRepository());
    }

    /**
     * @throws Exception
     */
    private static function registerServices(): void
    {
        ServiceRegistry::set(UserServiceInterface::class,
            new UserService(
                ServiceRegistry::get(UserRepository::class)
            ));
    }

    /**
     * @throws Exception
     */
    private static function registerControllers(): void
    {
        ServiceRegistry::set(ViewController::class,
            new ViewController()
        );
        ServiceRegistry::set(AdminController::class,
            new AdminController(
                ServiceRegistry::get(UserServiceInterface::class),
            ));
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
}