<?php

namespace DemoShop\src\Data\Repository;

use DemoShop\src\Business\Interfaces\Encryption\EncryptorInterface;
use DemoShop\src\Business\Interfaces\Repository\AdminRepositoryInterface;
use DemoShop\src\Business\Model\Admin;
use DemoShop\src\Data\Model\Admin as AdminEntity;
use DemoShop\src\Infrastructure\DI\ServiceRegistry;
use Exception;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use RuntimeException;

/*
 * Stores logic for interacting with database
 */

class AdminRepository implements AdminRepositoryInterface
{
    private EncryptorInterface $encryptor;

    /**
     * Constructs Admin Repository instance
     */
    public function __construct()
    {
        try {
            $this->encryptor = ServiceRegistry::get(EncryptorInterface::class);
        } catch (Exception $e) {
            error_log("CRITICAL: AdminRepository could not be initialized.
             Failed to get EncryptorInterface service. Original error: " . $e->getMessage());
            throw new RuntimeException(
                "AdminRepository failed to initialize due to a missing critical dependency (Encryptor).",
                0, $e
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function authenticate(Admin $admin): int
    {
        $plainUsername = $admin->getUsername();
        $plainPassword = $admin->getPassword();

        try {
            $allAdminUsers = AdminEntity::all();
            foreach ($allAdminUsers as $adminUser) {
                try {
                    $decryptedUsername = $this->encryptor->decrypt($adminUser->username);
                    if ($decryptedUsername === $plainUsername) {
                        if (!password_verify($plainPassword, $adminUser->password)) {
                            return -1;
                        }

                        return $adminUser->id;
                    }
                } catch (DecryptException $e) {
                    continue;
                }
            }

            return 0;
        } catch (QueryException $e) {
            error_log("authenticate - Database query failed while retrieving admin users: " . $e->getMessage());
            throw new RuntimeException(
                "Authentication failed due to a database error while retrieving user data.", 0, $e);
        } catch (Exception $e) {
            error_log("authenticate - An unexpected error occurred: " . $e->getMessage());
            throw new RuntimeException("An unexpected error occurred during authentication.", 0, $e);
        }
    }
}