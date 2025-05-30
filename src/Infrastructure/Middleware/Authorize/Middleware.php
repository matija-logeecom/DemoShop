<?php

namespace DemoShop\src\Infrastructure\Middleware\Authorize;

use DemoShop\src\Business\Interfaces\Service\AuthServiceInterface;
use DemoShop\src\Infrastructure\Request\Request;
use Exception;

/*
 * Stores a blueprint for a single link in a chain of Middleware
 */

abstract class Middleware
{
    private ?Middleware $next = null;
    protected const AUTH_COOKIE_NAME = 'DEMO_SHOP_AUTH';
    protected const DB_TOKEN_PREFIX = 'db_token:';
    protected const SESSION_PAYLOAD_PREFIX = 'session_payload:';

    /**
     * Sets link to next middleware
     *
     * @param Middleware $next
     *
     * @return Middleware
     */
    public function linkWith(Middleware $next): Middleware
    {
        $this->next = $next;

        return $next;
    }

    /**
     * Checks if the conditions are passed and calls the next link in the chain
     *
     * @param Request $request
     *
     * @throws Exception
     */
    public function check(Request $request): void
    {
        if (!$this->next) {
            return;
        }

        $this->next->check($request);
    }

    /**
     * Retrieves admin ID from cookie
     *
     * @param AuthServiceInterface $authService
     *
     * @return int|null
     */
    protected final function getAdminIdFromCookie(AuthServiceInterface $authService): ?int
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
                    $adminId = $authService->validateAuthToken($selector, $validatorFromCookie);
                }
            }
            if (str_starts_with($cookieValue, self::SESSION_PAYLOAD_PREFIX)) {
                $encryptedPayload = substr($cookieValue, strlen(self::SESSION_PAYLOAD_PREFIX));
                $adminId = $authService->validateEncryptedSessionPayload($encryptedPayload);
            }
        }

        return $adminId;
    }
}
