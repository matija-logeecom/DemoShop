<?php

namespace DemoShop\Infrastructure\Router;

use DemoShop\Infrastructure\Router\DTO\Route;
use InvalidArgumentException;

// Import the DTO

class RouteRegistry
{
    /**
     * @var array<string, Route[]>
     */
    private static array $routes = [];

    /**
     * Adds a route definition to the registry.
     *
     * @param string $httpMethod HTTP method (GET, POST, etc.)
     * @param string $pathPattern The route path pattern (e.g., /users/{id})
     * @param array $handler An array containing [ControllerClassName::class, 'methodName']
     * @param array $middlewareKeys An array of string keys for middleware services
     * @throws InvalidArgumentException
     */
    public static function add(string $httpMethod, string $pathPattern, array $handler, array $middlewareKeys = []): void
    {
        $route = new Route($httpMethod, $pathPattern, $handler, $middlewareKeys);

        self::$routes[$httpMethod][] = $route;
    }

    /**
     * Gets all registered routes, optionally filtered by HTTP method.
     *
     * @param string|null $httpMethod Optional HTTP method to filter routes.
     * @return Route[]|array<string, Route[]>
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

    // Note: The compilePattern() and extractParamNames() methods were in RouteDefinition.
    // If you prefer them here for a central utility, you can move them:
    // public static function compilePattern(string $path): string { ... }
    // public static function extractParamNames(string $path): array { ... }
    // And then RouteDefinition would call RouteRegistry::compilePattern() etc.
    // For now, keeping them in RouteDefinition is fine as it makes the DTO self-initializing.
}