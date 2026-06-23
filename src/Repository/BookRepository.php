<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    public function save(Book $book): void
    {
        $em = $this->getEntityManager();
        $em->persist($book);
        $em->flush();
    }

    public function remove(Book $book): void
    {
        $em = $this->getEntityManager();
        $em->remove($book);
        $em->flush();
    }
}
