<?php

namespace DemoShop\src\Infrastructure\Router;

use DemoShop\src\Infrastructure\DI\ServiceRegistry;
use DemoShop\src\Infrastructure\Middleware\Exception\AlreadyLoggedInException;
use DemoShop\src\Infrastructure\Middleware\Exception\AuthorizeException;
use DemoShop\src\Infrastructure\Middleware\Exception\CredentialsInvalidException;
use DemoShop\src\Infrastructure\Request\Request;
use DemoShop\src\Infrastructure\Response\HtmlResponse;
use DemoShop\src\Infrastructure\Response\RedirectionResponse;
use DemoShop\src\Infrastructure\Router\DTO\Route;
use Exception;
use RuntimeException;
use Throwable;

class RouteDispatcher
{
    /**
     * Dispatches the provided request to the appropriate handler.
     *
     * @param Request $request
     *
     * @throws Exception
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
            $this->callHandlerMethod($matchedRoute, $request);
        } catch (CredentialsInvalidException $e) {
            error_log("RouteDispatcher: Credentials are not valid. Error: " . $e->getMessage());
            $this->callHandlerMethod($matchedRoute, $request, 'loginPage');
        } catch (AuthorizeException $e) {
            error_log("RouteDispatcher: Authorization required for route
             '{$matchedRoute->getPathPattern()}'. Error: " . $e->getMessage());
            $response = new RedirectionResponse('/login');
            $response->view();
        } catch (AlreadyLoggedInException $e) {
            error_log("RouteDispatcher: Already logged in. " .
                "'{$matchedRoute->getPathPattern()}'. Error: " . $e->getMessage());
            $response = new RedirectionResponse('/admin');
            $response->view();
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            error_log(
                "RouteDispatcher: Unhandled exception during dispatch for route " .
                "'{$matchedRoute->getPathPattern()}'. Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw new RuntimeException("Error processing request: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Matches the request route with a route from the route list
     *
     * @param Request $request
     *
     * @return Route|null
     */
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
     * Calls the method of the provided handler
     *
     * @param Route $matchedRoute
     * @param Request $request
     * @param string|null $method
     *
     * @return void
     */
    private function callHandlerMethod(Route $matchedRoute, Request $request, string $method = null): void
    {
        $handlerClass = $matchedRoute->getHandlerClass();
        $handlerMethod = $method ?? $matchedRoute->getHandlerMethod();

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
    }

    /**
     * Calls the check method of all the middleware inside route
     *
     * @param Route $matchedRoute
     * @param Request $request
     *
     * @throws AlreadyLoggedInException
     * @throws AuthorizeException
     * @throws CredentialsInvalidException
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