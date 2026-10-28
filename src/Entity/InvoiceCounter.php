<?php

namespace App\Entity;

use App\Repository\InvoiceCounterRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceCounterRepository::class)]
#[ORM\Table(name: 'invoice_counter')]
#[ORM\UniqueConstraint(name: 'uniq_invoice_counter_year', columns: ['invoice_year'])]
class InvoiceCounter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'invoice_year', type: 'smallint', options: ['unsigned' => true])]
    private ?int $invoiceYear = null;

    #[ORM\Column(name: 'last_number', type: 'integer')]
    private ?int $lastNumber = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoiceYear(): ?int
    {
        return $this->invoiceYear;
    }

    public function setInvoiceYear(int $invoiceYear): static
    {
        $this->invoiceYear = $invoiceYear;

        return $this;
    }

    public function getLastNumber(): int
    {
        return $this->lastNumber ?? 0;
    }

    public function setLastNumber(int $lastNumber): static
    {
        $this->lastNumber = $lastNumber;

        return $this;
    }
}
