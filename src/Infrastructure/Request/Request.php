<?php

namespace DemoShop\src\Infrastructure\Request;

/*
 * Class containing info required for sending a request
 */

class Request
{
    private string $method;
    private string $uri;
    private array $query;
    private array $body;
    private array $server;
    private array $routeParams = [];

    /**
     * Constructs Request instance
     */
    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->query = $_GET;
        if (($this->method === 'POST' || $this->method === 'PUT') && !$_POST) {
            $this->body = json_decode(file_get_contents('php://input'), true);
        } else {
            $this->body = $_POST;
        }

        $this->server = $_SERVER;
    }

    /**
     * Getter for method parameter
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Getter for URI parameter
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Getter for query parameter
     *
     * @return array
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * Getter for body parameter
     *
     * @return array
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * Getter for server parameter
     *
     * @return array
     */
    public function getServer(): array
    {
        return $this->server;
    }

    /**
     * Getter for all route parameters
     *
     * @return array
     */
    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    /**
     * Getter for parameter with provided name
     *
     * @param string $key
     * @param $default
     *
     * @return mixed
     */
    public function getRouteParam(string $key, $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * Sets route parameters
     *
     * @param array $params
     *
     * @return void
     */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }
}
