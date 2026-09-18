<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use App\Validator\Constraints\StrongPassword;

class RegistrationFormType extends AbstractType
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            // =========================
            // PRENOM
            // =========================
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'placeholder' => 'Votre prénom',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez renseigner votre prénom.'
                    ),
                ],
            ])

            // =========================
            // NOM
            // =========================
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Votre nom',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez renseigner votre nom.'
                    ),
                ],
            ])

            // =========================
            // EMAIL
            // =========================
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => [
                    'placeholder' => 'vous@email.com',
                ],
            ])

            // =========================
            // TELEPHONE
            // =========================
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'placeholder' => '06 00 00 00 00',
                ],
            ])

            // =========================
            // MOT DE PASSE
            // =========================
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,

                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => [
                        'placeholder' => 'Votre mot de passe',
                        'class' => 'form-control luxury-input password-input',
                    ],
                ],

                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => [
                        'placeholder' => 'Confirmez le mot de passe',
                        'class' => 'form-control luxury-input password-input',
                    ],
                ],

                'invalid_message' => 'Les mots de passe ne correspondent pas.',

                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez entrer un mot de passe.'
                    ),

                    new StrongPassword(),
                ],
            ])

            // =========================
            // CONDITIONS
            // =========================
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label_html' => true,
                'label' => 'J’accepte les <a href="' . $this->urlGenerator->generate('app_cgv') . '" target="_blank">conditions générales d’utilisation</a>',
                'constraints' => [
                    new IsTrue(
                        message: 'Vous devez accepter les conditions générales.'
                    ),
                ],
            ])

            // =========================
            // MAJORITE
            // =========================
            ->add('adultConfirm', CheckboxType::class, [
                'mapped' => false,
                'label' => 'Je confirme avoir 18 ans ou plus',
                'constraints' => [
                    new IsTrue(
                        message: 'Vous devez être majeur pour créer un compte.'
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}