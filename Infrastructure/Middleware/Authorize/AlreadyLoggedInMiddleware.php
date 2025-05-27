<?php

namespace DemoShop\Infrastructure\Middleware\Authorize;

use DemoShop\Infrastructure\Request\Request;
use DemoShop\Business\Service\AdminServiceInterface;
use Exception;

class AlreadyLoggedInMiddleware extends Middleware
{
    private AdminServiceInterface $adminService;
    // Ensure these constants match those in AdminController.php
    private const AUTH_COOKIE_NAME = 'DEMO_SHOP_AUTH';
    private const DB_TOKEN_PREFIX = 'db_token:';
    private const SESSION_PAYLOAD_PREFIX = 'session_payload:';

    public function __construct(AdminServiceInterface $adminService)
    {
        $this->adminService = $adminService;
    }

    public function check(Request $request): void
    {
        $adminId = null; // Will store the admin ID if a valid session/token is found

        if (isset($_COOKIE[self::AUTH_COOKIE_NAME])) {
            $cookieValue = $_COOKIE[self::AUTH_COOKIE_NAME];

            if (str_starts_with($cookieValue, self::DB_TOKEN_PREFIX)) {
                $tokenString = substr($cookieValue, strlen(self::DB_TOKEN_PREFIX));
                $parts = explode(':', $tokenString, 2);
                if (count($parts) === 2) {
                    $selector = $parts[0];
                    $validatorFromCookie = $parts[1];
                    $adminId = $this->adminService->validateAuthToken($selector, $validatorFromCookie);
                }
            } elseif (str_starts_with($cookieValue, self::SESSION_PAYLOAD_PREFIX)) {
                $encryptedPayload = substr($cookieValue, strlen(self::SESSION_PAYLOAD_PREFIX));
                $adminId = $this->adminService->validateEncryptedSessionPayload($encryptedPayload);
            }
        }

        if ($adminId !== null && $adminId > 0) {
            throw new Exception('You are already logged in.');
        }

        parent::check($request);
    }
}