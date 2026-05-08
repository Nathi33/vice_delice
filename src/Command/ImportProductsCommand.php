<?php

namespace App\Command;

use App\Entity\Product;
use App\Entity\Category;
use App\Entity\ProductImage;
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
    description: 'Import produits XML avec images + SEO slug (optimisé mémoire)'
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
        ini_set('memory_limit', '1024M');

        $file = 'var/import/products.xml';

        if (!file_exists($file)) {
            $output->writeln('<error>XML introuvable</error>');
            return Command::FAILURE;
        }

        $xml = simplexml_load_file($file, 'SimpleXMLElement', LIBXML_NOCDATA);

        if (!$xml) {
            $output->writeln('<error>XML invalide</error>');
            return Command::FAILURE;
        }

        $i = 0;
        $batchSize = 50;

        $categoriesCache = [];
        $productsCache = [];

        foreach ($xml->product as $item) {

            $publicId = trim((string) $item->public_id);
            if ($publicId === '') {
                continue;
            }

            // =========================
            // PRODUCT (cache pour éviter re-query après clear)
            // =========================
            if (!isset($productsCache[$publicId])) {
                $product = $this->productRepo->findOneBy([
                    'supplierReference' => $publicId
                ]);

                if (!$product) {
                    $product = new Product();
                    $product->setSupplierReference($publicId);
                    $product->setSlug(
                        $this->slugger->slug($publicId)->lower()->toString()
                    );

                    $this->em->persist($product);
                }

                $productsCache[$publicId] = $product;
            }

            $product = $productsCache[$publicId];

            // =========================
            // CATEGORY (cache optimisé)
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
            // DATA PRODUIT
            // =========================
            $name = trim((string) ($item->title ?? 'Produit sans nom'));

            $description = (string) ($item->description ?? null);
            $price = (string) ($item->price ?? null);
            $stock = isset($item->stock->location) ? (int) $item->stock->location : 0;
            $isActive = ((int) ($item->available ?? 0)) === 1;

            // =========================
            // SEO SLUG UNIQUE (léger)
            // =========================
            $baseSeoSlug = $this->slugger->slug($name)->lower()->toString();

            $seoSlug = $baseSeoSlug;
            $counter = 1;

            while (true) {
                $existing = $this->productRepo->findOneBy([
                    'seoSlug' => $seoSlug
                ]);

                if (!$existing || $existing === $product) {
                    break;
                }

                $seoSlug = $baseSeoSlug . '-' . $counter;
                $counter++;
            }

            // =========================
            // UPDATE PRODUCT
            // =========================
            $product->setName($name);
            $product->setSeoSlug($seoSlug);
            $product->setDescription($description);
            $product->setPrice($price);
            $product->setStock($stock);
            $product->setCategory($category);
            $product->setIsActive($isActive);

            // =========================
            // IMAGES
            // =========================
            if (isset($item->images->image)) {

                foreach ($item->images->image as $imgXml) {

                    $image = new ProductImage();
                    $image->setUrl((string) $imgXml->src);
                    $image->setProduct($product);

                    $isMain = ((int) ($imgXml['preferred'] ?? 0)) === 1;
                    $image->setIsMain($isMain);

                    $this->em->persist($image);
                }
            }

            $i++;

            // =========================
            // BATCH FLUSH + MEMORY CLEAN
            // =========================
            if ($i % $batchSize === 0) {

                $this->em->flush();

                // ⚠ important : reset propre sans casser les objets déjà liés
                $this->em->clear();

                // on vide les caches pour éviter objets détachés incohérents
                $categoriesCache = [];
                $productsCache = [];
            }
        }

        $this->em->flush();
        $this->em->clear();

        $output->writeln("<info>Import terminé : $i produits</info>");

        return Command::SUCCESS;
    }
}