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
    #[Route('/products', name: 'product_index')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/products/ajax', name: 'product_ajax')]
    public function ajax(
        Request $request,
        ProductRepository $productRepository
    ): JsonResponse {

        $limit = 24;
        $page = max(1, (int) $request->query->get('page', 1));
        $offset = ($page - 1) * $limit;

        $category = $request->query->get('category');
        $sort = $request->query->get('sort', 'newest');
        $search = $request->query->get('search');

        $products = $productRepository->findFilteredProducts(
            $limit,
            $offset,
            $category,
            $search,
            $sort
        );

        $total = $productRepository->countFiltered($category, $search);
        $totalPages = (int) ceil($total / $limit);

        $data = [];

        foreach ($products as $product) {
            $data[] = [
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'slug' => $product->getSlug(),
                'image' => $product->getMainImageUrl(),
            ];
        }

        $response = new JsonResponse([
            'products' => $data,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);

        $response->setPublic();
        $response->setMaxAge(30);

        return $response;
    }
}