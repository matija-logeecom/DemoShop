<?php

namespace DemoShop\Business\Service;

use DemoShop\Business\Model\Admin;
use DemoShop\Data\Repository\AdminRepository;

/*
 * Stores business logic for admins
 */
class AdminService implements AdminServiceInterface
{
    private AdminRepository $repository;

    /**
     * Constructs Admin Service instance
     *
     * @param AdminRepository $repository
     */
    public function __construct(AdminRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(Admin $admin): bool
    {
        return $this->repository->authenticate($admin);
    }
}
