<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(
    fields: ['email'],
    message: 'Un compte existe déjà avec cette adresse email.'
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $pendingEmail = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $emailChangeToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $emailChangeTokenExpiresAt = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private string $password;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $postRegistrationRedirect = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resetTokenExpiresAt = null;

    // =========================
    // ID
    // =========================
    public function getId(): ?int
    {
        return $this->id;
    }

    // =========================
    // EMAIL
    // =========================
    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    // =========================
    // CHANGE EMAIL 
    // =========================
    public function getPendingEmail(): ?string
    {
        return $this->pendingEmail;
    }

    public function setPendingEmail(?string $pendingEmail): static
    {
        $this->pendingEmail = $pendingEmail;

        return $this;
    }

    public function getEmailChangeToken(): ?string
    {
        return $this->emailChangeToken;
    }

    public function setEmailChangeToken(?string $emailChangeToken): static
    {
        $this->emailChangeToken = $emailChangeToken;

        return $this;
    }

    public function getEmailChangeTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->emailChangeTokenExpiresAt;
    }

    public function setEmailChangeTokenExpiresAt(?\DateTimeImmutable $emailChangeTokenExpiresAt): static
    {
        $this->emailChangeTokenExpiresAt = $emailChangeTokenExpiresAt;

        return $this;
    }

    // =========================
    // USER IDENTIFIER
    // =========================
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    // =========================
    // FIRST NAME
    // =========================
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    // =========================
    // LAST NAME
    // =========================
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    // =========================
    // PHONE
    // =========================
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    // =========================
    // ROLES
    // =========================
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    // =========================
    // PASSWORD
    // =========================
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    // =========================
    // VERIFIED
    // =========================
    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    // =========================
    // ERASE CREDENTIALS
    // =========================
    public function eraseCredentials(): void
    {
    }

    // =========================
    // MDP OUBLIE
    // =========================
    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeImmutable $date): static
    {
        $this->resetTokenExpiresAt = $date;
        return $this;
    }

    // =========================
    // REDIRECTION
    // =========================

    public function getPostRegistrationRedirect(): ?string
    {
        return $this->postRegistrationRedirect;
    }

    public function setPostRegistrationRedirect(?string $url): self
    {
        $this->postRegistrationRedirect = $url;

        return $this;
    }
}