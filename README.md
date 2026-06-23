# Book rental system

A small REST API for tracking a library's books and who has borrowed them. It
runs on an internal staff network, so there's no authentication.

Built with Symfony running on FrankenPHP, with PostgreSQL for storage.

## Running it

You need Docker Compose (v2.10+). From the project root:

```console
docker compose up -d
```

Migrations run on startup, so the API is ready once the containers are healthy.
It's served over HTTPS at `https://localhost` with a self-signed certificate, so
use `curl -k` (or click through the browser warning).

### Sample data

Startup seeds a few sample books, some available and some borrowed. It's
idempotent, so you can re-run it by hand:

```console
docker compose exec php bin/console app:books:seed
```

## API

There's interactive documentation (Swagger UI) at `https://localhost/api/doc`, and
the raw OpenAPI spec at `https://localhost/api/doc.json`. Everything below lives
under `/api/books`. Send `Accept: application/json` so validation errors come
back as JSON rather than HTML.

| Method   | Path               | Description            |
|----------|--------------------|------------------------|
| `GET`    | `/api/books`       | List all books         |
| `POST`   | `/api/books`       | Add a book             |
| `PATCH`  | `/api/books/{id}`  | Borrow or return a book|
| `DELETE` | `/api/books/{id}`  | Remove a book          |

A book is represented like this:

```json
{
  "id": 1,
  "serialNumber": "000123",
  "title": "Lalka",
  "author": "Bolesław Prus",
  "borrowed": false,
  "borrowedAt": null,
  "borrowedByCardNumber": null
}
```

The serial number is a six-digit string (leading zeros matter, so it's stored as
text rather than an integer). Library card numbers are six digits as well.

### Examples

Add a book:

```console
curl -k https://localhost/api/books \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"serialNumber": "000123", "title": "Lalka", "author": "Bolesław Prus"}'
```

Borrow it (a card number is required when borrowing):

```console
curl -k -X PATCH https://localhost/api/books/1 \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"status": "borrowed", "cardNumber": "654321"}'
```

Returning is the same request with `{"status": "available"}`.

The rules the API enforces:

* Serial numbers are unique; reusing one returns `409 Conflict`.
* Borrowing needs a card number, and you can't borrow a book that's already out
  (also `409`).
* Setting the status is idempotent: returning a book that's already available is
  a no-op, and `"status": "available"` ignores any `cardNumber` in the payload.
* Bad input (wrong serial format, missing fields, malformed card number)
  returns `422 Unprocessable Entity`.

## Tests

The suite is mostly functional tests that hit the endpoints against a real
database, plus a few unit tests on the `Book` entity's borrow/return logic.
Each test runs inside a transaction that's rolled back afterwards (via
DAMADoctrineTestBundle), so they leave no data behind.

The test bootstrap creates the test database and schema automatically, so
there's nothing to set up by hand. Run:

```console
docker compose exec php bin/phpunit
```

## Notes

The project targets PHP 8.4 rather than 8.5 to stay compatible with the hosting
environment it's deployed to.

The Docker setup is based on [dunglas/symfony-docker](https://github.com/dunglas/symfony-docker),
whose documentation covers the more advanced options it ships with (Xdebug,
production builds, TLS, and so on).
