<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\BookResponse;
use App\Dto\CreateBookRequest;
use App\Dto\UpdateBookStatusRequest;
use App\Entity\Book;
use App\Enum\BookStatus;
use App\Exception\BookAlreadyBorrowedException;
use App\Repository\BookRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/books')]
#[OA\Tag(name: 'Books')]
final class BookController
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('', name: 'api_books_list', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'The list of all books.',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: BookResponse::class)),
        ),
    )]
    public function list(): JsonResponse
    {
        $data = array_map(
            BookResponse::fromBook(...),
            $this->books->findBy([], ['id' => 'ASC']),
        );

        return new JsonResponse($data);
    }

    #[Route('', name: 'api_books_create', methods: ['POST'])]
    #[OA\Response(
        response: 201,
        description: 'The book was created.',
        content: new OA\JsonContent(ref: new Model(type: BookResponse::class)),
    )]
    #[OA\Response(response: 409, description: 'A book with this serial number already exists.')]
    #[OA\Response(response: 422, description: 'The payload failed validation.')]
    public function create(#[MapRequestPayload] CreateBookRequest $request): JsonResponse
    {
        $book = new Book($request->serialNumber, $request->title, $request->author);

        try {
            $this->books->save($book);
        } catch (UniqueConstraintViolationException) {
            throw new ConflictHttpException('A book with this serial number already exists.');
        }

        return new JsonResponse(BookResponse::fromBook($book), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_books_delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'The book was deleted.')]
    #[OA\Response(response: 404, description: 'No book with the given id.')]
    public function delete(int $id): JsonResponse
    {
        $book = $this->books->find($id);
        if (null === $book) {
            throw $this->notFound($id);
        }

        $this->books->remove($book);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}', name: 'api_books_update_status', methods: ['PATCH'])]
    #[OA\Response(
        response: 200,
        description: 'The updated book.',
        content: new OA\JsonContent(ref: new Model(type: BookResponse::class)),
    )]
    #[OA\Response(response: 404, description: 'No book with the given id.')]
    #[OA\Response(response: 409, description: 'The book is already borrowed.')]
    #[OA\Response(response: 422, description: 'The payload failed validation.')]
    public function updateStatus(
        int $id,
        #[MapRequestPayload] UpdateBookStatusRequest $request,
    ): JsonResponse {
        $book = $this->books->find($id);
        if (null === $book) {
            throw $this->notFound($id);
        }

        if (BookStatus::Borrowed === $request->status) {
            try {
                $book->borrow((string) $request->cardNumber, $this->clock->now());
            } catch (BookAlreadyBorrowedException $e) {
                throw new ConflictHttpException($e->getMessage(), $e);
            }
        } else {
            $book->markAvailable();
        }

        $this->books->save($book);

        return new JsonResponse(BookResponse::fromBook($book));
    }

    private function notFound(int $id): NotFoundHttpException
    {
        return new NotFoundHttpException(sprintf('Book "%d" was not found.', $id));
    }
}
