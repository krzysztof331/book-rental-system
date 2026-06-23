<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Book;
use App\Exception\BookAlreadyBorrowedException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BookTest extends TestCase
{
    public function testNewBookIsAvailable(): void
    {
        $book = new Book('000123', 'Lalka', 'Bolesław Prus');

        self::assertFalse($book->isBorrowed());
        self::assertNull($book->getBorrowedAt());
        self::assertNull($book->getBorrowedByCardNumber());
    }

    public function testBorrowRecordsCardNumberAndDate(): void
    {
        $book = new Book('000123', 'Lalka', 'Bolesław Prus');
        $borrowedAt = new DateTimeImmutable('-1 day');

        $book->borrow('654321', $borrowedAt);

        self::assertTrue($book->isBorrowed());
        self::assertSame('654321', $book->getBorrowedByCardNumber());
        self::assertSame($borrowedAt, $book->getBorrowedAt());
    }

    public function testBorrowingAnAlreadyBorrowedBookThrows(): void
    {
        $book = new Book('000123', 'Lalka', 'Bolesław Prus');
        $book->borrow('654321', new DateTimeImmutable());

        $this->expectException(BookAlreadyBorrowedException::class);

        $book->borrow('111111', new DateTimeImmutable());
    }

    public function testMarkAvailableClearsBorrowState(): void
    {
        $book = new Book('000123', 'Lalka', 'Bolesław Prus');
        $book->borrow('654321', new DateTimeImmutable());

        $book->markAvailable();

        self::assertFalse($book->isBorrowed());
        self::assertNull($book->getBorrowedAt());
        self::assertNull($book->getBorrowedByCardNumber());
    }

    public function testMarkAvailableCanBeReborrowed(): void
    {
        $book = new Book('000123', 'Lalka', 'Bolesław Prus');
        $book->borrow('654321', new DateTimeImmutable());
        $book->markAvailable();

        $book->borrow('111111', new DateTimeImmutable());

        self::assertTrue($book->isBorrowed());
        self::assertSame('111111', $book->getBorrowedByCardNumber());
    }
}
