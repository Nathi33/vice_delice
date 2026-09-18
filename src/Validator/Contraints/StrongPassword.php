<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class StrongPassword extends Constraint
{
    public string $message = 'Votre mot de passe doit contenir au moins 12 caractères, dont une majuscule, une minuscule, un chiffre et un caractère spécial.';

    public string $tooShortMessage = 'Votre mot de passe doit contenir au moins 12 caractères.';

    public string $missingUppercaseMessage = 'Votre mot de passe doit contenir au moins une lettre majuscule.';

    public string $missingLowercaseMessage = 'Votre mot de passe doit contenir au moins une lettre minuscule.';

    public string $missingNumberMessage = 'Votre mot de passe doit contenir au moins un chiffre.';

    public string $missingSpecialMessage = 'Votre mot de passe doit contenir au moins un caractère spécial.';

    public function validatedBy(): string
    {
        return StrongPasswordValidator::class;
    }
}