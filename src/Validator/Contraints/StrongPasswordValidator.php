<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class StrongPasswordValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof StrongPassword) {
            return;
        }

        if ($value === null || $value === '') {
            return;
        }

        if (mb_strlen($value) < 12) {
            $this->context
                ->buildViolation($constraint->tooShortMessage)
                ->addViolation();

            return;
        }

        if (!preg_match('/[A-Z]/', $value)) {
            $this->context
                ->buildViolation($constraint->missingUppercaseMessage)
                ->addViolation();

            return;
        }

        if (!preg_match('/[a-z]/', $value)) {
            $this->context
                ->buildViolation($constraint->missingLowercaseMessage)
                ->addViolation();

            return;
        }

        if (!preg_match('/[0-9]/', $value)) {
            $this->context
                ->buildViolation($constraint->missingNumberMessage)
                ->addViolation();

            return;
        }

        if (!preg_match('/[^A-Za-z0-9]/', $value)) {
            $this->context
                ->buildViolation($constraint->missingSpecialMessage)
                ->addViolation();

            return;
        }
    }
}