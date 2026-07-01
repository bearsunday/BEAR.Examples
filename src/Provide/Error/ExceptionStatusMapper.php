<?php

declare(strict_types=1);

namespace BEAR\Kata\Provide\Error;

use BEAR\Resource\Code;
use BEAR\Resource\Exception\BadRequestException;
use BEAR\Resource\Exception\JsonSchemaRequestException;
use BEAR\Kata\Exception\ArticleNotFoundException;
use BEAR\Kata\Exception\AuthorNotFoundException;
use BEAR\Kata\Exception\CategoryNotFoundException;
use BEAR\Kata\Exception\ForbiddenException;
use BEAR\Kata\Exception\TagNotFoundException;
use BEAR\Kata\Exception\UnauthenticatedException;
use BEAR\Kata\Exception\ValidationException;
use Throwable;

use function preg_replace;

/**
 * Maps domain throwables to an HTTP status code and a user-facing message.
 *
 * Shared by AppThrowableHandler (JSON) and HtmlThrowableHandler so both
 * contexts agree on the exact status and message — only the representation
 * (JSON body vs HTML page) differs between the two handlers.
 */
final class ExceptionStatusMapper
{
    /** Mapped HTTP status for a throwable, or null when it is unexpected (→ 500). */
    public function status(Throwable $e): int|null
    {
        if ($e instanceof ValidationException || $e instanceof JsonSchemaRequestException) {
            return Code::BAD_REQUEST;
        }

        if ($e instanceof UnauthenticatedException) {
            return Code::UNAUTHORIZED;
        }

        if ($e instanceof ForbiddenException) {
            return Code::FORBIDDEN;
        }

        if ($e instanceof ArticleNotFoundException
            || $e instanceof AuthorNotFoundException
            || $e instanceof CategoryNotFoundException
            || $e instanceof TagNotFoundException
        ) {
            return Code::NOT_FOUND;
        }

        if ($e instanceof BadRequestException) {
            $code = $e->getCode();

            return $code >= 400 && $code < 600 ? $code : Code::BAD_REQUEST;
        }

        return null;
    }

    /** User-facing message for a throwable already mapped to $status. */
    public function message(Throwable $e, int $status): string
    {
        if ($e instanceof ValidationException) {
            return 'Validation failed.';
        }

        if ($e instanceof JsonSchemaRequestException) {
            $message = preg_replace('/; by .+$/', '', $e->getMessage());

            return 'Invalid input. ' . ($message ?: 'Request parameters do not match the schema.');
        }

        if ($e->getMessage() !== '') {
            return $e->getMessage();
        }

        return $this->statusText($status);
    }

    /**
     * Structured per-field validation errors for a throwable, or `[]` when it
     * carries none — so a handler can surface a `{ "errors": {...} }` body / list.
     *
     * @return array<string, list<string>> field path => messages
     */
    public function errors(Throwable $e): array
    {
        return $e instanceof ValidationException ? $e->errors : [];
    }

    /** Reason phrase for an HTTP status (e.g. 404 → "Not Found"). */
    public function statusText(int $status): string
    {
        return (new Code())->statusText[$status] ?? 'Error';
    }
}
