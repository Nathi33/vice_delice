<?php

namespace App\Service;

use App\Entity\Order;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripeService
{
    public function __construct(
        private string $stripeSecretKey,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function createCheckoutSession(Order $order): string
    {
        Stripe::setApiKey($this->stripeSecretKey);

        $lineItems = [];

        foreach ($order->getItems() as $item) {

            $lineItems[] = [
                'quantity' => $item->getQuantity(),
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => (int) round($item->getPrice() * 100),
                    'product_data' => [
                        'name' => $item->getProductName(),
                    ],
                ],
            ];
        }

        $session = Session::create([
            'mode' => 'payment',

            // 🔥 lien critique avec ta commande
            'metadata' => [
                'order_id' => $order->getId(),
            ],

            // 🔥 utile pour tracking Stripe
            'client_reference_id' => (string) $order->getId(),

            // 🔥 email Stripe (très utile pour reçus)
            'customer_email' => $order->getEmail(),

            'line_items' => $lineItems,

            'success_url' => $this->urlGenerator->generate(
                'checkout_success',
                ['id' => $order->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),

            'cancel_url' => $this->urlGenerator->generate(
                'checkout_cancel',
                ['id' => $order->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ]);

        return $session->url;
    }
}