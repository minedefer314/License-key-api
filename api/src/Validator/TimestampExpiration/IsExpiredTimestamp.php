<?php

namespace App\Validator\TimestampExpiration;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class IsExpiredTimestamp extends Constraint
{
    public string $message = 'The payload is expired.';
}