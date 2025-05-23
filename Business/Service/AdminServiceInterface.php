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

    /**
     * Checks if username is valid
     *
     * @param string $username
     * @param array $errors
     *
     * @return bool
     */
    public function isValidUsername(string $username, array &$errors): bool;

    /**
     * Checks if password is valid
     *
     * @param string $password
     * @param array $errors
     *
     * @return bool
     */
    public function isValidPassword(string $password, array &$errors): bool;

    /**
     * Gets statistics for dashboard (dummy parameters)
     *
     * @return array
     */
    public function getDashboardData(): array;
}
