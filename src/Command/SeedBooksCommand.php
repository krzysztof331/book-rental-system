<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Book;
use App\Repository\BookRepository;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:books:seed',
    description: 'Seed the catalogue with sample books. Idempotent: skips serial numbers that already exist.',
)]
final class SeedBooksCommand extends Command
{
    private const AVAILABLE = [
        ['000123', 'Lalka', 'Bolesław Prus'],
        ['000124', 'Quo Vadis', 'Henryk Sienkiewicz'],
        ['000125', 'Solaris', 'Stanisław Lem'],
        ['000126', 'Dziady', 'Adam Mickiewicz'],
    ];

    private const BORROWED = [
        ['000200', 'Ferdydurke', 'Witold Gombrowicz', '654321', '-4 days'],
        ['000201', 'Pan Tadeusz', 'Adam Mickiewicz', '111111', '-1 day'],
    ];

    public function __construct(
        private readonly BookRepository $books,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $created = 0;

        foreach (self::AVAILABLE as [$serialNumber, $title, $author]) {
            if ($this->exists($serialNumber)) {
                continue;
            }
            $this->books->save(new Book($serialNumber, $title, $author));
            ++$created;
        }

        foreach (self::BORROWED as [$serialNumber, $title, $author, $cardNumber, $borrowedAgo]) {
            if ($this->exists($serialNumber)) {
                continue;
            }
            $book = new Book($serialNumber, $title, $author);
            $book->borrow($cardNumber, $this->clock->now()->modify($borrowedAgo));
            $this->books->save($book);
            ++$created;
        }

        $io->success(sprintf('Seeded %d book(s).', $created));

        return Command::SUCCESS;
    }

    private function exists(string $serialNumber): bool
    {
        return null !== $this->books->findOneBy(['serialNumber' => $serialNumber]);
    }
}
