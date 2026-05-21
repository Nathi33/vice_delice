<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DebugController extends AbstractController
{
    #[Route('/debug/categories', name: 'debug_categories')]
    public function categories(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        // affichage simple et rapide pour debug
        dd($categories);

        // si jamais tu veux une version affichée en page :
        /*
        return $this->render('debug/categories.html.twig', [
            'categories' => $categories
        ]);
        */
    }
}