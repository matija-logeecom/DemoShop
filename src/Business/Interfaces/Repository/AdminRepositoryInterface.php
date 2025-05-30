<?php

namespace DemoShop\src\Business\Interfaces\Repository;

use DemoShop\src\Business\Model\Admin;

interface AdminRepositoryInterface
{
    /**
     * Checks if admin credentials are present in database
     *
     * @param Admin $admin
     *
     * @return int
     */
    public function authenticate(Admin $admin): int;
}