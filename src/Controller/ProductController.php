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
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'categories' => $categoryRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    // =========================
    // AJAX PRODUITS (CATALOGUE)
    // =========================
    #[Route('/products/ajax', name: 'product_ajax')]
    public function ajax(
        Request $request,
        ProductRepository $productRepository
    ): JsonResponse {

        $limit = 24;
        $page = max(1, $request->query->getInt('page', 1));
        $offset = ($page - 1) * $limit;

        $category = $request->query->get('category'); // slug
        $sort = $request->query->get('sort', 'newest');
        $search = trim((string) $request->query->get('search', ''));

        // =========================
        // PRODUITS
        // =========================
        $products = $productRepository->findFilteredProducts(
            $limit,
            $offset,
            $category,
            $search,
            $sort
        );

        // ⚠️ IMPORTANT : même logique de filtre que findFilteredProducts
        $total = $productRepository->countFiltered(
            $category,
            $search
        );

        $totalPages = (int) ceil($total / $limit);

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
            ->andWhere('p.isActive = 1')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable');
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}