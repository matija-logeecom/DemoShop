<?php

namespace DemoShop\src\Business\Interfaces\Service;

use DemoShop\src\Business\Model\Admin;

interface AuthServiceInterface
{
    /**
     * Authenticates user
     *
     * @param Admin $admin
     *
     * @return int
     */
    public function authenticate(Admin $admin): int;

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
     * Creates and stores token in database
     *
     * @param int $adminId
     *
     * @return array
     */
    public function handleLoginAndCreateAuthToken(int $adminId): array;

    /**
     * Validates provided token
     *
     * @param string $selector
     * @param string $validatorFromCookie
     *
     * @return int|null
     */
    public function validateAuthToken(string $selector, string $validatorFromCookie): ?int;

    /**
     * Deletes token from database based on provided selector
     *
     * @param string $selector
     *
     * @return bool
     */
    public function handleLogoutAndInvalidateToken(string $selector): bool;

    /**
     * Creates an encrypted payload
     *
     * @param int $adminId
     *
     * @return string|null
     */
    public function createEncryptedSessionPayload(int $adminId): ?string;

    /**
     * Validates the provided encrypted payload
     *
     * @param string $encryptedPayload
     *
     * @return int|null
     */
    public function validateEncryptedSessionPayload(string $encryptedPayload): ?int;
}
