<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Validator\Constraints\StrongPassword;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ResetPasswordController extends AbstractController
{
    #[Route('/mot-de-passe-oublie', name: 'app_forgot_password')]
    public function request(
        Request $request,
        UserRepository $repo,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {

        if ($request->isMethod('POST')) {

            $email = $request->request->get('email');
            $user = $repo->findOneBy(['email' => $email]);

            // 🔐 sécurité : toujours répondre pareil
            if ($user) {

                $token = bin2hex(random_bytes(32));

                $user->setResetToken($token);
                $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));

                $em->flush();

                $emailMessage = (new TemplatedEmail())
                    ->from(new Address('noreply@vice-delice.com', 'Vice & Délice'))
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe')
                    ->htmlTemplate('security/email_reset.html.twig')
                    ->context([
                        'token' => $token,
                        'user' => $user
                    ]);

                $mailer->send($emailMessage);
            }

            return $this->redirectToRoute('app_check_email_reset');
        }

        return $this->render('security/forgot_password.html.twig');
    }

    #[Route('/check-email-reset', name: 'app_check_email_reset')]
    public function check(): Response
    {
        return $this->render('security/check_email_reset.html.twig');
    }

    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(
        string $token,
        Request $request,
        UserRepository $repo,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        ValidatorInterface $validator
    ): Response {

        $user = $repo->findOneBy(['resetToken' => $token]);

        if (!$user || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {

            // Vérification CSRF
            if (!$this->isCsrfTokenValid(
                'reset_password',
                $request->request->get('_csrf_token')
            )) {
                $this->addFlash(
                    'error',
                    'La session a expiré. Veuillez recommencer la procédure.'
                );

                return $this->redirectToRoute('app_forgot_password');
            }

            $password = $request->request->get('password');
            $passwordConfirm = $request->request->get('password_confirm');

            // Vérification des deux mots de passe
            if ($password !== $passwordConfirm) {

                $this->addFlash(
                    'error',
                    'Les deux mots de passe ne correspondent pas.'
                );

                return $this->redirectToRoute(
                    'app_reset_password',
                    ['token' => $token]
                );
            }

            $violations = $validator->validate(
                $password,
                new StrongPassword()
            );

            if ($violations->count() > 0) {

                $this->addFlash(
                    'error',
                    $violations->get(0)->getMessage()
                );

                return $this->redirectToRoute(
                    'app_reset_password',
                    ['token' => $token]
                );
            }

            $user->setPassword(
                $hasher->hashPassword($user, $password)
            );

            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);

            $em->flush();

            $this->addFlash(
                'success',
                'Votre mot de passe a été mis à jour.'
            );

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token
        ]);
    }
}