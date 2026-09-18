<?php

namespace App\Form;

use App\Validator\Constraints\StrongPassword;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'attr' => [
                    'placeholder' => 'Votre mot de passe actuel',
                    'class' => 'form-control luxury-input password-input',
                    'autocomplete' => 'current-password',
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer votre mot de passe actuel.'),
                ],
            ])

            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,

                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'attr' => [
                        'placeholder' => 'Votre nouveau mot de passe',
                        'class' => 'form-control luxury-input password-input',
                        'minlength' => 12,
                        'autocomplete' => 'new-password',
                    ],
                    'constraints' => [
                        new NotBlank(message: 'Veuillez entrer un nouveau mot de passe.'),
                        new StrongPassword(),
                    ],
                ],

                'second_options' => [
                    'label' => 'Confirmer le nouveau mot de passe',
                    'attr' => [
                        'placeholder' => 'Confirmez votre nouveau mot de passe',
                        'class' => 'form-control luxury-input password-input',
                        'minlength' => 12,
                        'autocomplete' => 'new-password',
                    ],
                ],

                'invalid_message' => 'Les mots de passe ne correspondent pas.',
            ]);
    }
}