<?php

namespace DemoShop\Infrastructure\Middleware\Authorize;

use DemoShop\Business\Service\AuthServiceInterface;
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
     *
     * @param AuthServiceInterface $authService
     */
    public function __construct(AuthServiceInterface $authService)
    {
        $this->authService = $authService;
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
