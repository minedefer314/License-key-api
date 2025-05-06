<?php

namespace App\Service\Encryption;

class PayloadDecryptionService extends EncryptionService
{
    private ?string $aesKey = null;

    private ?string $aesIV = null;

    public function __construct($privateKey)
    {
        parent::__construct($privateKey);
    }

    private function setIV(string $ivBase64): true|string
    {
        $iv = base64_decode($ivBase64, true);
        if($iv === false)
            return "IV is not base64 encoded.";

        if (mb_check_encoding($iv, 'ASCII') && !preg_match('/[^\x00-\x7F]/', $iv)) {
            return "IV is not binary.";
        }

        else if (strlen($iv) !== 16)
            return "Invalid IV length.";

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

        $dataArray = json_decode($decryptedData, true);
        if($dataArray === null)
            return "Data is not valid json.";

        return $dataArray;
    }
}