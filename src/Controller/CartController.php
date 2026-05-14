<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    // =========================
    // PAGE PANIER
    // =========================

    #[Route('/cart', name: 'cart_index', methods: ['GET'])]
    public function index(CartService $cartService): Response
    {
        return $this->render('cart/index.html.twig', [
            'cart' => $cartService->getCartWithData()
        ]);
    }

    // =========================
    // AJOUT PRODUIT
    // =========================

    #[Route('/cart/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function add(
        int $id,
        CartService $cartService
    ): JsonResponse {

        $cartService->add($id);

        return new JsonResponse([
            'success' => true,
            'message' => 'Produit ajouté au panier'
        ]);
    }

    // =========================
    // SUPPRESSION PRODUIT
    // =========================

    #[Route('/cart/remove/{id}', name: 'cart_remove', methods: ['POST'])]
    public function remove(
        int $id,
        CartService $cartService
    ): JsonResponse {

        $cartService->remove($id);

        return new JsonResponse([
            'success' => true
        ]);
    }

    // =========================
    // DIMINUER QUANTITÉ
    // =========================

    #[Route('/cart/decrease/{id}', name: 'cart_decrease', methods: ['POST'])]
    public function decrease(
        int $id,
        CartService $cartService
    ): JsonResponse {

        $cartService->decrease($id);

        return new JsonResponse([
            'success' => true
        ]);
    }

    // =========================
    // VIDER PANIER
    // =========================

    #[Route('/cart/clear', name: 'cart_clear', methods: ['POST'])]
    public function clear(
        CartService $cartService
    ): JsonResponse {

        $cartService->clear();

        return new JsonResponse([
            'success' => true
        ]);
    }

    // =========================
    // COMPTEUR PANIER
    // =========================

    #[Route('/cart/count', name: 'cart_count', methods: ['GET'])]
    public function count(
        CartService $cartService
    ): JsonResponse {

        $cart = $cartService->getCart();

        $count = array_sum($cart);

        return new JsonResponse([
            'count' => $count
        ]);
    }
}