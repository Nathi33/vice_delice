<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        ProductRepository $productRepository
    ): Response {

        $newProducts = $productRepository->findNewProducts(8);
        $featuredProducts = $productRepository->findFeaturedProducts(8);

        return $this->render('home/index.html.twig', [
            'featuredProducts' => $featuredProducts,
            'newProducts' => $newProducts,
        ]);
    }
}