<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RequestDTO
{
    #[Assert\NotBlank(message: 'A data payload is required.')]
    #[Assert\Type(type: 'string', message: 'The data payload must be a string.')]
    public string $payload;

    #[Assert\NotBlank(message: 'A decryption key is required.')]
    #[Assert\Type(type: 'string', message: 'The decryption key must be a string.')]
    public string $key;

    #[Assert\NotBlank(message: 'An iv is required.')]
    #[Assert\Type(type: 'string', message: 'The iv must be a string.')]
    public string $iv;
}