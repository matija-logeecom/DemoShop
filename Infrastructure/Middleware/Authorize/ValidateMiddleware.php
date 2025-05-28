<?php

namespace DemoShop\Infrastructure\Middleware\Authorize;

use DemoShop\Business\Interfaces\Service\AuthServiceInterface;
use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Infrastructure\Request\Request;
use Exception;

/*
 * Middleware for username and password validation
 */

class ValidateMiddleware extends Middleware
{
    private AuthServiceInterface $authService;

    /**
     * Constructs Validate Middleware instance
     */
    public function __construct()
    {
        try {
            $this->authService = ServiceRegistry::get(AuthServiceInterface::class);
        } catch (\Exception $e) {
            error_log("CRITICAL: ValidateMiddleware could not be initialized.
             Failed to get AuthService service. Original error: " . $e->getMessage());
            throw new \RuntimeException(
                "ValidateMiddleware failed to initialize due to a
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
        $errors['username'] = '';
        $errors['password'] = '';

        $username = trim($request->getBody()['username']) ?? '';
        $password = trim($request->getBody()['password']) ?? '';
        $validUsername = $this->authService->isValidUsername($username, $errors);
        $validPassword = $this->authService->isValidPassword($password, $errors);

        if (!$validUsername || !$validPassword) {
            $request->setRouteParams([
                'username' => $username,
                'usernameError' => $errors['username'],
                'passwordError' => $errors['password'],
            ]);

            throw new Exception('Username and password are not valid');
        }

        parent::check($request);
    }
}
