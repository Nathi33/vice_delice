<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\ContactType;
use App\Service\ContactMailer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class LegalController extends AbstractController
{
    #[Route('/mentions-legales', name: 'app_legal')]
    public function legal(): Response
    {
        return $this->render('legal/index.html.twig');
    }

    #[Route('/politique-confidentialite', name: 'app_privacy')]
    public function privacy(): Response
    {
        return $this->render('legal/privacy.html.twig');
    }

    #[Route('/conditions-generales', name: 'app_cgv')]
    public function cgv(): Response
    {
        return $this->render('legal/cgv.html.twig');
    }

    #[Route('/livraison', name: 'app_shipping')]
    public function shipping(): Response
    {
        return $this->render('legal/shipping.html.twig');
    }

    #[Route('/faq', name: 'app_faq')]
    public function faq(): Response
    {
        return $this->render('legal/faq.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(
        Request $request,
        ContactMailer $contactMailer
    ): Response {
        $form = $this->createForm(ContactType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            $contactMailer->sendContactMessage(
                $data['name'],
                $data['email'],
                $data['orderNumber'],
                $data['subject'],
                $data['message']
            );

            $this->addFlash(
                'success',
                'Votre message a bien été envoyé. Nous vous répondrons dans les meilleurs délais.'
            );

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('legal/contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/cookies', name: 'app_cookies')]
    public function cookies(): Response
    {
        return $this->render('legal/cookies.html.twig');
    }
}
