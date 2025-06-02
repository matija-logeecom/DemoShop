<?php

namespace DemoShop\Infrastructure\Router;

use DemoShop\Infrastructure\Router\DTO\Route;
use InvalidArgumentException;

class RouteRegistry
{
    private static array $routes = [];

    /**
     * Adds a route definition to the registry.
     *
     * @param string $httpMethod
     * @param string $pathPattern
     * @param array $handler
     * @param array $middlewareKeys
     *
     * @throws InvalidArgumentException
     */
    public static function add(string $httpMethod, string $pathPattern,
                               array  $handler, array $middlewareKeys = []): void
    {
        $route = new Route($httpMethod, $pathPattern, $handler, $middlewareKeys);

        self::$routes[$httpMethod][] = $route;
    }

    /**
     * Gets all registered routes, optionally filtered by HTTP method.
     *
     * @param string|null $httpMethod
     *
     * @return Route[]
     */
    public static function getRoutes(?string $httpMethod = null): array
    {
        if ($httpMethod !== null) {
            $httpMethod = strtoupper($httpMethod);
            return self::$routes[$httpMethod] ?? [];
        }
        return self::$routes;
    }

    /**
     * Clears all registered routes. Useful for testing or dynamic route rebuilding.
     */
    public static function reset(): void
    {
        self::$routes = [];
    }
}