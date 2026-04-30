<?php

namespace App\Command;

use App\Entity\Product;
use App\Entity\Category;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:import-products',
    description: 'Import produits XML sécurisé'
)]
class ImportProductsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProductRepository $productRepo,
        private CategoryRepository $categoryRepo,
        private SluggerInterface $slugger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '512M');

        $file = 'var/import/products.xml';

        if (!file_exists($file)) {
            $output->writeln('<error>XML introuvable</error>');
            return Command::FAILURE;
        }

        $xml = simplexml_load_file($file);

        $i = 0;
        $categoriesCache = [];

        foreach ($xml->product as $item) {

            $publicId = trim((string) $item->public_id);

            if ($publicId === '') {
                // ⚠️ si pas d'ID → on ignore (important)
                continue;
            }

            // =========================
            // FIND EXISTING PRODUCT
            // =========================
            $product = $this->productRepo->findOneBy([
                'supplierReference' => $publicId
            ]);

            // =========================
            // CREATE IF NOT EXISTS
            // =========================
            if (!$product) {
                $product = new Product();
                $product->setSupplierReference($publicId);

                // 👉 SLUG FIXE basé sur ID (ULTRA SAFE)
                $slug = $this->slugger->slug($publicId)->lower()->toString();
                $product->setSlug($slug);

                $this->em->persist($product);
            }

            // =========================
            // CATEGORY
            // =========================
            $categoryName = trim((string) ($item->categories->category ?? 'Divers'));

            $categorySlug = $this->slugger->slug($categoryName)->lower()->toString();

            if (!isset($categoriesCache[$categorySlug])) {

                $category = $this->categoryRepo->findOneBy([
                    'slug' => $categorySlug
                ]);

                if (!$category) {
                    $category = new Category();
                    $category->setName($categoryName);
                    $category->setSlug($categorySlug);
                    $category->setExternalReference($categoryName);

                    $this->em->persist($category);
                }

                $categoriesCache[$categorySlug] = $category;
            }

            $category = $categoriesCache[$categorySlug];

            // =========================
            // UPDATE DATA
            // =========================
            $name = trim((string) ($item->title ?? 'Produit sans nom'));
            $description = (string) ($item->description ?? null);
            $price = (string) ($item->price ?? null);
            $stock = isset($item->stock->location) ? (int) $item->stock->location : 0;
            $isActive = ((int) ($item->available ?? 0)) === 1;

            $product->setName($name);
            $product->setDescription($description);
            $product->setPrice($price);
            $product->setStock($stock);
            $product->setCategory($category);
            $product->setIsActive($isActive);

            $i++;

            if (($i % 50) === 0) {
                $this->em->flush();
                $this->em->clear();
                $categoriesCache = [];
            }
        }

        $this->em->flush();

        $output->writeln('<info>Import terminé : ' . $i . ' produits</info>');

        return Command::SUCCESS;
    }
}