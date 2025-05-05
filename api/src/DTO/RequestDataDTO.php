<?php

namespace App\DTO;
use App\Validator\LicenseValidity\IsValidLicense;
use App\Validator\TimestampExpiration\IsExpiredTimestamp;
use Symfony\Component\Validator\Constraints as Assert;

class RequestDataDTO
{
    #[Assert\NotBlank(message: 'The expiration timestamp is missing.')]
    #[Assert\Type(type: 'string', message: 'The expiration timestamp is not a string.')]
    #[IsExpiredTimestamp]
    public string $expiresAt;

    #[Assert\NotBlank(message: 'The license key is missing.')]
    #[Assert\Type(type: 'string', message: 'The license key is not a string.')]
    #[IsValidLicense]
    public string $licenseKey;

    #[Assert\NotBlank(message: 'The ip address is missing.')]
    #[Assert\Type(type: 'string', message: 'The provided ip address is not a string.')]
    #[Assert\Ip(message: 'The provided ip address is invalid.')]
    public string $ipAddress;
}