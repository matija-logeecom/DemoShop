<?php

namespace DemoShop\Infrastructure\Request;

class Request
{
    private string $method;
    private string $uri;
    private array $query;
    private array $body;
    private array $server;
    private array $routeParams = [];

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->query = $_GET;
        $this->body = $_POST;
        $this->server = $_SERVER;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getQuery(): array
    {
        return $this->query;
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function getServer(): array
    {
        return $this->server;
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    public function getRouteParam(string $key, $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }
}