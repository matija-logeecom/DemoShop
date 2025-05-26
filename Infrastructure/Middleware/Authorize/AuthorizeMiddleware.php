<?php

namespace DemoShop\Infrastructure\Middleware\Authorize;

use DemoShop\Business\Service\AdminServiceInterface;
use DemoShop\Infrastructure\Session\SessionManager;
use DemoShop\Infrastructure\Request\Request;
use Exception;

/*
 * Middleware for admin authorization
 */

class AuthorizeMiddleware extends Middleware
{
    private AdminServiceInterface $adminService;
    private const AUTH_COOKIE_NAME = 'DEMO_SHOP_AUTH';

    public function __construct(AdminServiceInterface $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * @inheritDoc
     */
    public function check(Request $request): void
    {
       $isAuthenticated = false;
       if (isset($_COOKIE[self::AUTH_COOKIE_NAME])) {
           $cookieValue = $_COOKIE[self::AUTH_COOKIE_NAME];
           $parts = explode(':', $cookieValue, 2);

           if (count($parts) === 2) {
               $selector = $parts[0];
               $validatorFromCookie = $parts[1];

               $adminId = $this->adminService->validateAuthToken($selector, $validatorFromCookie);
               if ($adminId !== null && $adminId > 0) {
                   $isAuthenticated = true;
               }
           }
       }

       if (!$isAuthenticated) {
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
