<?php

declare(strict_types=1);

namespace MyVendor\Cms\Exception;

use BEAR\Resource\Exception\ExceptionInterface;
use RuntimeException;
use Throwable;

use function implode;
use function sprintf;

/**
 * Carries per-field validation errors as a structured map.
 *
 * Thrown by `JsonSchemaRequestExceptionHandler` after grouping
 * BEAR.Resource's structured request-schema errors. The Page layer catches
 * this and surfaces the errors to the form template; HAL+JSON callers receive
 * the same shape via `getErrors()` so the wire body stays consistent across
 * origins.
 *
 * @see docs/journal/validation-layer-design.md
 */
final class ValidationException extends RuntimeException implements ExceptionInterface
{
    /** @param array<string, list<string>> $errors field path => list of messages */
    public function __construct(
        private readonly array $errors,
        Throwable|null $previous = null,
    ) {
        parent::__construct(self::summary($errors), 0, $previous);
    }

    /** @return array<string, list<string>> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /** @param array<string, list<string>> $errors */
    private static function summary(array $errors): string
    {
        $parts = [];
        foreach ($errors as $field => $messages) {
            $parts[] = sprintf('%s: %s', $field, implode(', ', $messages));
        }

        return $parts === [] ? 'Validation failed' : implode('; ', $parts);
    }
}
