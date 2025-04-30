<?php

namespace App\Validator\TimestampExpiration;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsExpiredTimestampValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof IsExpiredTimestamp) {
            throw new \LogicException('Invalid constraint type.');
        }

        if (!is_int($value)) {
            return;
        }

        if($value < time()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ timestamp }}', (string) $value)
                ->addViolation();
        }
    }
}