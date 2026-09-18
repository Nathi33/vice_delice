<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(
        AuthenticationUtils $authenticationUtils,
    ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('product_index');
        }

        // $referer = $request->headers->get('referer');

        // if (
        //     $referer &&
        //     !str_contains($referer, '/login') &&
        //     !str_contains($referer, '/register')
        // ) {
        //     $request->getSession()->set(
        //         'after_login_redirect',
        //         $referer
        //     );
        // }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Intercepté par Symfony firewall.');
    }
}