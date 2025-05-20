<?php

namespace DemoShop\Infrastructure\Router;

use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Infrastructure\Request\Request;
use DemoShop\Infrastructure\Response\HtmlResponse;
use Exception;

/*
 * Class responsible for routing
 */

class Router
{
    private array $routes = [];

    /**
     * Adds route to routes array
     *
     * @param string $method
     * @param string $path
     * @param callable $handler
     *
     * @return void
     */
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

    /**
     * Dispatches the current request
     *
     * @return void
     */
    public function route(): void
    {
        try {
            $request = ServiceRegistry::get(Request::class);
            $this->dispatch($request);
        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }

    /**
     * Dispatches the provided request
     *
     * @param Request $request \
     *
     * @return void
     */
    private function dispatch(Request $request): void
    {
        $uri = $request->getUri();
        $method = $request->getMethod();

        foreach ($this->routes[$method] as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_slice($matches, 1);
                $paramMap = array_combine($route['paramNames'], $params);

                $request->setRouteParams($paramMap);

                call_user_func($route['callback'], $request)->view();

                return;
            }
        }

        HtmlResponse::createNotFound()->view();
    }

    /**
     * Generates a regex pattern based on the provided URL
     *
     * @param string $path
     *
     * @return string
     */
    private function compilePattern(string $path): string
    {
        $regex = preg_replace('#\{(\w+)\}#', '([^/]+)', $path);

        return "#^{$regex}$#";
    }

    /**
     * Extracts parameter names from the provided URL
     *
     * @param string $path
     *
     * @return array
     */
    private function extractParamNames(string $path): array
    {
        preg_match_all('#\{(\w+)\}#', $path, $matches);

        return $matches[1] ?? [];
    }
}
