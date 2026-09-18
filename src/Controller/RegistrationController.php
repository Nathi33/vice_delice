<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

use Symfony\Component\Security\Http\Util\TargetPathTrait;

use Symfony\Component\Routing\Attribute\Route;

use Symfony\Contracts\Translation\TranslatorInterface;

use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    use TargetPathTrait;

    public function __construct(
        private EmailVerifier $emailVerifier
    ) {
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {

        $user = new User();

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $user->setIsVerified(false);

            $redirectUrl = $request->query->get('redirect');

            if (
                $redirectUrl &&
                str_starts_with($redirectUrl, '/') &&
                !str_starts_with($redirectUrl, '//')
            ) {
                $user->setPostRegistrationRedirect($redirectUrl);
            }

            $entityManager->persist($user);
            $entityManager->flush(); // ✅ ID dispo ici

            // IMPORTANT : maintenant seulement
            $request->getSession()->set(
                'pending_verification_user_id',
                $user->getId()
            );

            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address('noreply@vicedelice.com', 'Vice & Délice'))
                    ->to($user->getEmail())
                    ->subject('Confirmez votre compte Vice & Délice')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            return $this->redirectToRoute('app_check_email');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        return $this->render('registration/check_email.html.twig');
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        UserRepository $userRepository,
        UserAuthenticatorInterface $userAuthenticator,
        LoginFormAuthenticator $authenticator,
        EntityManagerInterface $entityManager
    ): Response {

        $id = $request->query->get('id');

        if (!$id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (!$user) {
            return $this->redirectToRoute('app_register');
        }

        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $e) {
            return $this->redirectToRoute('app_check_email');
        }

        $user->setIsVerified(true);

        // on récupère l'URL sauvegardée avant de la supprimer
        $redirectUrl = $user->getPostRegistrationRedirect();

        $user->setPostRegistrationRedirect(null);
        $entityManager->flush();

        // login automatique
        $response = $userAuthenticator->authenticateUser(
            $user,
            $authenticator,
            $request
        );

        if ($redirectUrl) {
            $response->setTargetUrl($redirectUrl);
        }

        return $response;
    }

    #[Route('/resend-verification', name: 'app_resend_verification')]
    public function resendVerification(
        Request $request,
        UserRepository $userRepository
    ): Response {

        $session = $request->getSession();
        $id = $session->get('pending_verification_user_id');

        if (!$id) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_check_email');
        }

        $user = $userRepository->find($id);

        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_check_email');
        }

        $this->emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $user,
            (new TemplatedEmail())
                ->from(new Address('noreply@vicedelice.com', 'Vice & Délice'))
                ->to($user->getEmail())
                ->subject('Confirmez votre compte Vice & Délice')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );

        $this->addFlash('success', 'Un nouvel email de confirmation vous a été envoyé.');

        return $this->redirectToRoute('app_check_email');
    }

}