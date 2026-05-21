<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\ProductRepository;

class CartService
{
    public function __construct(
        private RequestStack $requestStack,
        private ProductRepository $productRepository
    ) {}

    private function getSession()
    {
        return $this->requestStack->getSession();
    }

    // =========================
    // GET CART RAW
    // =========================

    public function getCart(): array
    {
        return $this->getSession()->get('cart', []);
    }

    // =========================
    // SAVE CART (FIX IMPORTANT)
    // =========================

    private function saveCart(array $cart): void
    {
        $this->getSession()->set('cart', $cart);
    }

    // =========================
    // ADD PRODUCT
    // =========================

    public function add(int $productId): void
    {
        $cart = $this->getCart();

        $product = $this->productRepository->find($productId);

        if (!$product) {
            return;
        }

        $stock = $product->getStock();
        $currentQty = $cart[$productId] ?? 0;

        // sécurité stock
        if ($currentQty >= $stock) {
            return;
        }

        $cart[$productId] = $currentQty + 1;

        $this->saveCart($cart);
    }

    // =========================
    // REMOVE PRODUCT
    // =========================

    public function remove(int $productId): void
    {
        $cart = $this->getCart();

        if (isset($cart[$productId])) {
            unset($cart[$productId]);
        }

        $this->saveCart($cart);
    }

    // =========================
    // DECREASE QUANTITY
    // =========================

    public function decrease(int $productId): void
    {
        $cart = $this->getCart();

        if (!isset($cart[$productId])) {
            return;
        }

        $cart[$productId]--;

        if ($cart[$productId] <= 0) {
            unset($cart[$productId]);
        }

        $this->saveCart($cart);
    }

    // =========================
    // CLEAR CART
    // =========================

    public function clear(): void
    {
        $this->saveCart([]);
    }

    // =========================
    // SET QUANTITY (IMPORTANT)
    // =========================

    public function set(int $productId, int $qty): void
    {
        $cart = $this->getCart();

        $product = $this->productRepository->find($productId);

        if (!$product) {
            return;
        }

        $stock = $product->getStock();

        // clamp stock
        $qty = max(1, min($qty, $stock));

        if ($qty <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = $qty;
        }

        $this->saveCart($cart);
    }

    // =========================
    // COUNT
    // =========================

    public function getProductQuantity(int $productId): int
    {
        $cart = $this->getCart();

        return $cart[$productId] ?? 0;
    }

    // =========================
    // CART WITH DATA
    // =========================

    public function getCartWithData(): array
    {
        $cart = $this->getCart();

        $data = [];
        $total = 0;

        foreach ($cart as $productId => $qty) {

            $product = $this->productRepository->find($productId);

            if (!$product) {
                continue;
            }

            $subtotal = $product->getPrice() * $qty;

            $data[] = [
                'product' => $product,
                'qty' => $qty,
                'subtotal' => $subtotal
            ];

            $total += $subtotal;
        }

        return [
            'items' => $data,
            'total' => $total
        ];
    }
}