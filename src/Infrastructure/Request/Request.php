<?php

namespace DemoShop\Infrastructure\Request;

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
//        $this->method = $_SERVER['REQUEST_METHOD'];
//        $this->uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
//        $this->query = $_GET;
//        if (($this->method === 'POST' || $this->method === 'PUT') && !$_POST) {
//            $this->body = json_decode(file_get_contents('php://input'), true);
//        } else {
//            $this->body = $_POST;
//        }
//
//        $this->server = $_SERVER;

        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->query = $_GET;
        $this->server = $_SERVER;
        $this->body = []; // Default to empty

        if ($this->method === 'POST' || $this->method === 'PUT' || $this->method === 'PATCH' || $this->method === 'DELETE') {
            $contentType = trim(strtolower($_SERVER['CONTENT_TYPE'] ?? ''));

            // If Content-Type indicates JSON (common for DELETE with body, PUT, PATCH, some POST)
            if (str_contains($contentType, 'application/json')) {
                $input = file_get_contents('php://input');
                if ($input !== false) {
                    $decodedJson = json_decode($input, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $this->body = $decodedJson;
                    } else {
                        // Log error if JSON is malformed but Content-Type was application/json
                        error_log("Request class: Failed to decode JSON input for {$this->method} request. Error: " . json_last_error_msg());
                    }
                }
            }
            // For POST with form data (multipart/form-data or x-www-form-urlencoded)
            // $_POST is primarily for POST. DELETE with multipart/form-data is not standard.
            // If it's not JSON and $this->body is still empty, check $_POST (mostly relevant for POST).
            elseif ($this->method === 'POST' && !empty($_POST)) {
                $this->body = $_POST;
            }
            // If it's a DELETE request not identified as JSON, and you still expect
            // URL-encoded parameters in the body (less common for DELETE but possible),
            // you might need to parse php://input with parse_str if $_POST is empty.
            // However, for DELETE, a JSON body is more standard if a body is used.
            // The current logic prioritizes JSON for DELETE if the header is set.
            // If Content-Type for DELETE is not 'application/json', $this->body would remain [].
        }
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
