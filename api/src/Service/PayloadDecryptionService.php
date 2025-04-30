<?php

namespace App\Service;

class PayloadDecryptionService
{
    private \OpenSSLAsymmetricKey $privateKey;
    private ?string $aesKey = null;

    private ?string $aesIV = null;

    public function __construct($private_key) {
        $loadedPrivateKey = openssl_pkey_get_private($private_key);

        if ($loadedPrivateKey === false) {
            throw new \RuntimeException('Failed to load private key : ' . $private_key);
        }

        $this->privateKey = $loadedPrivateKey;
    }

    private function setIV(string $ivBase64): true|string
    {
        $iv = base64_decode($ivBase64);
        if($iv === false)
            return "IV must be base64 encoded.";
        else if (strlen($iv) !== 16)
            return "Invalid IV.";

        $this->aesIV = $iv;
        return true;
    }

    public function decryptPayload(string $payload, string $cryptedKeyBase64, string $ivBase64): string|array
    {
        // Tested
        $isSuccess = openssl_private_decrypt(base64_decode($cryptedKeyBase64), $this->aesKey, $this->privateKey);

        if(!$isSuccess)
            return "Invalid RSA encryption.";

        // Not tested
        $isSuccess = $this->setIV($ivBase64);
        if(is_string($isSuccess))
            return $isSuccess;

        try {
            $decryptedData = openssl_decrypt(
                data: base64_decode($payload),
                cipher_algo: 'aes-256-cbc',
                passphrase: $this->aesKey,
                options: OPENSSL_RAW_DATA,
                iv: $this->aesIV
            );
            if($decryptedData === false)
                return "Invalid AES encryption.";
        }
        catch(\Exception $e) {
            return "Failed AES decryption.";
        }

        return json_decode($decryptedData, true);
    }
}