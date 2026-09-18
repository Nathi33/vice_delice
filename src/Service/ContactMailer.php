<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class ContactMailer
{
    public function __construct(
        private MailerInterface $mailer
    ) {}

    public function sendContactMessage(
        string $name,
        string $email,
        ?string $orderNumber,
        string $subject,
        string $message
    ): void {
        $mail = (new TemplatedEmail())
            ->from(new Address('no-reply@votre-site.com', 'Vice & Délice'))
            ->to('vice-delice@outlook.fr')
            ->replyTo(new Address($email, $name))
            ->subject('Contact - ' . $subject)
            ->htmlTemplate('emails/contact.html.twig')
            ->context([
                'name' => $name,
                'senderEmail' => $email,
                'orderNumber' => $orderNumber,
                'subject' => $subject,
                'message' => $message,
            ]);

        $this->mailer->send($mail);
    }
}