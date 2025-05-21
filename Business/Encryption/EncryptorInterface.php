<?php

namespace DemoShop\Business\Encryption;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;

interface EncryptorInterface
{
    /**
     * Encrypts string
     *
     * @param string $value
     * @param bool $serialize
     *
     * @throws EncryptException
     *
     * @return string
 */
    public function encrypt(string $value, bool $serialize): string;

    /**
     * Decrypts string
     *
     * @param string $value
     * @param bool $deserialize
     *
     * @throws DecryptException
     *
     * @return string
     */
    public function decrypt(string $value, bool $deserialize): string;
}
