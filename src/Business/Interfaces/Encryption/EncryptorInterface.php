<?php

namespace DemoShop\src\Business\Interfaces\Encryption;

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
     * @return string
     *
     * @throws EncryptException
     */
    public function encrypt(string $value, bool $serialize): string;

    /**
     * Decrypts string
     *
     * @param string $value
     * @param bool $deserialize
     *
     * @return string
     *
     * @throws DecryptException
     */
    public function decrypt(string $value, bool $deserialize): string;
}
