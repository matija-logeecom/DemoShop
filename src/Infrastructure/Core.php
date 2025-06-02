<?php

namespace DemoShop\Infrastructure;

use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Infrastructure\Request\Request;
use DemoShop\Infrastructure\Response\HtmlResponse;
use DemoShop\Infrastructure\Router\RouteDispatcher;
use Dotenv\Dotenv;
use Throwable;

class Core
{
    public function run(): void
    {
        try {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
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