<?php

namespace DemoShop\Infrastructure\Middleware\Authorize;

use DemoShop\Business\Service\AdminServiceInterface;
use DemoShop\Infrastructure\Session\SessionManager;
use DemoShop\Infrastructure\Request\Request;
use Exception;

/*
 * Checks if the user is already logged in
 */

class AlreadyLoggedInMiddleware extends Middleware
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
        $isLoggedIn = false;
        if (isset($_COOKIE[self::AUTH_COOKIE_NAME])) {
            $cookieValue = $_COOKIE[self::AUTH_COOKIE_NAME];
            $parts = explode(':', $cookieValue, 2);

            if (count($parts) === 2) {
                $selector = $parts[0];
                $validatorFromCookie = $parts[1];

                $adminId = $this->adminService->validateAuthToken($selector, $validatorFromCookie);
                if ($adminId !== null && $adminId > 0) {
                    $isLoggedIn = true;
                }
            }
        }

        if ($isLoggedIn) {
            throw new Exception('You are already logged in.');
        }

        parent::check($request);
    }
}
