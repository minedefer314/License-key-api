<?php

namespace App\Service\Encryption;

class EncryptionService
{
    protected \OpenSSLAsymmetricKey $privateKey;

    public function __construct($privateKey) {
        $loadedPrivateKey = openssl_pkey_get_private($privateKey);

        if ($loadedPrivateKey === false) {
            throw new \RuntimeException('Failed to load private key : ' . $privateKey);
        }

        $this->privateKey = $loadedPrivateKey;
    }

    public function rsaEncrypt(string $rawData): false|string
    {
        $keyDetails = openssl_pkey_get_details($this->privateKey);
        $publicKey = $keyDetails['key'];

        $success = openssl_public_encrypt($rawData, $rawEncryptedData, $publicKey);

        if(!$success)
            return false;

        return $rawEncryptedData;
    }

    public function rsaDecrypt(string $rawEncryptedData): false|string
    {
        $success = openssl_private_decrypt($rawEncryptedData, $rawDecryptedData, $this->privateKey);

        if(!$success)
            return false;

        return $rawDecryptedData;
    }

    public function aesEncrypt(
        string $rawData,
        string $binaryKey,
        string $binaryIv
    ): false|string
    {
        $rawEncryptedData = openssl_encrypt(
            data: $rawData,
            cipher_algo: 'aes-256-cbc',
            passphrase: $binaryKey,
            options: OPENSSL_RAW_DATA,
            iv: $binaryIv
        );

        return $rawEncryptedData;
    }

    public function aesDecrypt(
        string $rawEncrytedData,
        string $binaryKey,
        string $binaryIv
    ): false|string
    {
        $rawDecryptedData = openssl_decrypt(
            data: $rawEncrytedData,
            cipher_algo: 'aes-256-cbc',
            passphrase: $binaryKey,
            options: OPENSSL_RAW_DATA,
            iv: $binaryIv
        );

        return $rawDecryptedData;
    }
}