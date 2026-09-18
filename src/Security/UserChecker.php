<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        // rien ici pour l'instant
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        // 🔐 BLOQUAGE EMAIL NON VÉRIFIÉ
        if (method_exists($user, 'isVerified') && !$user->isVerified()) {
            throw new CustomUserMessageAuthenticationException(
                'Votre compte n’est pas activé. Vérifiez vos emails.'
            );
        }
    }
}