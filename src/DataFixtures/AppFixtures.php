<?php

namespace App\DataFixtures;

use App\Entity\Brand;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private SluggerInterface $slugger
    ) {}

    public function load(ObjectManager $manager): void
    {
        // =========================
        // BRANDS
        // =========================
        $brands = ['Vice & Co', 'Dark Pleasure', 'Noir Desire'];

        $brandEntities = [];

        foreach ($brands as $name) {
            $brand = new Brand();
            $brand->setName($name);
            $brand->setSlug($this->slug($name));

            $manager->persist($brand);
            $brandEntities[] = $brand;
        }

        // =========================
        // CATEGORIES
        // =========================
        $categories = [
            'Sextoys',
            'Lingerie',
            'Lubrifiants',
            'Fetichisme',
            'Accessoires'
        ];

        $categoryEntities = [];

        foreach ($categories as $name) {
            $category = new Category();
            $category->setName($name);
            $category->setSlug($this->slug($name));
            $category->setIsActive(true);

            $manager->persist($category);
            $categoryEntities[] = $category;
        }

        $manager->flush();
    }

    private function slug(string $text): string
    {
        return strtolower(
            $this->slugger->slug($text)->toString()
        );
    }
}