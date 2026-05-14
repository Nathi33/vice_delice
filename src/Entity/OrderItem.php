<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
class OrderItem
{
    // =========================
    // ID
    // =========================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // =========================
    // ORDER
    // =========================

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    // =========================
    // PRODUCT
    // =========================

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    // =========================
    // PRODUCT SNAPSHOT
    // =========================

    #[ORM\Column(length: 255)]
    private string $productName;

    #[ORM\Column(length: 255)]
    private string $productSlug;

    // =========================
    // VARIANT
    // =========================

    #[ORM\Column(nullable: true)]
    private ?string $variantSku = null;

    // =========================
    // QUANTITY
    // =========================

    #[ORM\Column]
    private int $quantity = 1;

    // =========================
    // PRICE
    // =========================

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $price;

    // =========================
    // SUBTOTAL
    // =========================

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $subtotal;

    // =========================
    // GETTERS / SETTERS
    // =========================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;

        return $this;
    }

    public function getProductSlug(): string
    {
        return $this->productSlug;
    }

    public function setProductSlug(string $productSlug): static
    {
        $this->productSlug = $productSlug;

        return $this;
    }

    public function getVariantSku(): ?string
    {
        return $this->variantSku;
    }

    public function setVariantSku(?string $variantSku): static
    {
        $this->variantSku = $variantSku;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getSubtotal(): string
    {
        return $this->subtotal;
    }

    public function setSubtotal(string $subtotal): static
    {
        $this->subtotal = $subtotal;

        return $this;
    }
}