<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateSessionRequest
{
    #[Assert\NotBlank]
    public string $payload;

    #[Assert\NotBlank]
    public string $key;

    #[Assert\NotBlank]
    public string $iv;
}