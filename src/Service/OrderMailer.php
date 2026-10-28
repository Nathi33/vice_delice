<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class OrderMailer
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    /**
     * Envoie l'e-mail habituel de confirmation de commande.
     */
    public function sendOrderConfirmation(Order $order): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@votre-site.com', 'Vice & Délice'))
            ->to($order->getEmail())
            ->subject('Confirmation de votre commande #' . $order->getId())
            ->htmlTemplate('emails/order_confirmation.html.twig')
            ->context([
                'order' => $order,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Envoie un PDF de test en pièce jointe.
     *
     * Cette méthode doit être utilisée uniquement pour des tests
     * avec une adresse e-mail que tu contrôles.
     */
    public function sendTestPdf(
        Order $order,
        string $pdfContent,
        string $recipientEmail
    ): void {
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@votre-site.com', 'Vice & Délice'))
            ->to($recipientEmail)
            ->subject('Test PDF — commande #' . $order->getId())
            ->htmlTemplate('emails/order_confirmation.html.twig')
            ->context([
                'order' => $order,
            ])
            ->attach(
                $pdfContent,
                'document-test-commande-' . $order->getId() . '.pdf',
                'application/pdf'
            );

        $this->mailer->send($email);
    }
}
