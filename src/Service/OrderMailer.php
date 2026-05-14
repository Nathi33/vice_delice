<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class OrderMailer
{
    public function __construct(
        private MailerInterface $mailer
    ) {}

    public function sendOrderConfirmation(Order $order): void
    {
        $email = (new Email())
            ->from('no-reply@votre-site.com')
            ->to($order->getEmail())
            ->subject('Confirmation de votre commande #' . $order->getId())
            ->html($this->buildEmailContent($order));

        $this->mailer->send($email);
    }

    private function buildEmailContent(Order $order): string
    {
        $itemsHtml = '';

        foreach ($order->getItems() as $item) {
            $itemsHtml .= "
                <tr>
                    <td>{$item->getProductName()}</td>
                    <td>{$item->getQuantity()}</td>
                    <td>{$item->getSubtotal()} €</td>
                </tr>
            ";
        }

        return "
            <h1>Merci pour votre commande</h1>

            <p>Commande #{$order->getId()}</p>

            <h3>Récapitulatif :</h3>

            <table border='1' cellpadding='8' cellspacing='0'>
                <tr>
                    <th>Produit</th>
                    <th>Quantité</th>
                    <th>Total</th>
                </tr>
                {$itemsHtml}
            </table>

            <h3>Total : {$order->getTotal()} €</h3>

            <p>Adresse de livraison :</p>
            <p>
                {$order->getFirstName()} {$order->getLastName()}<br>
                {$order->getAddress()}<br>
                {$order->getPostalCode()} {$order->getCity()}
            </p>

            <p>Votre commande est en cours de traitement.</p>
        ";
    }
}