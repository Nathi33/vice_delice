<?php

namespace App\Entity;

use App\Repository\StripeEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StripeEventRepository::class)]
#[ORM\Table(name: 'stripe_event')]
#[ORM\UniqueConstraint(
    name: 'uniq_stripe_event_id',
    columns: ['stripe_event_id']
)]
class StripeEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'stripe_event_id', length: 191)]
    private ?string $stripeEventId = null;

    #[ORM\Column(name: 'event_type', length: 255)]
    private ?string $eventType = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStripeEventId(): ?string
    {
        return $this->stripeEventId;
    }

    public function setStripeEventId(string $stripeEventId): static
    {
        $this->stripeEventId = $stripeEventId;

        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): static
    {
        $this->eventType = $eventType;

        return $this;
    }
}
