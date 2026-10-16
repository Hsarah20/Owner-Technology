<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Document;
use App\Enum\DocumentStatus;
use App\Enum\DocumentNature;
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

    public function paginateForClient(
        Client $client, 
        int $page = 1, 
        int $limit = 10,
        ?int $year = null,
        ?int $month = null,
        ?DocumentNature $nature = null,
        ): PaginationInterface
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.client = :client')
            ->setParameter('client', $client)
            ->orderBy('d.createdAt', 'DESC');
        
        if ($year !== null) {
            $qb->andWhere('d.periodYear = :year')->setParameter('year', $year);
        }

        if ($month !== null) {
            $qb->andWhere('d.periodMonth = :month')->setParameter('month', $month);
        }

        if ($nature !== null) {
            $qb->andWhere('d.nature = :nature')
            ->setParameter('nature', $nature);
        }


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

    public function findAvailableYears(Client $client): array
    {
        return $this->createQueryBuilder('d')
            ->select('DISTINCT d.periodYear AS year')
            ->andWhere('d.client = :client')
            ->andWhere('d.periodYear IS NOT NULL')
            ->setParameter('client', $client)
            ->orderBy('year', 'DESC')
            ->getQuery()
            ->getSingleColumnResult();
    }
}