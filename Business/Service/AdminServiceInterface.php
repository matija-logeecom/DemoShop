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
     * @return int
     */
    public function authenticate(Admin $admin): int;

    public function handleLoginAndCreateAuthToken(int $adminId): array;

    public function validateAuthToken(string $selector, string $validatorFromCookie): ?int;

    public function handleLogout(string $selector): bool;


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

    public function updateCategory(array $data): bool;

    public function deleteCategory(int $id): bool;
}
