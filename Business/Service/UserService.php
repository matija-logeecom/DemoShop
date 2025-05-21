<?php

namespace DemoShop\Business\Service;

use DemoShop\Business\Model\Admin;
use DemoShop\Data\Repository\UserRepository;

class UserService implements UserServiceInterface
{
    private UserRepository $repository;
    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function authenticate(Admin $admin): bool
    {
        return $this->repository->authenticate($admin);
    }


}