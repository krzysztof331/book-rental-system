<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_CLASS)]
final class CardNumberRequiredWhenBorrowing extends Constraint
{
    public string $message = 'A card number is required to borrow a book.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
