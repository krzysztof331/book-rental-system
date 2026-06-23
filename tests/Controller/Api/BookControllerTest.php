<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\HttpFoundation\Response;

final class BookControllerTest extends WebTestCase
{
    use ClockSensitiveTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testListIsEmptyByDefault(): void
    {
        $this->request('GET', '/api/books');

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->json());
    }

    public function testCreateBook(): void
    {
        $book = $this->createBook('000123', 'Lalka', 'Bolesław Prus');

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertSame('000123', $book['serialNumber']);
        self::assertSame('Lalka', $book['title']);
        self::assertSame('Bolesław Prus', $book['author']);
        self::assertFalse($book['borrowed']);
        self::assertNull($book['borrowedAt']);
        self::assertNull($book['borrowedByCardNumber']);
        self::assertArrayHasKey('id', $book);

        $this->request('GET', '/api/books');

        self::assertCount(1, $this->json());
    }

    public function testCreateWithDuplicateSerialNumberReturnsConflict(): void
    {
        $this->createBook('111111', 'A', 'Author');
        $this->createBook('111111', 'B', 'Author');

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('invalidPayloads')]
    public function testCreateWithInvalidPayloadReturnsValidationError(array $payload): void
    {
        $this->request('POST', '/api/books', $payload);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertArrayHasKey('violations', $this->json());
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidPayloads(): iterable
    {
        yield 'serial too short' => [['serialNumber' => '12', 'title' => 'T', 'author' => 'A']];
        yield 'serial not numeric' => [['serialNumber' => 'abcdef', 'title' => 'T', 'author' => 'A']];
        yield 'missing title' => [['serialNumber' => '123456', 'title' => '', 'author' => 'A']];
        yield 'missing author' => [['serialNumber' => '123456', 'title' => 'T', 'author' => '']];
    }

    public function testBorrowAndReturnBook(): void
    {
        $id = $this->createBook('222222', 'Quo Vadis', 'Henryk Sienkiewicz')['id'];

        $this->request('PATCH', '/api/books/'.$id, ['status' => 'borrowed', 'cardNumber' => '654321']);
        self::assertResponseIsSuccessful();
        $borrowed = $this->json();
        self::assertTrue($borrowed['borrowed']);
        self::assertSame('654321', $borrowed['borrowedByCardNumber']);
        self::assertNotNull($borrowed['borrowedAt']);

        $this->request('PATCH', '/api/books/'.$id, ['status' => 'available']);
        self::assertResponseIsSuccessful();
        $returned = $this->json();
        self::assertFalse($returned['borrowed']);
        self::assertNull($returned['borrowedByCardNumber']);
        self::assertNull($returned['borrowedAt']);
    }

    public function testBorrowRecordsTheCurrentTime(): void
    {
        $borrowedAt = new DateTimeImmutable('-1 day');
        static::mockTime($borrowedAt);

        $id = $this->createBook('777777', 'Solaris', 'Stanisław Lem')['id'];

        $this->request('PATCH', '/api/books/'.$id, ['status' => 'borrowed', 'cardNumber' => '654321']);

        self::assertResponseIsSuccessful();
        self::assertSame($borrowedAt->format(DateTimeInterface::ATOM), $this->json()['borrowedAt']);
    }

    public function testBorrowingAnAlreadyBorrowedBookReturnsConflict(): void
    {
        $id = $this->createBook('333333', 'Ferdydurke', 'Witold Gombrowicz')['id'];

        $this->request('PATCH', '/api/books/'.$id, ['status' => 'borrowed', 'cardNumber' => '654321']);
        self::assertResponseIsSuccessful();

        $this->request('PATCH', '/api/books/'.$id, ['status' => 'borrowed', 'cardNumber' => '111111']);
        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testBorrowingWithoutCardNumberReturnsValidationError(): void
    {
        $id = $this->createBook('444444', 'Solaris', 'Stanisław Lem')['id'];

        $this->request('PATCH', '/api/books/'.$id, ['status' => 'borrowed']);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSame('cardNumber', $this->json()['violations'][0]['propertyPath']);
    }

    public function testUpdatingAnUnknownBookReturnsNotFound(): void
    {
        $this->request('PATCH', '/api/books/999999', ['status' => 'available']);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertArrayHasKey('title', $this->json());
    }

    public function testDeleteBook(): void
    {
        $id = $this->createBook('555555', 'Dziady', 'Adam Mickiewicz')['id'];

        $this->request('DELETE', '/api/books/'.$id);
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->request('DELETE', '/api/books/'.$id);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @return array<string, mixed>
     */
    private function createBook(string $serialNumber, string $title, string $author): array
    {
        $this->request('POST', '/api/books', [
            'serialNumber' => $serialNumber,
            'title' => $title,
            'author' => $author,
        ]);

        return $this->json();
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function request(string $method, string $uri, ?array $payload = null): void
    {
        $server = ['HTTP_ACCEPT' => 'application/json'];
        if (null !== $payload) {
            $server['CONTENT_TYPE'] = 'application/json';
        }

        $this->client->request(
            $method,
            $uri,
            server: $server,
            content: null !== $payload ? json_encode($payload, \JSON_THROW_ON_ERROR) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function json(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }
}
