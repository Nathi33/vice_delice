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

        if ($categorySlug) {
            $qb->andWhere('c.slug = :category')
               ->setParameter('category', $categorySlug);
        }

        if ($search) {
            $qb->andWhere('p.name LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        match ($sort) {
            'price_asc' => $qb->orderBy('p.price', 'ASC'),
            'price_desc' => $qb->orderBy('p.price', 'DESC'),
            default => $qb->orderBy('p.id', 'DESC'),
        };

        return $qb->setFirstResult($offset)
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    public function countFiltered(?string $categorySlug, ?string $search): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->leftJoin('p.category', 'c')
            ->where('p.isActive = 1');

        if ($categorySlug) {
            $qb->andWhere('c.slug = :category')
               ->setParameter('category', $categorySlug);
        }

        if ($search) {
            $qb->andWhere('p.name LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}