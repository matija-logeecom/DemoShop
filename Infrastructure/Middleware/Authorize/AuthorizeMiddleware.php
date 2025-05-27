<?php

namespace DemoShop\Infrastructure\Middleware\Authorize;

use DemoShop\Business\Service\AuthServiceInterface;
use DemoShop\Infrastructure\Request\Request;
use Exception;

/*
 * Middleware for admin authorization
 */

class AuthorizeMiddleware extends Middleware
{
    private AuthServiceInterface $authService;
    private const AUTH_COOKIE_NAME = 'DEMO_SHOP_AUTH';
    private const DB_TOKEN_PREFIX = 'db_token:';
    private const SESSION_PAYLOAD_PREFIX = 'session_payload:';

    public function __construct(AuthServiceInterface $authService)
    {
        $this->authService = $authService;
    }

    /**
     * @inheritDoc
     */
    public function check(Request $request): void
    {
        $adminId = null;

       if (isset($_COOKIE[self::AUTH_COOKIE_NAME])) {
           $cookieValue = $_COOKIE[self::AUTH_COOKIE_NAME];

           if (str_starts_with($cookieValue, self::DB_TOKEN_PREFIX)) {
               $tokenString = substr($cookieValue, strlen(self::DB_TOKEN_PREFIX));
               $parts = explode(':', $tokenString, 2);
               if (count($parts) === 2) {
                   $selector = $parts[0];
                   $validatorFromCookie = $parts[1];
                   $adminId = $this->authService->validateAuthToken($selector, $validatorFromCookie);
               }
           }

           if (str_starts_with($cookieValue, self::SESSION_PAYLOAD_PREFIX)) {
               $encryptedPayload = substr($cookieValue, strlen(self::SESSION_PAYLOAD_PREFIX));
               $adminId = $this->authService->validateEncryptedSessionPayload($encryptedPayload);
           }
       }

       if ($adminId === null || $adminId <= 0) {
           if (isset($_COOKIE[self::AUTH_COOKIE_NAME])) {
               setcookie(self::AUTH_COOKIE_NAME, '', [
                   'expires' => time() - 3600,
                   'path' => '/',
                   'domain' => '',
                   'secure' => $request->getServer()['HTTPS'] ?? false,
                   'httponly' => true,
                   'samesite' => 'Lax'
               ]);
           }
           throw new Exception('You are not authorized to access this page.');
       }
        parent::check($request);
    }
}
