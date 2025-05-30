<?php

namespace DemoShop\src\Data\Encryption;

use DemoShop\src\Business\Interfaces\Encryption\EncryptorInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Encryption\Encrypter;
use RuntimeException;

/*
 * Stores methods for encrypting and decrypting strings based on key
 */

class Encryptor implements EncryptorInterface
{
    private Encrypter $encrypter;

    /**
     * Constructs Encryptor instance
     *
     * @param string $key
     * @param string $cipher
     */
    public function __construct(string $key, string $cipher = 'AES-256-CBC')
    {
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        if (Encrypter::supported($key, $cipher)) {
            $this->encrypter = new Encrypter($key, $cipher);
        } else {
            throw new RuntimeException('The only supported ciphers are AES-128-CBC " . 
            "and AES-256-CBC with the correct key lengths.');
        }
    }

    /**
     * @inheritDoc
     */
    public function encrypt(string $value, bool $serialize = true): string
    {
        try {
            return $this->encrypter->encrypt($value, $serialize);
        } catch (EncryptException $e) {
            throw new EncryptException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function decrypt(string $value, bool $deserialize = true): string
    {
        try {
            return $this->encrypter->decrypt($value, $deserialize);
        } catch (DecryptException $e) {
            throw new DecryptException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
