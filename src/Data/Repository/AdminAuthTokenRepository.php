<?php

namespace DemoShop\Data\Repository;

use Carbon\Carbon;
use DemoShop\Business\Interfaces\Repository\AdminAuthTokenRepositoryInterface;
use DemoShop\Data\Model\AdminAuthToken;
use Exception;
use Illuminate\Database\QueryException;
use RuntimeException;

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
        } catch (QueryException $e) {
            error_log("storeToken - Database query failed. Error: " . $e->getMessage());
            throw new RuntimeException("Database query failed for storing token.", 0, $e);
        } catch (Exception $e) {
            error_log("storeToken - An unexpected error occurred. Error: " . $e->getMessage());
            throw new RuntimeException("An unexpected error occurred while storing the auth token", 0, $e);
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
        } catch (QueryException $e) {
            error_log(
                "findTokenBySelector - Database query failed for selector '{$selector}': " . $e->getMessage());
            throw new RuntimeException(
                "Database error while trying to find auth token by selector '{$selector}'.", 0, $e);
        } catch (Exception $e) {
            error_log("findTokenBySelector - An unexpected error occurred for selector '{$selector}': "
                . $e->getMessage());
            throw new RuntimeException(
                "An unexpected error occurred while finding auth token by selector '{$selector}'.", 0, $e);
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
        } catch (QueryException $e) {
            error_log(
                "deleteTokenBySelector - Database query failed for selector '{$selector}': " . $e->getMessage());
            throw new RuntimeException(
                "Database error while trying to delete auth token by selector '{$selector}'.", 0, $e);
        } catch (Exception $e) {
            error_log(
                "deleteTokenBySelector - An unexpected error occurred for selector '{$selector}': "
                . $e->getMessage());
            throw new RuntimeException(
                "An unexpected error occurred while deleting auth token by selector '{$selector}'.",
                0, $e);
        }
    }
}