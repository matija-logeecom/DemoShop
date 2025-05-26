<?php

namespace DemoShop\Infrastructure\Router;

use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Infrastructure\Request\Request;
use DemoShop\Infrastructure\Response\HtmlResponse;
use DemoShop\Presentation\Controller\AdminController;
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
     * @param string|null $middlewareName
     *
     * @return void
     */
    public function add(string $method, string $path, callable $handler, ?string $middlewareName = null): void
    {
        $paramNames = $this->extractParamNames($path);

        $this->routes[$method][] = [
            'pattern' => $this->compilePattern($path),
            'original' => $path,
            'callback' => $handler,
            'paramNames' => $paramNames,
            'middlewareName' => $middlewareName
        ];
    }

    /**
     * Dispatches the current request
     *
     * @throws Exception
     */
    public function route(): void
    {
        try {
            $request = ServiceRegistry::get(Request::class);
            $this->dispatch($request);
        } catch (Exception $e) {
            if (!isset($request)) {
                HtmlResponse::createInternalServerError();
                return;
            }

            $controller = ServiceRegistry::get(AdminController::class);
            if ($e->getMessage() === 'You are already logged in.') {
                $controller->adminPage()->view();
                return;
            }

            $controller->loginPage($request)->view();
        }
    }

    /**
     * Dispatches the provided request
     *
     * @param Request $request
     *
     * @throws Exception
     */
    private function dispatch(Request $request): void
    {
        $uri = $request->getUri();
        $method = $request->getMethod();

        foreach ($this->routes[$method] as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                if (!empty($route['middlewareName'])) {
                    $chain = ServiceRegistry::get($route['middlewareName']);
                    $chain->check($request);
                }

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
