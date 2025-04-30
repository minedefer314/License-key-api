<?php

namespace App\DTO;
use App\Validator\LicenseValidity\IsValidLicense;
use App\Validator\TimestampExpiration\IsExpiredTimestamp;
use Symfony\Component\Validator\Constraints as Assert;

class RequestDataDTO
{
    #[Assert\NotBlank(message: 'An expiration timestamp is required.')]
    #[Assert\Type(type: 'integer', message: 'The expiration timestamp must be an integer.')]
    #[IsExpiredTimestamp(message: 'The payload is expired.')]
    public int $expiresAt;

    #[Assert\NotBlank(message: 'A license key is required.')]
    #[Assert\Type(type: 'string', message: 'The license key must be a string.')]
    #[IsValidLicense(message: 'The license key is invalid.')]
    public string $licenseKey;
}