<?php

namespace DemoShop\Infrastructure\Middleware\Authorize;

use DemoShop\Business\Interfaces\Service\AuthServiceInterface;
use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Infrastructure\Middleware\Exception\AlreadyLoggedInException;
use DemoShop\Infrastructure\Request\Request;
use Exception;
use RuntimeException;

class AlreadyLoggedInMiddleware extends Middleware
{
    private authServiceInterface $authService;

    public function __construct()
    {
        try {
            $this->authService = ServiceRegistry::get(AuthServiceInterface::class);
        } catch (Exception $e) {
            error_log("CRITICAL: AlreadyLoggedInMiddleware could not be initialized.
             Failed to get AuthService service. Original error: " . $e->getMessage());
            throw new RuntimeException(
                "AlreadyLoggedInMiddleware failed to initialize due to a
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
        $adminId = parent::getAdminIdFromCookie($request, $this->authService);

        if ($adminId !== null && $adminId > 0) {
            throw new AlreadyLoggedInException('You are already logged in.');
        }

        parent::check($request);
    }
}