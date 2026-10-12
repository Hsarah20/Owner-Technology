<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Document;
use App\Enum\DocumentStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @extends ServiceEntityRepository<Document>
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private PaginatorInterface $paginator,
    ) {
        parent::__construct($registry, Document::class);
    }

    public function paginateForClient(Client $client, int $page = 1, int $limit = 10): PaginationInterface
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.client = :client')
            ->setParameter('client', $client)
            ->orderBy('d.createdAt', 'DESC');

        return $this->paginator->paginate($qb, $page, $limit);
    }

    /**
     * Liste paginée de tous les documents avec filtres.
     */
    public function paginateForAdmin(
        int $page = 1,
        int $limit = 20,
        ?DocumentStatus $status = null,
        ?Client $client = null,
    ): PaginationInterface {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.client', 'c')
            ->addSelect('c')
            ->orderBy('d.createdAt', 'DESC');

        if ($status !== null) {
            $qb->andWhere('d.status = :status')->setParameter('status', $status);
        }

        if ($client !== null) {
            $qb->andWhere('d.client = :client')->setParameter('client', $client);
        }

        return $this->paginator->paginate($qb, $page, $limit);
    }

    /**
     * Documents en échec, pour supervision rapide.
     *
     * @return Document[]
     */
    public function findFailed(int $limit = 10): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.status = :status')
            ->setParameter('status', DocumentStatus::FAILED)
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(DocumentStatus $status): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
}