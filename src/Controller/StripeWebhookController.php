<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Service\OrderMailer;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StripeWebhookController extends AbstractController
{
    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function index(
        Request $request,
        OrderRepository $orderRepository,
        EntityManagerInterface $em,
        OrderMailer $orderMailer
    ): Response {

        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        $endpointSecret = $_ENV['STRIPE_WEBHOOK_SECRET'];

        // =========================
        // 1. VALIDATION SIGNATURE STRIPE
        // =========================
        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\Throwable $e) {
            return new Response('Invalid signature', 400);
        }

        // =========================
        // 2. ON TRAITE UNIQUEMENT LE BON EVENT
        // =========================
        if ($event->type !== 'checkout.session.completed') {
            return new Response('Ignored', 200);
        }

        $session = $event->data->object;

        // =========================
        // 3. SÉCURITÉ : PAYMENT STATUS
        // =========================
        if (($session->payment_status ?? null) !== 'paid') {
            return new Response('Payment not completed', 200);
        }

        // =========================
        // 4. RÉCUP ORDER ID
        // =========================
        $orderId = $session->metadata->order_id ?? null;

        if (!$orderId) {
            return new Response('No order id', 400);
        }

        $order = $orderRepository->find($orderId);

        if (!$order) {
            return new Response('Order not found', 404);
        }

        // =========================
        // 5. IDEMPOTENCE (ANTI DOUBLE WEBHOOK)
        // =========================
        if ($order->getStatus() === 'paid') {
            return new Response('Already processed', 200);
        }

        // =========================
        // 6. UPDATE COMMANDE
        // =========================
        $order->setStatus('paid');
        $order->setPaidAt(new \DateTimeImmutable());

        // optionnel (très recommandé si tu debug plus tard)
        // $order->setStripeSessionId($session->id);

        $em->flush();

        // =========================
        // 7. EMAIL FINAL CLIENT
        // =========================
        $orderMailer->sendOrderConfirmation($order);

        return new Response('OK', 200);
    }
}