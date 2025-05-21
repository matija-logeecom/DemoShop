<?php

namespace DemoShop\Data\Repository;

use DemoShop\Business\Model\Admin;
use DemoShop\Data\Model\Admin as Entity;

class UserRepository
{
    public function authenticate(Admin $admin): bool
    {
        $user = Entity::where('username', $admin->getUsername())->first();

        if (!$user) {
            return false;
        }

        return $user->password === $admin->getPassword();
    }
}