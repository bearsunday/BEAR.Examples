<?php

declare(strict_types=1);

namespace MyVendor\Cms\Exception;

use BEAR\Resource\Exception\ExceptionInterface;
use RuntimeException;
use Throwable;

/**
 * Carries per-field validation errors as a structured map.
 *
 * Thrown by `JsonSchemaRequestExceptionHandler` after grouping
 * BEAR.Resource's structured request-schema errors. The Page layer catches
 * this and surfaces the errors to the form template; HAL+JSON callers receive
 * the same shape via `$errors` so the wire body stays consistent across
 * origins.
 *
 * @see docs/journal/validation-layer-design.md
 */
final class ValidationException extends RuntimeException implements ExceptionInterface
{
    /** @param array<string, list<string>> $errors field path => list of messages */
    public function __construct(
        public readonly array $errors,
        Throwable|null $previous = null,
    ) {
        parent::__construct('Validation failed', 0, $previous);
    }
}
