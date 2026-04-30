<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'product')]
#[ORM\UniqueConstraint(name: 'UNIQ_PRODUCT_SLUG', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // =========================
    // IDENTITÉ MÉTIER IMPORT
    // =========================

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(length: 100, unique: true, nullable: true)]
    private ?string $supplierReference = null;

    // =========================
    // CONTENU
    // =========================

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    // ⚠ mieux que string pour import
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\Column(nullable: true)]
    private ?int $stock = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    // =========================
    // RELATIONS
    // =========================

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Brand $brand = null;

    /**
     * @var Collection<int, ProductImage>
     */
    #[ORM\OneToMany(
        targetEntity: ProductImage::class,
        mappedBy: 'product',
        cascade: ['persist'],
        orphanRemoval: true
    )]
    private Collection $productImages;

    /**
     * @var Collection<int, ProductVariant>
     */
    #[ORM\OneToMany(
        targetEntity: ProductVariant::class,
        mappedBy: 'product',
        cascade: ['persist'],
        orphanRemoval: true
    )]
    private Collection $productVariants;

    // =========================
    // DATES
    // =========================

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->productImages = new ArrayCollection();
        $this->productVariants = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->isActive = true;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // =========================
    // GETTERS / SETTERS
    // =========================

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }

    public function setName(string $name): static
    {
        $this->name = trim($name);
        return $this;
    }

    public function getSlug(): string { return $this->slug; }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getSupplierReference(): ?string
    {
        return $this->supplierReference;
    }

    public function setSupplierReference(?string $ref): static
    {
        $this->supplierReference = $ref;
        return $this;
    }

    public function getPrice(): ?string { return $this->price; }

    public function setPrice(?string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getStock(): ?int { return $this->stock; }

    public function setStock(?int $stock): static
    {
        $this->stock = $stock;
        return $this;
    }

    public function isActive(): bool { return $this->isActive; }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCategory(): ?Category { return $this->category; }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getBrand(): ?Brand { return $this->brand; }

    public function setBrand(?Brand $brand): static
    {
        $this->brand = $brand;
        return $this;
    }

    public function getProductImages(): Collection
    {
        return $this->productImages;
    }

    public function getProductVariants(): Collection
    {
        return $this->productVariants;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }
}