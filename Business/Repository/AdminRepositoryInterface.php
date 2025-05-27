<?php

namespace DemoShop\Business\Repository;

use DemoShop\Business\Model\Admin;

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