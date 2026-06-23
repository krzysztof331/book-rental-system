<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Book;
use DateTimeInterface;

final class BookResponse
{
    public function __construct(
        public int $id,
        public string $serialNumber,
        public string $title,
        public string $author,
        public bool $borrowed,
        public ?string $borrowedAt,
        public ?string $borrowedByCardNumber,
    ) {
    }

    public static function fromBook(Book $book): self
    {
        return new self(
            (int) $book->getId(),
            $book->getSerialNumber(),
            $book->getTitle(),
            $book->getAuthor(),
            $book->isBorrowed(),
            $book->getBorrowedAt()?->format(DateTimeInterface::ATOM),
            $book->getBorrowedByCardNumber(),
        );
    }
}
