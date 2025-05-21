<?php

namespace DemoShop\Business\Service;

use DemoShop\Business\Model\Admin;

interface UserServiceInterface
{
    public function authenticate(Admin $admin): bool;
}