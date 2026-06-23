<?php

declare(strict_types=1);

namespace App\Validator;

use App\Dto\UpdateBookStatusRequest;
use App\Enum\BookStatus;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class CardNumberRequiredWhenBorrowingValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CardNumberRequiredWhenBorrowing) {
            throw new UnexpectedValueException($constraint, CardNumberRequiredWhenBorrowing::class);
        }

        if (!$value instanceof UpdateBookStatusRequest) {
            return;
        }

        if (BookStatus::Borrowed !== $value->status) {
            return;
        }

        if (null === $value->cardNumber || '' === $value->cardNumber) {
            $this->context->buildViolation($constraint->message)
                ->atPath('cardNumber')
                ->addViolation();
        }
    }
}
