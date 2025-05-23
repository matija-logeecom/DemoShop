<?php

namespace DemoShop\Infrastructure\Middleware\Authorize;

use DemoShop\Infrastructure\Session\SessionManager;
use DemoShop\Infrastructure\Request\Request;
use Exception;

/*
 * Middleware for admin authorization
 */

class AuthorizeMiddleware extends Middleware
{
    /**
     * @inheritDoc
     */
    public function check(Request $request): void
    {
        if (!SessionManager::getInstance()->get('adminLoggedIn')) {
            throw new Exception('You are not authorized to access this page.');
        }

        parent::check($request);
    }
}
