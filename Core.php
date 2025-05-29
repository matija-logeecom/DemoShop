<?php

namespace DemoShop;

use DemoShop\Infrastructure\Bootstrap;
use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Infrastructure\Middleware\Exception\AlreadyLoggedInException;
use DemoShop\Infrastructure\Middleware\Exception\AuthorizeException;
use DemoShop\Infrastructure\Request\Request;
use DemoShop\Infrastructure\Response\RedirectionResponse;
use DemoShop\Infrastructure\Response\Response;
use DemoShop\Infrastructure\Router\RouteDispatcher;
use DemoShop\Infrastructure\Response\HtmlResponse;
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
            HtmlResponse::createInternalServerError();
        }
    }
}