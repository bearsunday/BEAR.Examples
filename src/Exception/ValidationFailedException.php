<?php

declare(strict_types=1);

namespace MyVendor\Cms\Exception;

use BEAR\Resource\Exception\BadRequestException;
use MyVendor\Cms\Validation\ValidationErrors;

final class ValidationFailedException extends BadRequestException
{
    /** @var array<string, list<string>> */
    private readonly array $errors;

    public function __construct(
        ValidationErrors $errors,
        string $message = 'Validation failed',
    ) {
        $this->errors = $errors->toArray();

        parent::__construct($message, 422);
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }
}
