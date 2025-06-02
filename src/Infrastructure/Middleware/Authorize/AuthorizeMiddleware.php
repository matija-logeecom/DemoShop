<?php

namespace DemoShop\Infrastructure\Middleware\Authorize;

use DemoShop\Business\Interfaces\Service\AuthServiceInterface;
use DemoShop\Infrastructure\Cookie\CookieManager;
use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Infrastructure\Middleware\Exception\AuthorizeException;
use DemoShop\Infrastructure\Request\Request;

/*
 * Middleware for admin authorization
 */

class AuthorizeMiddleware extends Middleware
{
    private AuthServiceInterface $authService;

    public function __construct()
    {
        try {
            $this->authService = ServiceRegistry::get(AuthServiceInterface::class);
        } catch (\Exception $e) {
            error_log("CRITICAL: AuthorizeMiddleware could not be initialized.
             Failed to get AuthService service. Original error: " . $e->getMessage());
            throw new \RuntimeException(
                "AuthorizeMiddleware failed to initialize due to a
                 missing critical dependency.",
                0, $e
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function check(Request $request): void
    {
        $adminId = parent::getAdminIdFromCookie($this->authService);

        if ($adminId === null || $adminId <= 0) {
            if (CookieManager::getInstance()->get(self::AUTH_COOKIE_NAME) !== null) {
                CookieManager::getInstance()->setEmptyCookie(self::AUTH_COOKIE_NAME,
                    $request->getServer()['HTTPS']);
            }
            throw new AuthorizeException('You are not authorized to access this page.');
        }

        parent::check($request);
    }
}
