<?php

namespace DemoShop\Business\Service;

use DemoShop\Business\Encryption\EncryptorInterface;
use DemoShop\Business\Model\Admin;
use DemoShop\Business\Repository\AdminAuthTokenRepositoryInterface;
use DemoShop\Business\Repository\AdminRepositoryInterface;
use Carbon\Carbon;
use Exception;

class AuthService implements AuthServiceInterface
{
    private AdminRepositoryInterface $adminRepository;
    private AdminAuthTokenRepositoryInterface $adminAuthTokenRepository;
    private EncryptorInterface $encryptor;

    public function __construct(
        AdminRepositoryInterface          $adminRepository,
        AdminAuthTokenRepositoryInterface $adminAuthTokenRepository,
        EncryptorInterface                $encryptor
    )
    {
        $this->adminRepository = $adminRepository;
        $this->adminAuthTokenRepository = $adminAuthTokenRepository;
        $this->encryptor = $encryptor;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(Admin $admin): int
    {
        return $this->adminRepository->authenticate($admin);
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
        $lowerFlag = 0;
        $upperFlag = 0;
        $specialFlag = 0;
        $numberFlag = 0;
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

            if ($tokenStored) {
                return ['selector' => $selector, 'validator' => $validator];
            }
        } catch (Exception $e) {
            echo $e->getMessage();

            return [];
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function validateAuthToken(string $selector, string $validatorFromCookie): ?int
    {
        $tokenInstance = $this->adminAuthTokenRepository->findTokenBySelector($selector);
        if ($tokenInstance) {
            if (hash_equals($tokenInstance->hashed_validator, hash('sha256', $validatorFromCookie))) {
                return $tokenInstance->admin_id;
            } else {
                $this->adminAuthTokenRepository->deleteTokenBySelector($selector);
            }
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function handleLogoutAndInvalidateToken(string $selector): bool
    {
        return $this->adminAuthTokenRepository->deleteTokenBySelector($selector);
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
        } catch (Exception $e) {
            echo $e->getMessage();

            return null;
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
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        return null;
    }
}