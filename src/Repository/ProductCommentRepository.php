<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\ProductCommentBundle\Entity\ProductComment;

/**
 * @extends ServiceEntityRepository<ProductComment>
 */
#[AsRepository(entityClass: ProductComment::class)]
final class ProductCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductComment::class);
    }

    public function save(ProductComment $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ProductComment $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function clear(): void
    {
        $this->getEntityManager()->clear();
    }

    /**
     * @param array<ProductComment> $entities
     */
    public function saveAll(array $entities, bool $flush = true): void
    {
        foreach ($entities as $entity) {
            $this->getEntityManager()->persist($entity);
        }

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
