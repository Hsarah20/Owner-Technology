<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Document;
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
}