<?php

namespace DemoShop\Data\Repository;

use DemoShop\Data\Model\AdminAuthToken;
use Carbon\Carbon;
use Exception;

class AdminAuthTokenRepository
{
    public function storeToken(
        int $adminId,
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

    public function updateTokenValidator(string $selector, string $newHashedValidator, string $newExpiresAt): bool
    {
        try {
            $token = AdminAuthToken::where('selector', $selector)
                ->where('expires_at', '>', Carbon::now())
                ->first();

            if ($token) {
                return $token->update([
                    'hashed_validator' => $newHashedValidator,
                    'expires_at' => $newExpiresAt,
                ]);
            }
            return false;
        } catch (Exception $e) {
            echo $e->getMessage();

            return false;
        }
    }

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

    public function deleteTokensByAdminId(string $adminId): bool
    {
        try {
            $deletedRows = AdminAuthToken::where('admin_id', $adminId)->delete();
            return $deletedRows > 0;
        } catch (Exception $e) {
            echo $e->getMessage();

            return false;
        }
    }

    public function deleteExpiredTokens(): int
    {
        try {
            return AdminAuthToken::where('expires_at', '<', Carbon::now())->delete();
        } catch (Exception $e) {
            echo $e->getMessage();

            return -1;
        }
    }
}