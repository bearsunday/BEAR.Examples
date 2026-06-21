<?php

declare(strict_types=1);

namespace BEAR\Kata\Validation;

use BEAR\Resource\Exception\JsonSchemaException;
use BEAR\Resource\JsonSchemaRequestExceptionHandlerInterface;
use BEAR\Resource\ResourceObject;
use BEAR\Kata\Exception\ValidationException;
use Override;

/**
 * Surfaces JSON Schema request failures as a per-field `ValidationException`.
 *
 * `JsonSchemaInterceptor` calls this hook with the original
 * `JsonSchemaRequestException`, which since BEAR.Resource#364/#369 is
 * source-distinct from response schema failures and carries a structured
 * `JsonSchemaErrors` collection. BEAR.Resource resolves any schema-declared
 * `errorMessage` templates before invoking this handler; this class only
 * groups the already-rendered messages into a `field => list<string>` map.
 * Throwing — rather than mutating `$ro` — is the only way to short-circuit
 * `$invocation->proceed()`; the surrounding interceptor falls through to
 * `proceed()` if the handler returns normally.
 *
 * @see docs/journal/validation-layer-design.md
 */
final readonly class JsonSchemaRequestExceptionHandler implements JsonSchemaRequestExceptionHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    #[Override]
    public function handleRequestException(
        array $arguments,
        ResourceObject $ro,
        JsonSchemaException $e,
        string $schemaFile,
    ): never {
        $errors = $e->getErrors();
        // Defence against exceptions raised outside the normal interceptor
        // path. Preserve the original failure rather than surface an
        // empty-shape ValidationException that swallows the signal.
        if (! $errors->hasErrors()) {
            throw $e;
        }

        $collected = [];
        foreach ($errors as $error) {
            $field = $error->property === '' ? '_root' : $error->property;
            $messages = $collected[$field] ?? [];
            $messages[] = $error->message;
            $collected[$field] = $messages;
        }

        throw new ValidationException($collected, $e);
    }
}
