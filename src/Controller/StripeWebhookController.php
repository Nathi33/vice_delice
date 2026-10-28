<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\OrderMailer;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
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
        // 1. Vérifier la signature Stripe.
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        $endpointSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';

        if ($endpointSecret === '' || $sigHeader === null) {
            return new Response('Invalid signature configuration', 400);
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\Throwable $e) {
            return new Response('Invalid signature', 400);
        }

        // 2. Ne traiter que les paiements confirmés par Stripe.
        if ($event->type !== 'checkout.session.completed') {
            return new Response('Ignored', 200);
        }

        $session = $event->data->object;

        if (($session->payment_status ?? null) !== 'paid') {
            return new Response('Payment not completed', 200);
        }

        // 3. Retrouver la commande.
        $orderId = $session->metadata->order_id ?? null;

        if (
            !is_scalar($orderId)
            || filter_var($orderId, FILTER_VALIDATE_INT) === false
            || (int) $orderId <= 0
        ) {
            return new Response('No valid order id', 400);
        }

        $order = $orderRepository->find((int) $orderId);

        if (!$order) {
            return new Response('Order not found', 404);
        }

        $connection = $em->getConnection();

        try {
            $connection->beginTransaction();

            // 4. Réserver l'événement. L'index unique bloque les doublons.
            try {
                $connection->insert('stripe_event', [
                    'stripe_event_id' => $event->id,
                    'event_type' => $event->type,
                ]);
            } catch (UniqueConstraintViolationException $e) {
                $connection->rollBack();

                return new Response('Already processed', 200);
            }

            // 5. Verrouiller la commande jusqu'à la fin du traitement.
            $connection->executeQuery(
                'SELECT id FROM `order` WHERE id = :id FOR UPDATE',
                ['id' => $order->getId()]
            );

            // Recharger les données après l'acquisition du verrou.
            $em->refresh($order);

            if ($order->getStatus() !== 'paid') {
                $order->setStatus('paid');
            }

            if ($order->getPaidAt() === null) {
                $order->setPaidAt(new \DateTimeImmutable());
            }

            $em->flush();

            // 6. Envoyer la confirmation seulement si elle n'est pas enregistrée.
            if ($order->getOrderConfirmationEmailSentAt() === null) {
                $orderMailer->sendOrderConfirmation($order);

                $order->setOrderConfirmationEmailSentAt(
                    new \DateTimeImmutable()
                );

                $em->flush();
            }

            // 7. Valider ensemble les modifications en base.
            $connection->commit();

            return new Response('OK', 200);
        } catch (\Throwable $e) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            return new Response('Webhook processing failed', 500);
        }
    }
}
