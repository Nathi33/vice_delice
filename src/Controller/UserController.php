<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ChangePasswordFormType;
use App\Form\EditProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class UserController extends AbstractController
{
    #[Route('/mon-compte', name: 'app_account')]
    public function account(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/account.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/mon-compte/modifier-mot-de-passe', name: 'app_change_password')]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $currentPassword = $form->get('currentPassword')->getData();

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $form->get('currentPassword')->addError(
                    new \Symfony\Component\Form\FormError(
                        'Votre mot de passe actuel est incorrect.'
                    )
                );
            } else {

                $newPassword = $form->get('newPassword')->getData();

                $user->setPassword(
                    $passwordHasher->hashPassword($user, $newPassword)
                );

                $em->flush();

                $this->addFlash(
                    'success',
                    'Votre mot de passe a bien été modifié.'
                );

                return $this->redirectToRoute('app_account');
            }
        }

        return $this->render('user/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/mon-compte/modifier-mes-informations', name: 'app_edit_profile')]
    public function editProfile(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        UserRepository $repo
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(EditProfileFormType::class, [
            'email' => $user->getEmail(),
            'phone' => $user->getPhone(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $newEmail = mb_strtolower(trim($form->get('email')->getData()));
            $currentEmail = mb_strtolower(trim($user->getEmail()));

            $phone = $form->get('phone')->getData();

            $user->setPhone($phone);

            // L'adresse e-mail n'a pas changé
            if (mb_strtolower($newEmail) === mb_strtolower($currentEmail)) {

                $em->flush();

                $this->addFlash(
                    'success',
                    'Vos informations ont bien été mises à jour.'
                );

                return $this->redirectToRoute('app_account');
            }

            // Vérifier que la nouvelle adresse n'est pas déjà utilisée
            $existingUser = $repo->findOneBy(['email' => $newEmail]);

            if ($existingUser && $existingUser->getId() !== $user->getId()) {

                $form->get('email')->addError(
                    new \Symfony\Component\Form\FormError(
                        'Cette adresse e-mail est déjà utilisée.'
                    )
                );

            } else {

                $token = bin2hex(random_bytes(32));

                $user->setPendingEmail($newEmail);
                $user->setEmailChangeToken($token);
                $user->setEmailChangeTokenExpiresAt(
                    new \DateTimeImmutable('+1 hour')
                );

                $em->flush();

                $emailMessage = (new TemplatedEmail())
                    ->from(new Address(
                        'noreply@vice-delice.com',
                        'Vice & Délice'
                    ))
                    ->to($newEmail)
                    ->subject('Confirmez votre nouvelle adresse e-mail')
                    ->htmlTemplate('security/email_change.html.twig')
                    ->context([
                        'token' => $token,
                        'user' => $user,
                        'newEmail' => $newEmail,
                    ]);

                $mailer->send($emailMessage);

                $this->addFlash(
                    'success',
                    'Un e-mail de confirmation a été envoyé à votre nouvelle adresse.'
                );

                return $this->redirectToRoute('app_account');
            }
        }

        return $this->render('user/edit_profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/mon-compte/confirmer-email/{token}', name: 'app_confirm_email_change')]
    public function confirmEmailChange(
        string $token,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($user->getEmailChangeToken() !== $token) {
            $this->addFlash(
                'error',
                'Le lien de confirmation est invalide.'
            );

            return $this->redirectToRoute('app_account');
        }

        $expiresAt = $user->getEmailChangeTokenExpiresAt();

        if (!$expiresAt || $expiresAt < new \DateTimeImmutable()) {

            $user->setPendingEmail(null);
            $user->setEmailChangeToken(null);
            $user->setEmailChangeTokenExpiresAt(null);

            $em->flush();

            $this->addFlash(
                'error',
                'Le lien de confirmation a expiré. Veuillez recommencer la modification de votre adresse e-mail.'
            );

            return $this->redirectToRoute('app_edit_profile');
        }

        $pendingEmail = $user->getPendingEmail();

        if (!$pendingEmail) {
            $this->addFlash(
                'error',
                'Aucune nouvelle adresse e-mail à confirmer.'
            );

            return $this->redirectToRoute('app_account');
        }

        $user->setEmail($pendingEmail);
        $user->setPendingEmail(null);
        $user->setEmailChangeToken(null);
        $user->setEmailChangeTokenExpiresAt(null);

        $em->flush();

        $this->addFlash(
            'success',
            'Votre adresse e-mail a bien été modifiée.'
        );

        return $this->redirectToRoute('app_account');
    }
}