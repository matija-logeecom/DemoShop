<?php

namespace DemoShop\Business\Service;

use Carbon\Carbon;
use DemoShop\Business\Interfaces\Encryption\EncryptorInterface;
use DemoShop\Business\Interfaces\Repository\AdminAuthTokenRepositoryInterface;
use DemoShop\Business\Interfaces\Repository\AdminRepositoryInterface;
use DemoShop\Business\Interfaces\Service\AuthServiceInterface;
use DemoShop\Business\Model\Admin;
use DemoShop\Infrastructure\DI\ServiceRegistry;
use Exception;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;
use RuntimeException;

class AuthService implements AuthServiceInterface
{
    private AdminRepositoryInterface $adminRepository;
    private AdminAuthTokenRepositoryInterface $adminAuthTokenRepository;
    private EncryptorInterface $encryptor;

    public function __construct()
    {
        try {
            $this->adminRepository = ServiceRegistry::get(AdminRepositoryInterface::class);
            $this->adminAuthTokenRepository = ServiceRegistry::get(AdminAuthTokenRepositoryInterface::class);
            $this->encryptor = ServiceRegistry::get(EncryptorInterface::class);
        } catch (Exception $e) {
            error_log("CRITICAL: AuthService could not be initialized.
             Failed to get a required service. Original error: " . $e->getMessage());
            throw new RuntimeException(
                "AuthService failed to initialize due to a
                 missing critical dependency.",
                0, $e
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function authenticate(Admin $admin): int
    {
        try {
            return $this->adminRepository->authenticate($admin);
        } catch (RuntimeException $e) {
            error_log(
                "authenticate - Authentication failed due to repository error.
                 User: {$admin->getUsername()}. Error: " . $e->getMessage());
            throw new RuntimeException("Authentication process encountered an issue. Please try again later.",
                0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function isValidUsername(string $username, array &$errors): bool
    {
        if (empty($username)) {
            $errors['username'] = 'Username cannot be empty';
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function isValidPassword(string $password, array &$errors): bool
    {
        if (empty($password)) {
            $errors['password'] = 'Password cannot be empty';

            return false;
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long';

            return false;
        }
        $lowerFlag = $upperFlag = $specialFlag = $numberFlag = 0;
        foreach (str_split($password) as $char) {
            if (ctype_upper($char)) {
                $upperFlag = 1;
            }
            if (ctype_lower($char)) {
                $lowerFlag = 1;
            }
            if (ctype_digit($char)) {
                $numberFlag = 1;
            }
            if (in_array($char, ['!', '_', '#', '$', '-'])) {
                $specialFlag = 1;
            }
        }
        if (!($lowerFlag && $upperFlag && $numberFlag && $specialFlag)) {
            $errors['password'] = 'Password must contain at least one upper,
             one lower, one number and one special character (supported: ! _ # $ -)';

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function handleLoginAndCreateAuthToken(int $adminId): array
    {
        try {
            $selector = bin2hex(random_bytes(16));
            $validator = bin2hex(random_bytes(32));
            $hashedValidator = hash('sha256', $validator);
            $expiresAt = Carbon::now()->addDays(30)->toDateTimeString();

            $tokenStored = $this->adminAuthTokenRepository->storeToken(
                $adminId,
                $selector,
                $hashedValidator,
                $expiresAt
            );

            if ($tokenStored !== null) {
                return ['selector' => $selector, 'validator' => $validator];
            }

            return [];
        } catch (RuntimeException $e) {
            error_log(
                "handleLoginAndCreateAuthToken - Failed to store token for admin ID {$adminId}
                 due to repository error: " . $e->getMessage());
            throw new RuntimeException(
                "Failed to create authentication token. Please try again later.", 0, $e);
        } catch (Exception $e) {
            error_log(
                "handleLoginAndCreateAuthToken - 
                Error generating token components for admin ID {$adminId}: " . $e->getMessage());
            throw new RuntimeException(
                "Failed to create authentication token due to an internal error. Please try again later.",
                0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function validateAuthToken(string $selector, string $validatorFromCookie): ?int
    {
        try {
            $tokenInstance = $this->adminAuthTokenRepository->findTokenBySelector($selector);
            if ($tokenInstance) {
                if (hash_equals($tokenInstance->hashed_validator, hash('sha256', $validatorFromCookie))) {
                    return $tokenInstance->admin_id;
                } else {
                    $this->adminAuthTokenRepository->deleteTokenBySelector($selector);
                }
            }
            return null;
        } catch (RuntimeException $e) {
            error_log("validateAuthToken - 
            Error during token validation for selector '{$selector}': " . $e->getMessage());
            throw new RuntimeException(
                "Error validating authentication token. Please try logging in again.", 0, $e);        }

    }

    /**
     * @inheritDoc
     */
    public function handleLogoutAndInvalidateToken(string $selector): bool
    {
        try {
            return $this->adminAuthTokenRepository->deleteTokenBySelector($selector);
        } catch (RuntimeException $e) {
            error_log("handleLogoutAndInvalidateToken - 
            Failed to delete token for selector '{$selector}': " . $e->getMessage());
            throw new RuntimeException("Error during logout process. Please try again.", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function createEncryptedSessionPayload(int $adminId): ?string
    {
        try {
            $sessionExpiresAtTimestamp = Carbon::now()->addHours(2)->timestamp;
            $payload = [
                'sub' => $adminId,
                'exp' => $sessionExpiresAtTimestamp,
            ];
            $serializedPayload = json_encode($payload);

            return $this->encryptor->encrypt($serializedPayload, true);
        } catch (EncryptException $e) {
            error_log("createEncryptedSessionPayload -
             Encryption failed for admin ID {$adminId}: " . $e->getMessage());
            throw new RuntimeException("Failed to create secure session. Please try again.", 0, $e);
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Exception $e) {
            error_log("createEncryptedSessionPayload -
             Unexpected error for admin ID {$adminId}: " . $e->getMessage());
            throw new RuntimeException(
                "An unexpected error occurred while creating session payload. Please try again.", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function validateEncryptedSessionPayload(string $encryptedPayload): ?int
    {
        try {
            $decryptedSerializedPayload = $this->encryptor->decrypt($encryptedPayload, true);
            $payload = json_decode($decryptedSerializedPayload, true);

            if (json_last_error() === JSON_ERROR_NONE &&
                isset($payload['sub']) &&
                isset($payload['exp']) &&
                is_numeric($payload['exp']) &&
                $payload['exp'] > Carbon::now()->timestamp) {
                return (int)$payload['sub'];
            }
        } catch (DecryptException $e) {
            error_log("validateEncryptedSessionPayload - Decryption failed: " . $e->getMessage());

            return null;
        } catch (Exception $e) {
            error_log("validateEncryptedSessionPayload - Unexpected error: " . $e->getMessage());

            return null;
        }

        return null;
    }
}