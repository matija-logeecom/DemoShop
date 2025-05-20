<?php

namespace DemoShop\Infrastructure\Router;

use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Infrastructure\Request\Request;
use Exception;

class Router
{
    private array $routes = [];

    public function add(string $method, string $path, callable $handler): void
    {
        $paramNames = $this->extractParamNames($path);

        $this->routes[$method][] = [
            'pattern' => $this->compilePattern($path),
            'original' => $path,
            'callback' => $handler,
            'paramNames' => $paramNames
        ];
    }

    public function route(): void
    {
        try {
            $request = ServiceRegistry::get(Request::class);
            $this->dispatch($request);
        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }

    private function dispatch(Request $request): void
    {
        $uri = $request->getUri();
        $method = $request->getMethod();

        foreach ($this->routes[$method] as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_slice($matches, 1);
                $paramMap = array_combine($route['paramNames'], $params);

                $request->setRouteParams($paramMap);

                call_user_func($route['callback'], $request);

                return;
            }
        }

        http_response_code(404);
        echo "404 Not Found";
    }

    private function compilePattern(string $path): string
    {
        $regex = preg_replace('#\{(\w+)}#', '([^/]+)', $path);

        return "#^{$regex}$#";
    }

    private function extractParamNames(string $path): array
    {
        preg_match_all('#\{(\w+)\}#', $path, $matches);

        return $matches[1] ?? [];
    }
}