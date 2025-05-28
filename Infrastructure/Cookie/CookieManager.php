<?php

namespace DemoShop\Infrastructure\Cookie;

class CookieManager
{
    private static ?CookieManager $instance = null;

    public static function getInstance(): CookieManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $name): mixed
    {
        return $_COOKIE[$name] ?? null;
    }

    public function set(string $name, string $value, int $expiry, bool $secure = false): void
    {
        setcookie($name, $value, [
            'expires' => $expiry,
            'path' => '/',
            'domain' => '',
            'secure' =>  $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    public function setEmptyCookie(string $name, bool $secure = false): void
    {
        setcookie($name, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}