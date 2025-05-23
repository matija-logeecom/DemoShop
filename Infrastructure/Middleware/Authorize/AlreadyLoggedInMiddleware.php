<?php

namespace DemoShop\Infrastructure\Middleware\Authorize;

use DemoShop\Infrastructure\Session\SessionManager;
use DemoShop\Infrastructure\Request\Request;
use Exception;

/*
 * Checks if the user is already logged in
 */

class AlreadyLoggedInMiddleware extends Middleware
{
    /**
     * @inheritDoc
     */
    public function check(Request $request): void
    {
        if (SessionManager::getInstance()->get(('adminLoggedIn'))) {
            throw new Exception('You are already logged in.');
        }

        parent::check($request);
    }
}
