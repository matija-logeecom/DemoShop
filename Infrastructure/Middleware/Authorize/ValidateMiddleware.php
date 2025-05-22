<?php

namespace DemoShop\Infrastructure\Middleware\Authorize;

use DemoShop\Business\Service\AdminServiceInterface;
use DemoShop\Infrastructure\Request\Request;
use Exception;

/*
 * Middleware for username and password validation
 */

class ValidateMiddleware extends Middleware
{
    private AdminServiceInterface $adminService;

    /**
     * Constructs Validate Middleware instance
     *
     * @param AdminServiceInterface $adminService
     */
    public function __construct(AdminServiceInterface $adminService)
    {
        $this->adminService = $adminService;
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
        $validUsername = $this->adminService->isValidUsername($username, $errors);
        $validPassword = $this->adminService->isValidPassword($password, $errors);

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
