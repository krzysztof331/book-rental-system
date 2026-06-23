<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\BookStatus;
use App\Validator\CardNumberRequiredWhenBorrowing;
use Symfony\Component\Validator\Constraints as Assert;

#[CardNumberRequiredWhenBorrowing]
final class UpdateBookStatusRequest
{
    public function __construct(
        #[Assert\NotNull(message: 'The status is required.')]
        public ?BookStatus $status = null,

        #[Assert\Regex(pattern: '/^\d{6}$/', message: 'The card number must be a six-digit number.')]
        public ?string $cardNumber = null,
    ) {
    }
}
