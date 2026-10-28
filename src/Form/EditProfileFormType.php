<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class EditProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['is_admin']) {
            $builder
                ->add('firstName', TextType::class, [
                    'label' => 'Prénom',
                    'attr' => [
                        'placeholder' => 'Votre prénom',
                        'class' => 'form-control luxury-input',
                        'autocomplete' => 'given-name',
                    ],
                    'constraints' => [
                        new NotBlank(message: 'Veuillez renseigner votre prénom.'),
                    ],
                ])
                ->add('lastName', TextType::class, [
                    'label' => 'Nom',
                    'attr' => [
                        'placeholder' => 'Votre nom',
                        'class' => 'form-control luxury-input',
                        'autocomplete' => 'family-name',
                    ],
                    'constraints' => [
                        new NotBlank(message: 'Veuillez renseigner votre nom.'),
                    ],
                ]);
        }

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'attr' => [
                    'placeholder' => 'Votre adresse e-mail',
                    'class' => 'form-control luxury-input',
                    'autocomplete' => 'email',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez renseigner votre adresse e-mail.'
                    ),
                    new Email(
                        message: 'Veuillez renseigner une adresse e-mail valide.'
                    ),
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Votre numéro de téléphone',
                    'class' => 'form-control luxury-input',
                    'autocomplete' => 'tel',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'is_admin' => false,
        ]);

        $resolver->setAllowedTypes('is_admin', 'bool');
    }
}
