<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\String\Slugger\AsciiSlugger;
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

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 191, unique: true)]
    private string $slug;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $seoSlug = null;

    #[ORM\Column(length: 191, unique: true, nullable: true)]
    private ?string $supplierReference = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\Column(nullable: true)]
    private ?int $stock = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Brand $brand = null;

    #[ORM\OneToMany(
        targetEntity: ProductImage::class,
        mappedBy: 'product',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $productImages;

    #[ORM\OneToMany(
        targetEntity: ProductVariant::class,
        mappedBy: 'product',
        cascade: ['persist'],
        orphanRemoval: true
    )]
    private Collection $productVariants;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->productImages = new ArrayCollection();
        $this->productVariants = new ArrayCollection();
        $this->isActive = true;
    }

    // =========================
    // LIFECYCLE
    // =========================

    #[ORM\PrePersist]
    public function onCreate(): void
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;

        if (empty($this->slug) && !empty($this->name)) {
            $this->generateSlug();
        }
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();

        if (empty($this->slug) && !empty($this->name)) {
            $this->generateSlug();
        }
    }

    // =========================
    // SLUG
    // =========================

    public function generateSlug(): void
    {
        $slugger = new AsciiSlugger();

        $this->slug = strtolower(
            $slugger->slug($this->name)->toString()
        );
    }

    public function ensureSlug(): void
    {
        if (!$this->slug && $this->name) {
            $this->generateSlug();
        }
    }

    // =========================
    // PRICE HELPERS
    // =========================

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): static
    {
        $this->price = $price !== null
            ? number_format((float)$price, 2, '.', '')
            : null;

        return $this;
    }

    public function getPriceAsFloat(): float
    {
        return (float) $this->price;
    }

    // =========================
    // TIMESTAMPS
    // =========================

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // =========================
    // MAIN IMAGE
    // =========================

    public function getMainImage(): ?ProductImage
    {
        foreach ($this->productImages as $image) {
            if ($image->isMain()) {
                return $image;
            }
        }

        $first = $this->productImages->first();

        return $first instanceof ProductImage ? $first : null;
    }

    public function getMainImageUrl(): ?string
    {
        return $this->getMainImage()?->getUrl();
    }

    public function hasImages(): bool
    {
        return !$this->productImages->isEmpty();
    }

    // =========================
    // IMAGES
    // =========================

    public function getProductImages(): Collection
    {
        return $this->productImages;
    }

    public function addProductImage(ProductImage $image): static
    {
        if (!$this->productImages->contains($image)) {
            $this->productImages->add($image);
            $image->setProduct($this);
        }

        return $this;
    }

    public function removeProductImage(ProductImage $image): static
    {
        if ($this->productImages->removeElement($image)) {
            if ($image->getProduct() === $this) {
                $image->setProduct(null);
            }
        }

        return $this;
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

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(?int $stock): static
    {
        $this->stock = $stock;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): static
    {
        $this->brand = $brand;
        return $this;
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

    public function getSeoSlug(): ?string
    {
        return $this->seoSlug;
    }

    public function setSeoSlug(?string $seoSlug): static
    {
        $this->seoSlug = $seoSlug;
        return $this;
    }
}