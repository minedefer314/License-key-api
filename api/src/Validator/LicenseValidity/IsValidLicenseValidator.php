<?php

namespace App\Validator\LicenseValidity;

use App\Repository\LicenseRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsValidLicenseValidator extends ConstraintValidator
{
    public function __construct(private readonly LicenseRepository $licenseRepository) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof IsValidLicense) {
            throw new \LogicException('Invalid constraint type.');
        }

        if (!is_string($value) || $value === '') {
            return;
        }

        if (!$this->licenseRepository->findByLicenseKey($value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}