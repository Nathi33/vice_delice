<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Créer un compte administrateur'
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse email de l’administrateur')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe de l’administrateur')
            ->addArgument('firstName', InputArgument::REQUIRED, 'Prénom')
            ->addArgument('lastName', InputArgument::REQUIRED, 'Nom');
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {

        $email = mb_strtolower(trim($input->getArgument('email')));

        $existingUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existingUser) {
            $output->writeln('<error>Un utilisateur existe déjà avec cette adresse email.</error>');

            return Command::FAILURE;
        }

        $user = new User();

        $user->setEmail($email);
        $user->setFirstName($input->getArgument('firstName'));
        $user->setLastName($input->getArgument('lastName'));
        $user->setRoles(['ROLE_ADMIN']);
        $user->setIsVerified(true);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $input->getArgument('password')
        );

        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('<info>Compte administrateur créé avec succès.</info>');

        return Command::SUCCESS;
    }
}