<?php

namespace App\Command;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:import-products',
    description: 'Import produits XML avec catégories, images et SEO'
)]
class ImportProductsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProductRepository $productRepo,
        private CategoryRepository $categoryRepo,
        private ProductImageRepository $productImageRepo,
        private SluggerInterface $slugger
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        ini_set('memory_limit', '1024M');

        $file = 'var/import/products.xml';

        if (!is_file($file)) {
            $output->writeln(
                '<error>XML introuvable : ' . $file . '</error>'
            );

            return Command::FAILURE;
        }

        libxml_use_internal_errors(true);

        $xml = simplexml_load_file(
            $file,
            'SimpleXMLElement',
            LIBXML_NOCDATA
        );

        if ($xml === false) {
            $output->writeln(
                '<error>Le fichier XML est invalide.</error>'
            );

            foreach (libxml_get_errors() as $error) {
                $output->writeln(trim($error->message));
            }

            libxml_clear_errors();

            return Command::FAILURE;
        }

        libxml_clear_errors();

        /*
         * ÉTAPE 1 : vérifier toutes les catégories avant l'import.
         */
        $categoryPaths = [];

        foreach ($xml->product as $item) {
            $publicId = trim((string) $item->public_id);

            if ($publicId === '') {
                continue;
            }

            $path = trim(
                (string) ($item->categories->category ?? '')
            );

            if ($path === '') {
                $path = 'Divers';
            }

            $categoryPaths[$path] = true;
        }

        $missingCategories = [];

        foreach (array_keys($categoryPaths) as $path) {
            if (
                !$this->categoryRepo
                    ->findOneByExternalReference($path)
            ) {
                $missingCategories[] = $path;
            }
        }

        if ($missingCategories !== []) {
            $output->writeln(
                '<error>Import annulé : catégories absentes de la base.</error>'
            );

            foreach ($missingCategories as $path) {
                $output->writeln(' - ' . $path);
            }

            $output->writeln(
                '<comment>Aucun produit n’a été modifié par cette commande.</comment>'
            );

            return Command::FAILURE;
        }

        $output->writeln(sprintf(
            '<info>Vérification des catégories réussie : %d chemins.</info>',
            count($categoryPaths)
        ));

        /*
         * ÉTAPE 2 : préparer l'import par lots.
         */
        $i = 0;
        $batchSize = 50;

        $categoriesCache = [];
        $productsCache = [];
        $imagesCache = [];

        /*
         * Les réservations utilisent le publicId, et non l'ID
         * en base, car un nouveau produit n'a pas encore d'ID
         * avant le flush().
         */
        $reservedSlugs = [];
        $reservedSeoSlugs = [];

        /*
         * Générer un slug technique unique.
         */
        $generateUniqueSlug = function (
            string $value,
            Product $product,
            array &$reserved,
            string $ownerKey
        ): string {
            $baseSlug = $this->slugger
                ->slug($value)
                ->lower()
                ->toString();

            if ($baseSlug === '') {
                $baseSlug = 'produit';
            }

            $slug = $baseSlug;
            $counter = 1;

            while (true) {
                $existing = $this->productRepo->findOneBy([
                    'slug' => $slug,
                ]);

                $availableInDatabase = (
                    !$existing || $existing === $product
                );

                $reservedOwner = $reserved[$slug] ?? null;

                $availableInBatch = (
                    $reservedOwner === null
                    || $reservedOwner === $ownerKey
                );

                if (
                    $availableInDatabase
                    && $availableInBatch
                ) {
                    $reserved[$slug] = $ownerKey;

                    return $slug;
                }

                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
        };

        /*
         * ÉTAPE 3 : parcourir les produits XML.
         */
        foreach ($xml->product as $item) {
            $publicId = trim((string) $item->public_id);

            if ($publicId === '') {
                continue;
            }

            /*
             * PRODUIT : recherche par référence fournisseur.
             */
            if (!isset($productsCache[$publicId])) {
                $product = $this->productRepo->findOneBy([
                    'supplierReference' => $publicId,
                ]);

                if (!$product) {
                    $product = new Product();

                    $product->setSupplierReference($publicId);

                    $product->setSlug(
                        $generateUniqueSlug(
                            $publicId,
                            $product,
                            $reservedSlugs,
                            $publicId
                        )
                    );

                    $this->em->persist($product);
                }

                $productsCache[$publicId] = $product;
            }

            $product = $productsCache[$publicId];

            /*
             * CATÉGORIE : utiliser le chemin complet.
             */
            $categoryPath = trim(
                (string) ($item->categories->category ?? '')
            );

            if ($categoryPath === '') {
                $categoryPath = 'Divers';
            }

            if (!isset($categoriesCache[$categoryPath])) {
                $category = $this->categoryRepo
                    ->findOneByExternalReference($categoryPath);

                if (!$category) {
                    $output->writeln(sprintf(
                        '<error>Catégorie introuvable : "%s".</error>',
                        $categoryPath
                    ));

                    return Command::FAILURE;
                }

                $categoriesCache[$categoryPath] = $category;
            }

            $category = $categoriesCache[$categoryPath];

            /*
             * DONNÉES PRODUIT.
             */
            $name = trim((string) ($item->title ?? ''));

            if ($name === '') {
                $name = 'Produit sans nom';
            }

            $description = (string) ($item->description ?? '');
            $price = (string) ($item->price ?? '');

            $stock = isset($item->stock->location)
                ? (int) $item->stock->location
                : 0;

            $isActive = (
                (int) ($item->available ?? 0)
            ) === 1;

            /*
             * SLUG SEO : unicité en base et dans le lot.
             */
            $baseSeoSlug = $this->slugger
                ->slug($name)
                ->lower()
                ->toString();

            if ($baseSeoSlug === '') {
                $baseSeoSlug = 'produit';
            }

            $seoSlug = $baseSeoSlug;
            $counter = 1;

            while (true) {
                $existing = $this->productRepo->findOneBy([
                    'seoSlug' => $seoSlug,
                ]);

                $reservedOwner = (
                    $reservedSeoSlugs[$seoSlug] ?? null
                );

                $availableInDatabase = (
                    !$existing || $existing === $product
                );

                $availableInBatch = (
                    $reservedOwner === null
                    || $reservedOwner === $publicId
                );

                if (
                    $availableInDatabase
                    && $availableInBatch
                ) {
                    break;
                }

                $seoSlug = $baseSeoSlug . '-' . $counter;
                $counter++;
            }

            $reservedSeoSlugs[$seoSlug] = $publicId;

            /*
             * MISE À JOUR DES DONNÉES PRODUIT.
             */
            $product->setName($name);
            $product->setSeoSlug($seoSlug);
            $product->setDescription($description);
            $product->setPrice($price);
            $product->setStock($stock);
            $product->setCategory($category);
            $product->setIsActive($isActive);

            /*
             * IMAGES : réutiliser les images existantes.
             */
            if (isset($item->images->image)) {
                foreach ($item->images->image as $imgXml) {
                    $imageUrl = trim((string) $imgXml->src);

                    if ($imageUrl === '') {
                        continue;
                    }

                    $isMain = (
                        (int) ($imgXml['preferred'] ?? 0)
                    ) === 1;

                    /*
                     * Éviter les doublons dans le lot courant.
                     */
                    if (isset($imagesCache[$publicId][$imageUrl])) {
                        $imagesCache[$publicId][$imageUrl]
                            ->setIsMain($isMain);

                        continue;
                    }

                    $image = null;

                    /*
                     * Rechercher en base si le produit possède
                     * déjà un identifiant.
                     */
                    if ($product->getId() !== null) {
                        $image = $this->productImageRepo
                            ->findOneByProductAndUrl(
                                $product,
                                $imageUrl
                            );
                    }

                    if (!$image) {
                        $image = new ProductImage();

                        $image->setUrl($imageUrl);
                        $image->setProduct($product);

                        $this->em->persist($image);
                    }

                    $image->setIsMain($isMain);

                    $imagesCache[$publicId][$imageUrl] = $image;
                }
            }

            $i++;

            /*
             * ENREGISTREMENT PAR LOTS.
             */
            if ($i % $batchSize === 0) {
                $this->em->flush();
                $this->em->clear();

                $categoriesCache = [];
                $productsCache = [];
                $imagesCache = [];

                /*
                 * Les produits du lot sont maintenant en base.
                 * Les prochaines recherches les retrouveront.
                 */
                $reservedSlugs = [];
                $reservedSeoSlugs = [];

                $output->writeln(sprintf(
                    '<comment>%d produits traités...</comment>',
                    $i
                ));
            }
        }

        /*
         * Enregistrer le dernier lot.
         */
        $this->em->flush();
        $this->em->clear();

        $output->writeln(sprintf(
            '<info>Import terminé : %d produits traités.</info>',
            $i
        ));

        return Command::SUCCESS;
    }
}
