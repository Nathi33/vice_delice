<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    // =========================
    // PAGE CATALOGUE
    // =========================
    #[Route('/products', name: 'product_index')]
    public function index(CategoryRepository $categoryRepository, Request $request): Response
    {
        return $this->render('product/index.html.twig', [
            'categories' => $categoryRepository->findBy([], ['name' => 'ASC']),
            'selectedCategory' => $request->query->get('category'),
        ]);
    }

    // =========================
    // AJAX PRODUITS
    // =========================
    #[Route('/products/ajax', name: 'product_ajax')]
    public function ajax(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): JsonResponse {

        $limit = 24;
        $page = max(1, $request->query->getInt('page', 1));
        $offset = ($page - 1) * $limit;

        $categorySlug = $request->query->get('category');
        $sort = $request->query->get('sort', 'newest');
        $search = trim((string) $request->query->get('search', ''));

        // =========================
        // MAPPING HOMEPAGE (IMPORTANT)
        // =========================
        $mapping = [
            'godes-dildos' => 'sextoys',

            // ⚠️ on évite doublon logique
            // "délices intimes" → lingerie / mode
            'delices-intimes' => 'mode-et-lingerie',

            'pharmacie' => 'pharmacie',
        ];

        $resolvedSlug = $mapping[$categorySlug] ?? $categorySlug;

        // =========================
        // CATÉGORIE + ENFANTS
        // =========================
        $categoryIds = null;

        if (!empty($resolvedSlug)) {

            $category = $categoryRepository->findOneBy([
                'slug' => $resolvedSlug
            ]);

            if ($category) {

                $categoryIds = [$category->getId()];

                foreach ($category->getChildren() as $child) {
                    $categoryIds[] = $child->getId();
                }
            }
        }

        // =========================
        // PRODUITS
        // =========================
        $products = $productRepository->findFilteredProducts(
            $limit,
            $offset,
            $categoryIds,
            $search,
            $sort
        );

        $total = $productRepository->countFiltered(
            $categoryIds,
            $search
        );

        // ⚠️ sécurité division par 0
        $totalPages = $limit > 0 ? (int) ceil($total / $limit) : 1;

        $data = [];

        foreach ($products as $product) {

            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'slug' => $product->getSlug(),
                'image' => $product->getMainImageUrl(),
            ];
        }

        return new JsonResponse([
            'products' => $data,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total
        ]);
    }

    // =========================
    // FICHE PRODUIT
    // =========================
    #[Route('/product/{slug}', name: 'product_show')]
    public function show(
        string $slug,
        ProductRepository $productRepository
    ): Response {

        $product = $productRepository->createQueryBuilder('p')
            ->leftJoin('p.productImages', 'pi')
            ->addSelect('pi')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->where('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable');
        }

        $similarProducts = [];

        if ($product->getCategory()) {

            $categoryIds = [$product->getCategory()->getId()];

            foreach ($product->getCategory()->getChildren() as $child) {
                $categoryIds[] = $child->getId();
            }

            $similarProducts = $productRepository->createQueryBuilder('p')
                ->leftJoin('p.productImages', 'pi')
                ->addSelect('pi')
                ->where('p.category IN (:categories)')
                ->andWhere('p.id != :product')
                ->andWhere('p.isActive = 1')
                ->setParameter('categories', $categoryIds)
                ->setParameter('product', $product->getId())
                ->setMaxResults(4)
                ->getQuery()
                ->getResult();
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'similarProducts' => $similarProducts,
        ]);
    }

    // =========================
    // NOUVEAUTES
    // =========================
    #[Route('/nouveautes', name: 'product_new')]
    public function newProducts(ProductRepository $productRepository): Response
    {
        $products = $productRepository->createQueryBuilder('p')
            ->leftJoin('p.productImages', 'pi')
            ->addSelect('pi')
            ->where('p.isNew = 1')
            ->andWhere('p.isActive = 1')
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('product/new.html.twig', [
            'products' => $products
        ]);
    }
}