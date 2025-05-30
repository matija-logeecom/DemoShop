<?php

namespace DemoShop\src\Business\Interfaces\Repository;

use DemoShop\src\Data\Model\AdminAuthToken;

interface AdminAuthTokenRepositoryInterface
{
    /**
     * Stores token in database
     *
     * @param int $adminId
     * @param string $selector
     * @param string $hashedValidator
     * @param string $expiresAt
     *
     * @return AdminAuthToken|null
     */
    public function storeToken(int $adminId, string $selector, string $hashedValidator, string $expiresAt): ?AdminAuthToken;

    /**
     * Finds the token in database based on the provided selector
     *
     * @param string $selector
     *
     * @return AdminAuthToken|null
     */
    public function findTokenBySelector(string $selector): ?AdminAuthToken;

    /**
     * Deletes token from database based on provided selector
     *
     * @param string $selector
     *
     * @return bool
     */
    public function deleteTokenBySelector(string $selector): bool;
}