<?php

declare(strict_types=1);

namespace MyVendor\Cms\Validation;

use BEAR\Resource\Exception\JsonSchemaException;
use BEAR\Resource\JsonSchema\JsonSchemaError;
use BEAR\Resource\JsonSchemaRequestExceptionHandlerInterface;
use BEAR\Resource\ResourceObject;
use JsonException;
use MyVendor\Cms\Exception\ValidationException;
use Override;
use stdClass;

use function explode;
use function file_get_contents;
use function is_object;
use function is_string;
use function json_decode;
use function property_exists;
use function strtok;

use const JSON_THROW_ON_ERROR;

/**
 * Surfaces JSON Schema request failures as a per-field `ValidationException`.
 *
 * `JsonSchemaInterceptor` calls this hook with the original
 * `JsonSchemaException`, which since BEAR.Resource#364 carries the
 * structured `list<JsonSchemaError>` produced by the validator. We walk
 * each error to look up `errorMessage.<constraint>` (ajv-errors convention)
 * on the corresponding property and pack a `field => list<string>` map.
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
        // Defence against exceptions raised outside the interceptor — manual
        // throws, future refactors, or any path that bypasses
        // `JsonSchemaErrorMapper`. Preserve the original failure rather than
        // surface an empty-shape ValidationException that swallows the signal.
        if ($errors === []) {
            throw $e;
        }

        $schema = $this->loadSchema($schemaFile);
        $collected = [];
        foreach ($errors as $error) {
            $field = $this->fieldFromError($error);
            $messages = $collected[$field] ?? [];
            $messages[] = $this->resolveMessage($schema, $error);
            $collected[$field] = $messages;
        }

        throw new ValidationException($collected, $e);
    }

    private function loadSchema(string $schemaFile): stdClass|null
    {
        $schemaJson = file_get_contents($schemaFile);
        if (! is_string($schemaJson)) {
            return null;
        }

        // Symmetry with the missing-file branch above — a corrupt schema
        // file falls back to the validator's default message rather than
        // crashing every form validation. The validator already passed
        // the schema once (we're inside the failure handler), so this
        // mostly catches a file mutated between interceptor and handler.
        try {
            $schema = json_decode($schemaJson, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return $schema instanceof stdClass ? $schema : null;
    }

    private function fieldFromError(JsonSchemaError $error): string
    {
        // For `required` failures, justinrainbow reports the parent object as
        // the property (empty string at root); the missing field name is in
        // `constraint.params.property`.
        if ($error->constraint->name === 'required') {
            $missing = $error->constraint->params['property'] ?? null;
            if (is_string($missing) && $missing !== '') {
                return $missing;
            }
        }

        return $error->property === '' ? '_root' : $error->property;
    }

    private function resolveMessage(stdClass|null $schema, JsonSchemaError $error): string
    {
        if ($schema === null) {
            return $error->message;
        }

        $field = $this->fieldFromError($error);
        $constraint = $error->constraint->name;

        if ($constraint === 'required') {
            // `errorMessage.required.<field>` at the object level — ajv-errors
            // assigns required-message overrides on the parent, not the
            // (absent) child property.
            $message = $this->lookupRequiredMessage($schema, $field);
            if ($message !== null) {
                return $message;
            }
        }

        $propertyNode = $this->propertyNode($schema, $field);
        if ($propertyNode !== null) {
            $message = $this->lookupPropertyMessage($propertyNode, $constraint);
            if ($message !== null) {
                return $message;
            }
        }

        return $error->message;
    }

    private function lookupRequiredMessage(stdClass $schema, string $field): string|null
    {
        if (! property_exists($schema, 'errorMessage') || ! is_object($schema->errorMessage)) {
            return null;
        }

        $errorMessage = $schema->errorMessage;
        if (! property_exists($errorMessage, 'required')) {
            return null;
        }

        $required = $errorMessage->required;
        if (is_string($required)) {
            return $required;
        }

        if (is_object($required) && property_exists($required, $field) && is_string($required->{$field})) {
            return $required->{$field};
        }

        return null;
    }

    private function lookupPropertyMessage(stdClass $propertyNode, string $constraint): string|null
    {
        if (! property_exists($propertyNode, 'errorMessage')) {
            return null;
        }

        $errorMessage = $propertyNode->errorMessage;
        // ajv-errors permits both shapes:
        //   "errorMessage": "any failure"   — fallback for any constraint
        //   "errorMessage": {"pattern": "...", "minLength": "..."}   — per-keyword
        if (is_string($errorMessage)) {
            return $errorMessage;
        }

        if (is_object($errorMessage) && property_exists($errorMessage, $constraint) && is_string($errorMessage->{$constraint})) {
            return $errorMessage->{$constraint};
        }

        return null;
    }

    /**
     * Walks the schema's `properties` to find the node for the given dot-path
     * field (`a.b` → `properties.a.properties.b`). Returns null when the
     * intermediate object is missing — we fall back to the default message.
     */
    private function propertyNode(stdClass $schema, string $field): stdClass|null
    {
        if ($field === '' || $field === '_root') {
            return $schema;
        }

        $segments = explode('.', strtok($field, '[') ?: $field);
        $node = $schema;
        foreach ($segments as $segment) {
            if (! property_exists($node, 'properties') || ! is_object($node->properties)) {
                return null;
            }

            $properties = $node->properties;
            if (! property_exists($properties, $segment)) {
                return null;
            }

            $next = $properties->{$segment};
            if (! is_object($next)) {
                return null;
            }

            $node = $next;
        }

        return $node;
    }
}
