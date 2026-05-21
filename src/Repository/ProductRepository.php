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
    // PRODUITS FILTRÉS
    // =========================
    public function findFilteredProducts(
        int $limit,
        int $offset,
        ?array $categoryIds,
        ?string $search,
        string $sort
    ): array {

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->where('p.isActive = 1');

        // =========================
        // CATEGORIES
        // =========================
        if (!empty($categoryIds)) {
            $qb->andWhere('c.id IN (:categories)')
               ->setParameter('categories', $categoryIds);
        }

        // =========================
        // SEARCH
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
        // SORT
        // =========================
        switch ($sort) {
            case 'price_asc':
                $qb->orderBy('p.price', 'ASC');
                break;

            case 'price_desc':
                $qb->orderBy('p.price', 'DESC');
                break;

            case 'newest':
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
    // SIMILAIRES
    // =========================
    public function findSimilarProducts(
        int $categoryId,
        int $excludeProductId,
        int $limit = 4
    ): array {

        return $this->createQueryBuilder('p')
            ->leftJoin('p.productImages', 'pi')
            ->addSelect('pi')
            ->leftJoin('p.category', 'c')
            ->where('c.id = :category')
            ->andWhere('p.id != :product')
            ->andWhere('p.isActive = 1')
            ->setParameter('category', $categoryId)
            ->setParameter('product', $excludeProductId)
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    // =========================
    // COUNT FIX (IMPORTANT)
    // =========================
    public function countFiltered(
        ?array $categoryIds,
        ?string $search
    ): int {

        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.id)')
            ->leftJoin('p.category', 'c')
            ->where('p.isActive = 1');

        if (!empty($categoryIds)) {
            $qb->andWhere('c.id IN (:categories)')
               ->setParameter('categories', $categoryIds);
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

    // =========================
    // PRODUITS MIS EN AVANT
    // =========================
    public function findFeaturedProducts(int $limit = 8): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.isActive = 1')
            ->andWhere('p.isFeatured = 1')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    // =========================
    // NOUVEAUTE
    // =========================
    public function findNewProducts(int $limit = 8): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.productImages', 'pi')
            ->addSelect('pi')
            ->where('p.isNew = 1')
            ->andWhere('p.isActive = 1')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}