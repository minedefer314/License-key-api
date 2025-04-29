<?php

namespace App\Service;

class PayloadDecryptionService
{
    private \OpenSSLAsymmetricKey $privateKey;
    private ?string $aesKey = null;

    public function __construct($privateKey) {
        $this->privateKey = openssl_pkey_get_private($privateKey);
    }

    public function decryptPayload(string $payload, string $cryptedKey, string $iv): array
    {
        openssl_private_decrypt(base64_decode($cryptedKey), $this->aesKey, $this->privateKey, OPENSSL_PKCS1_OAEP_PADDING);

        $decryptedData = openssl_decrypt(
            base64_decode($payload),
            'aes-256-cbc',
            $this->aesKey,
            OPENSSL_RAW_DATA,
            base64_decode($iv)
        );

        return json_decode($decryptedData, true);
    }
}