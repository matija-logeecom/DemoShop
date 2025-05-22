<?php

namespace DemoShop\Infrastructure\Middleware\Authorize;

use DemoShop\Infrastructure\Request\Request;
use Exception;

/*
 * Stores a blueprint for a single link in a chain of Middleware
 */

abstract class Middleware
{
    private ?Middleware $next = null;

    /**
     * Sets link to next middleware
     *
     * @param Middleware $next
     *
     * @return Middleware
     */
    public function linkWith(Middleware $next): Middleware
    {
        $this->next = $next;

        return $next;
    }

    /**
     * Checks if the conditions are passed and calls the next link in the chain
     *
     * @param Request $request
     *
     * @throws Exception
     */
    public function check(Request $request): void
    {
        if (!$this->next) {
            return;
        }

        $this->next->check($request);
    }
}
