<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    
    public function paginateAdmin(
        int $page = 1,
        int $limit = 20,
        string $search = '',
        ?int $parentId = null,
        ?string $status = null,
        string $sort = 'name',
        string $direction = 'ASC'
    ): array {
        $page = max(1, $page);

        $allowedSorts = [
            'id',
            'name',
            'externalReference',
            'createdAt',
            'isActive',
        ];

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }

        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $qb = $this->createQueryBuilder('c');

        if ($search !== '') {
            $qb
                ->andWhere(
                    'c.name LIKE :search
                    OR c.externalReference LIKE :search'
                )
                ->setParameter('search', '%' . $search . '%');
        }

        if ($parentId !== null && $parentId > 0) {
            $qb
                ->andWhere('IDENTITY(c.parent) = :parentId')
                ->setParameter('parentId', $parentId);
        }

        if ($status === 'active') {
            $qb->andWhere('c.isActive = :active')
                ->setParameter('active', true);
        } elseif ($status === 'inactive') {
            $qb->andWhere('c.isActive = :active')
                ->setParameter('active', false);
        }

        $qb
            ->orderBy('c.' . $sort, $direction)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb);

        return [
            'items' => iterator_to_array($paginator),
            'total' => count($paginator),
            'pages' => (int) ceil(count($paginator) / $limit),
            'currentPage' => $page,
        ];
    }

    
    public function findOneByExternalReference(string $externalReference): ?Category
    {
        return $this->findOneBy([
            'externalReference' => $externalReference,
        ]);
    }

    public function findAdminSuggestions(
        string $search,
        int $limit = 8
    ): array {
        $search = trim($search);

        if ($search === '') {
            return [];
        }

        return $this->createQueryBuilder('c')
            ->andWhere(
                'c.name LIKE :search
                OR c.externalReference LIKE :search'
            )
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('c.name', 'ASC')
            ->setMaxResults(max(1, min($limit, 10)))
            ->getQuery()
            ->getResult();
    }

}
