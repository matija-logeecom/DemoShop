<?php

namespace DemoShop\src\Infrastructure\Router\DTO;

class Route
{
    private string $httpMethod;
    private string $pathPattern;
    private string $handlerClass;
    private string $handlerMethod;
    private array $middlewareKeys;
    private string $regexPattern;
    private array $paramNames;

    /**
     * @param string $httpMethod
     * @param string $pathPattern
     * @param array $handler
     * @param array $middlewareKeys
     */
    public function __construct(
        string $httpMethod,
        string $pathPattern,
        array  $handler,
        array  $middlewareKeys = []
    )
    {
        $this->httpMethod = strtoupper($httpMethod);
        $this->pathPattern = $pathPattern;

        if (count($handler) !== 2 || !is_string($handler[0]) || !is_string($handler[1])) {
            throw new \InvalidArgumentException(
                'Handler must be an array of [ControllerClassName::class, "methodName"].');
        }
        $this->handlerClass = $handler[0];
        $this->handlerMethod = $handler[1];

        $this->middlewareKeys = $middlewareKeys;

        $this->paramNames = self::extractParamNames($this->pathPattern);
        $this->regexPattern = self::compilePattern($this->pathPattern);
    }

    /**
     * @return string
     */
    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    /**
     * @return string
     */
    public function getPathPattern(): string
    {
        return $this->pathPattern;
    }

    /**
     * @return string
     */
    public function getHandlerClass(): string
    {
        return $this->handlerClass;
    }

    /**
     * @return string
     */
    public function getHandlerMethod(): string
    {
        return $this->handlerMethod;
    }

    /**
     * @return array
     */
    public function getMiddlewareKeys(): array
    {
        return $this->middlewareKeys;
    }

    /**
     * @return string
     */
    public function getRegexPattern(): string
    {
        return $this->regexPattern;
    }

    /**
     * @return array
     */
    public function getParamNames(): array
    {
        return $this->paramNames;
    }

    /**
     * Generates a regex pattern based on the provided URL path.
     *
     * @param string $path
     *
     * @return string
     */
    private static function compilePattern(string $path): string
    {
        $regex = preg_replace('#\{(\w+)\}#', '([^/]+)', $path);
        return "#^{$regex}$#u";
    }

    /**
     * Extracts parameter names from the provided URL path.
     *
     * @param string $path
     *
     * @return array
     */
    private static function extractParamNames(string $path): array
    {
        preg_match_all('#\{(\w+)\}#', $path, $matches);
        return $matches[1] ?? [];
    }
}
