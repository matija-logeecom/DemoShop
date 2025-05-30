<?php

namespace DemoShop;

use DemoShop\src\Infrastructure\Bootstrap;
use DemoShop\src\Infrastructure\DI\ServiceRegistry;
use DemoShop\src\Infrastructure\Request\Request;
use DemoShop\src\Infrastructure\Response\HtmlResponse;
use DemoShop\src\Infrastructure\Router\RouteDispatcher;
use Dotenv\Dotenv;
use Throwable;

class Core
{
    public function run(): void
    {
        try {
            $dotenv = Dotenv::createImmutable(__DIR__);
            $dotenv->load();

            Bootstrap::init();
            $request = ServiceRegistry::get(Request::class);
            $dispatcher = ServiceRegistry::get(RouteDispatcher::class);
            $dispatcher->dispatch($request);
        } catch (Throwable $e) {
            HtmlResponse::createInternalServerError()->view();
        }
    }
}