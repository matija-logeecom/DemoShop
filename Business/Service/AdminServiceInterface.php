<?php

namespace DemoShop\Business\Service;

use DemoShop\Business\Model\Admin;

interface AdminServiceInterface
{
    /**
     * Checks if admin credentials are stored
     *
     * @param Admin $admin
     *
     * @return bool
     */
    public function authenticate(Admin $admin): bool;
}
