<?php

declare(strict_types=1);

namespace MyVendor\Cms\Validation;

use BEAR\Resource\Exception\JsonSchemaException;
use BEAR\Resource\JsonSchemaRequestExceptionHandlerInterface;
use BEAR\Resource\ResourceObject;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use MyVendor\Cms\Exception\ValidationException;
use Override;
use stdClass;

use function assert;
use function explode;
use function file_get_contents;
use function is_object;
use function is_string;
use function json_decode;
use function property_exists;
use function strtok;

use const JSON_THROW_ON_ERROR;

/**
 * Re-runs the validator to surface field-level errors with `errorMessage`
 * (ajv-errors convention) applied, then raises a `ValidationException`.
 *
 * JsonSchemaInterceptor calls this hook with the original `JsonSchemaException`
 * (which only carries a flattened "[prop] msg; …" string). We re-validate against
 * the same schema to recover the structured error list, walk each error to look
 * up `errorMessage.<constraint>` on the corresponding property, and pack a
 * `field => list<string>` map. Throwing — rather than mutating `$ro` — is the
 * only way to short-circuit `$invocation->proceed()`; the surrounding
 * interceptor falls through to `proceed()` if the handler returns normally.
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
        $errors = $this->collectErrors($arguments, $schemaFile);

        throw new ValidationException($errors, $e);
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array<string, list<string>>
     */
    private function collectErrors(array $arguments, string $schemaFile): array
    {
        $schemaJson = file_get_contents($schemaFile);
        $schema = is_string($schemaJson)
            ? json_decode($schemaJson, false, 512, JSON_THROW_ON_ERROR)
            : null;
        assert($schema instanceof stdClass || $schema === null);

        $validator = new Validator();
        // Match the interceptor's validate(): use the file:// $ref so external
        // schemas resolve identically when collecting errors.
        $rootSchema = (object) ['$ref' => 'file://' . $schemaFile];
        $target = $arguments;
        $validator->validate($target, $rootSchema, Constraint::CHECK_MODE_TYPE_CAST);
        /** @var list<array{property: string, message: string, constraint: array{name: string, params: array<string, mixed>}}> $rawErrors */
        $rawErrors = $validator->getErrors();

        $collected = [];
        foreach ($rawErrors as $error) {
            $field = $this->fieldFromError($error);
            $messages = $collected[$field] ?? [];
            $messages[] = $this->resolveMessage($schema, $error);
            $collected[$field] = $messages;
        }

        return $collected;
    }

    /** @param array{property: string, constraint: array{name: string, params: array<string, mixed>}} $error */
    private function fieldFromError(array $error): string
    {
        // For `required` failures, justinrainbow reports the parent object as
        // the property (empty string at root); the missing field name is in
        // `constraint.params.property`.
        if ($error['constraint']['name'] === 'required') {
            $missing = $error['constraint']['params']['property'] ?? null;
            if (is_string($missing) && $missing !== '') {
                return $missing;
            }
        }

        return $error['property'] === '' ? '_root' : $error['property'];
    }

    /** @param array{property: string, message: string, constraint: array{name: string, params: array<string, mixed>}} $error */
    private function resolveMessage(stdClass|null $schema, array $error): string
    {
        if ($schema === null) {
            return $error['message'];
        }

        $field = $this->fieldFromError($error);
        $constraint = $error['constraint']['name'];

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

        return $error['message'];
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
