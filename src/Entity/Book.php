<?php

declare(strict_types=1);

namespace App\Entity;

use App\Exception\BookAlreadyBorrowedException;
use App\Repository\BookRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\Table(name: 'books')]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 6, unique: true)]
    private string $serialNumber;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 255)]
    private string $author;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $borrowedAt = null;

    #[ORM\Column(length: 6, nullable: true)]
    private ?string $borrowedByCardNumber = null;

    public function __construct(string $serialNumber, string $title, string $author)
    {
        $this->serialNumber = $serialNumber;
        $this->title = $title;
        $this->author = $author;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSerialNumber(): string
    {
        return $this->serialNumber;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function isBorrowed(): bool
    {
        return null !== $this->borrowedByCardNumber;
    }

    public function getBorrowedAt(): ?DateTimeImmutable
    {
        return $this->borrowedAt;
    }

    public function getBorrowedByCardNumber(): ?string
    {
        return $this->borrowedByCardNumber;
    }

    public function borrow(string $cardNumber, DateTimeImmutable $borrowedAt): void
    {
        if ($this->isBorrowed()) {
            throw new BookAlreadyBorrowedException('The book is already borrowed.');
        }

        $this->borrowedByCardNumber = $cardNumber;
        $this->borrowedAt = $borrowedAt;
    }

    public function markAvailable(): void
    {
        $this->borrowedByCardNumber = null;
        $this->borrowedAt = null;
    }
}
