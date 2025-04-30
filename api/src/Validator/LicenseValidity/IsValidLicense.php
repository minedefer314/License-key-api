<?php

namespace App\Validator\LicenseValidity;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class IsValidLicense extends Constraint
{
    public string $message = 'The license {{ key }} is invalid.';
}