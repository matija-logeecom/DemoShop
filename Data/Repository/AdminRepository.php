<?php

namespace DemoShop\Data\Repository;

use DemoShop\Business\Model\Admin;
use DemoShop\Business\Encryption\EncryptorInterface;
use DemoShop\Business\Repository\AdminRepositoryInterface;
use DemoShop\Data\Model\Admin as AdminEntity;
use Illuminate\Contracts\Encryption\DecryptException;

/*
 * Stores logic for interacting with database
 */

class AdminRepository implements AdminRepositoryInterface
{
    private EncryptorInterface $encryptor;

    /**
     * Constructs Admin Repository instance
     *
     * @param EncryptorInterface $encryptor
     */
    public function __construct(EncryptorInterface $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(Admin $admin): int
    {
        $plainUsername = $admin->getUsername();
        $plainPassword = $admin->getPassword();

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

        return false;
    }
}