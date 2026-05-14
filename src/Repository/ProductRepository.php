<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    // =========================
    // LISTE PRODUITS FILTRÉS
    // =========================
    public function findFilteredProducts(
        int $limit,
        int $offset,
        ?string $categorySlug,
        ?string $search,
        string $sort
    ): array {

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.productImages', 'pi')
            ->addSelect('pi')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->where('p.isActive = 1');

        // =========================
        // CATÉGORIE (slug)
        // =========================
        if (!empty($categorySlug)) {
            $qb->andWhere('c.slug = :category')
               ->setParameter('category', $categorySlug);
        }

        // =========================
        // SEARCH (AMÉLIORÉ)
        // =========================
        if (!empty($search)) {
            $qb->andWhere('
                p.name LIKE :search
                OR p.slug LIKE :search
                OR p.supplierReference LIKE :search
            ')
            ->setParameter('search', '%' . $search . '%');
        }

        // =========================
        // SORT SÉCURISÉ
        // =========================
        switch ($sort) {
            case 'price_asc':
                $qb->orderBy('p.price', 'ASC');
                break;

            case 'price_desc':
                $qb->orderBy('p.price', 'DESC');
                break;

            default:
                $qb->orderBy('p.id', 'DESC');
                break;
        }

        return $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    // =========================
    // COUNT (IMPORTANT POUR PAGINATION)
    // =========================
    public function countFiltered(
        ?string $categorySlug,
        ?string $search
    ): int {

        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.id)')
            ->leftJoin('p.category', 'c')
            ->where('p.isActive = 1');

        if (!empty($categorySlug)) {
            $qb->andWhere('c.slug = :category')
               ->setParameter('category', $categorySlug);
        }

        if (!empty($search)) {
            $qb->andWhere('
                p.name LIKE :search
                OR p.slug LIKE :search
                OR p.supplierReference LIKE :search
            ')
            ->setParameter('search', '%' . $search . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}