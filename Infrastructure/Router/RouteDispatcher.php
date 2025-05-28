<?php

namespace DemoShop\Infrastructure\Router;

use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Infrastructure\Middleware\Exception\AlreadyLoggedInException;
use DemoShop\Infrastructure\Middleware\Exception\AuthorizeException;
use DemoShop\Infrastructure\Request\Request;
use DemoShop\Infrastructure\Response\HtmlResponse;
use DemoShop\Infrastructure\Response\Response;
use DemoShop\Infrastructure\Middleware\Authorize\Middleware;
use DemoShop\Infrastructure\Router\DTO\Route;
use Exception;
use RuntimeException;
use Throwable;

class RouteDispatcher
{
    /**
     * Dispatches the provided request to the appropriate handler.
     *
     * @param Request $request
     * @throws Exception If middleware or controller instantiation/execution fails critically.
     */
    public function dispatch(Request $request): void
    {
        $matchedRoute = $this->findRoute($request);
        if ($matchedRoute === null) {
            HtmlResponse::createNotFound()->view();
            return;
        }

        try {
            $this->handleMiddleware($matchedRoute, $request);

            $handlerClass = $matchedRoute->getHandlerClass();
            $handlerMethod = $matchedRoute->getHandlerMethod();

            if (!class_exists($handlerClass)) {
                error_log("RouteDispatcher: Controller class '{$handlerClass}' not found.");
                throw new RuntimeException("Routing error: Handler class not found.");
            }

            $controllerInstance = new $handlerClass();

            if (!method_exists($controllerInstance, $handlerMethod)) {
                error_log(
                    "RouteDispatcher: Method '{$handlerMethod}' not found in controller '{$handlerClass}'.");
                throw new RuntimeException("Routing error: Handler method not found.");
            }

            $controllerInstance->$handlerMethod($request)->view();
        } catch (AuthorizeException $e) {
            error_log("RouteDispatcher: Authorization required for route
             '{$matchedRoute->getPathPattern()}'. Error: " . $e->getMessage());
            throw $e;
        } catch (AlreadyLoggedInException $e) {
            error_log("RouteDispatcher: Already logged in.
             '{$matchedRoute->getPathPattern()}'. Error: " . $e->getMessage());
            throw $e;
        } catch (Throwable $e) {
            error_log(
                "RouteDispatcher: Unhandled exception during dispatch for route 
                '{$matchedRoute->getPathPattern()}'. Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw new RuntimeException("Error processing request: " . $e->getMessage(), 0, $e);
        }
    }
    
    private function findRoute(Request $request): ?Route
    {
        $uri = $request->getUri();
        $method = $request->getMethod();
        $matchedRoute = null;

        $routesForMethod = RouteRegistry::getRoutes($method);
        
        foreach ($routesForMethod as $route) {
            if (preg_match($route->getRegexPattern(), $uri, $matches)) {
                $matchedRoute = $route;
                $params = array_slice($matches, 1);
                $paramMap = array_combine($route->getParamNames(), $params);
                $request->setRouteParams($paramMap);
                break;
            }
        }

        return $matchedRoute;
    }

    /**
     * @throws Exception
     */
    private function handleMiddleware(Route $matchedRoute, Request $request): void
    {
        $middlewareKeys = $matchedRoute->getMiddlewareKeys();
        if (!empty($middlewareKeys)) {
            $chainHead = null;
            $previousMiddleware = null;

            foreach ($middlewareKeys as $middlewareKey) {
                $currentMiddleware = ServiceRegistry::get($middlewareKey);

                if ($chainHead === null) {
                    $chainHead = $currentMiddleware;
                }
                $previousMiddleware?->linkWith($currentMiddleware);
                $previousMiddleware = $currentMiddleware;
            }

            $chainHead?->check($request);
        }
    }
}