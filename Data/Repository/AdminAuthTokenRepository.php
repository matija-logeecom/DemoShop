<?php

namespace DemoShop\Data\Repository;

use DemoShop\Data\Model\AdminAuthToken;
use DemoShop\Business\Repository\AdminAuthTokenRepositoryInterface;
use Carbon\Carbon;
use Exception;

/*
 * Stores logic for admin authentication
 */
class AdminAuthTokenRepository implements AdminAuthTokenRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function storeToken(
        int    $adminId,
        string $selector,
        string $hashedValidator,
        string $expiresAt,
    ): ?AdminAuthToken
    {
        try {
            return AdminAuthToken::create([
                'admin_id' => $adminId,
                'selector' => $selector,
                'hashed_validator' => $hashedValidator,
                'expires_at' => $expiresAt,
            ]);
        } catch (Exception $e) {
            echo $e->getMessage();

            return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function findTokenBySelector(string $selector): ?AdminAuthToken
    {
        try {
            return AdminAuthToken::where('selector', $selector)
                ->where('expires_at', '>', Carbon::now())
                ->first();
        } catch (Exception $e) {
            echo $e->getMessage();

            return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteTokenBySelector(string $selector): bool
    {
        try {
            $deletedRows = AdminAuthToken::where('selector', $selector)->delete();
            return $deletedRows > 0;
        } catch (Exception $e) {
            echo $e->getMessage();

            return false;
        }
    }
}