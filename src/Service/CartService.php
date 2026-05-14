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

    public function getCart(): array
    {
        return $this->getSession()->get('cart', []);
    }

    public function add(int $productId): void
    {
        $cart = $this->getCart();

        if (!isset($cart[$productId])) {
            $cart[$productId] = 1;
        } else {
            $cart[$productId]++;
        }

        $this->getSession()->set('cart', $cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->getCart();

        if (isset($cart[$productId])) {
            unset($cart[$productId]);
        }

        $this->getSession()->set('cart', $cart);
    }

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

        $this->getSession()->set('cart', $cart);
    }

    public function clear(): void
    {
        $this->getSession()->set('cart', []);
    }

    public function getCartWithData(): array
    {
        $cart = $this->getCart();

        $data = [];
        $total = 0;

        foreach ($cart as $productId => $qty) {
            $product = $this->productRepository->find($productId);

            if (!$product) continue;

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