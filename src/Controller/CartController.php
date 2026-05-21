<?php

namespace App\Controller;

use App\Service\CartService;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'cart_index', methods: ['GET'])]
    public function index(CartService $cartService): Response
    {
        return $this->render('cart/index.html.twig', [
            'cart' => $cartService->getCartWithData()
        ]);
    }

    // =========================
    // AJOUT PRODUIT (STOCK SAFE)
    // =========================

    #[Route('/cart/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function add(
        int $id,
        Request $request,
        CartService $cartService,
        ProductRepository $productRepository
    ): JsonResponse {

        $qty = max(1, (int) $request->request->get('qty', 1));

        $product = $productRepository->find($id);

        if (!$product) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Produit introuvable'
            ], 404);
        }

        $currentQty = $cartService->getProductQuantity($id);
        $stock = $product->getStock();

        if ($currentQty + $qty > $stock) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Stock insuffisant',
                'stock' => $stock,
                'in_cart' => $currentQty
            ], 400);
        }

        for ($i = 0; $i < $qty; $i++) {
            $cartService->add($id);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Produit ajouté au panier'
        ]);
    }

    // =========================
    // DIMINUER
    // =========================

    #[Route('/cart/decrease/{id}', name: 'cart_decrease', methods: ['POST'])]
    public function decrease(int $id, CartService $cartService): JsonResponse
    {
        $cartService->decrease($id);

        return new JsonResponse(['success' => true]);
    }

    // =========================
    // SUPPRIMER
    // =========================

    #[Route('/cart/remove/{id}', name: 'cart_remove', methods: ['POST'])]
    public function remove(int $id, CartService $cartService): JsonResponse
    {
        $cartService->remove($id);

        return new JsonResponse(['success' => true]);
    }

    // =========================
    // VIDER
    // =========================

    #[Route('/cart/clear', name: 'cart_clear', methods: ['POST'])]
    public function clear(CartService $cartService): JsonResponse
    {
        $cartService->clear();

        return new JsonResponse(['success' => true]);
    }

    // =========================
    // COUNT
    // =========================

    #[Route('/cart/count', name: 'cart_count', methods: ['GET'])]
    public function count(CartService $cartService): JsonResponse
    {
        return new JsonResponse([
            'count' => array_sum($cartService->getCart())
        ]);
    }

    // =========================
    // STOCK
    // =========================

    #[Route('/cart/set/{id}', name: 'cart_set', methods: ['POST'])]
    public function setQty(
        int $id,
        Request $request,
        CartService $cartService,
        ProductRepository $productRepository
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);
        $qty = (int) ($data['qty'] ?? 1);

        $product = $productRepository->find($id);

        if (!$product) {
            return new JsonResponse(['error' => 'not found'], 404);
        }

        $stock = $product->getStock();

        // clamp stock
        $qty = max(1, min($qty, $stock));

        // update cart
        $cartService->set($id, $qty);

        // 🔥 CALCUL IMPORTANT MANQUANT
        $subtotal = $product->getPrice() * $qty;

        return new JsonResponse([
            'success' => true,
            'qty' => $qty,
            'stock' => $stock,
            'subtotal' => $subtotal
        ]);
    }
}