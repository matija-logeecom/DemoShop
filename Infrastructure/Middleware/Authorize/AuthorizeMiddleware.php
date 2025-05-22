<?php

namespace DemoShop\Infrastructure\Middleware\Authorize;

use DemoShop\Business\Service\AdminServiceInterface;
use DemoShop\Business\Model\Admin;
use DemoShop\Infrastructure\Request\Request;
use Exception;

/*
 * Middleware for admin authorization
 */

class AuthorizeMiddleware extends Middleware
{
    private AdminServiceInterface $adminService;

    /**
     * Constructs Authorize Middleware instance
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
        $username = trim($request->getBody()['username']) ?? '';
        $password = trim($request->getBody()['password']) ?? '';
        $admin = new Admin($username, $password);

        if (!$this->adminService->authenticate($admin)) {
            $request->setRouteParams([
                'username' => $username,
                'passwordError' => 'Username and password do not match.',
            ]);

            throw new Exception('User is not recognized as admin');
        }

        parent::check($request);
    }
}
