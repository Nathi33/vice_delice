<?php

namespace App\Form;

use App\Entity\Brand;
use App\Entity\Category;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // =========================
            // IDENTITÉ PRODUIT
            // =========================
            ->add('name', TextType::class, [
                'label' => 'Nom du produit'
            ])

            ->add('supplierReference', TextType::class, [
                'label' => 'Référence fournisseur',
                'required' => false
            ])

            // =========================
            // CONTENU
            // =========================
            ->add('description', null, [
                'label' => 'Description'
            ])

            // =========================
            // PRIX / STOCK
            // =========================
            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR'
            ])

            ->add('stock', NumberType::class, [
                'label' => 'Stock',
                'required' => false
            ])

            // =========================
            // STATUT
            // =========================
            ->add('isActive', CheckboxType::class, [
                'label' => 'Produit actif',
                'required' => false
            ])

            // =========================
            // RELATIONS
            // =========================
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie'
            ])

            ->add('brand', EntityType::class, [
                'class' => Brand::class,
                'choice_label' => 'name',
                'label' => 'Marque',
                'required' => false
            ])

            // =========================
            // IMAGES
            // =========================
            ->add('imageFile', FileType::class, [
                'label' => 'Image produit',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Format image invalide'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}