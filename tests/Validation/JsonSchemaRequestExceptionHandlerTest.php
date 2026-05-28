<?php

declare(strict_types=1);

namespace MyVendor\Cms\Validation;

use BEAR\Resource\Exception\JsonSchemaRequestException;
use BEAR\Resource\JsonSchema\ConstraintViolation;
use BEAR\Resource\JsonSchema\JsonSchemaError;
use BEAR\Resource\JsonSchema\JsonSchemaErrors;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Drives the request handler directly. BEAR.Resource now maps validator rows
 * into source-specific request/response exceptions before app code sees them;
 * this test pins only this codebase's responsibility: request errors are
 * grouped by field.
 */
final class JsonSchemaRequestExceptionHandlerTest extends TestCase
{
    public function testRequestErrorsAreGroupedByField(): void
    {
        $error = new JsonSchemaError(
            'slug',
            '/slug',
            'Slug must contain only lowercase letters, digits and hyphens.',
            new ConstraintViolation('pattern', ['pattern' => '^[a-z0-9][a-z0-9-]*$']),
            'Does not match the regex pattern ^[a-z0-9][a-z0-9-]*$',
        );

        try {
            $this->dispatch([$error]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(
                ['slug' => ['Slug must contain only lowercase letters, digits and hyphens.']],
                $e->getErrors(),
            );
        }
    }

    public function testMultipleRequestErrorsForSameFieldArePreserved(): void
    {
        $errors = [
            new JsonSchemaError(
                'title',
                '/title',
                'Title is required.',
                new ConstraintViolation('required', ['property' => 'title']),
            ),
            new JsonSchemaError(
                'title',
                '/title',
                'Title must be at least 1 character long.',
                new ConstraintViolation('minLength', ['minLength' => 1]),
            ),
        ];

        try {
            $this->dispatch($errors);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(
                ['Title is required.', 'Title must be at least 1 character long.'],
                $e->getErrors()['title'],
            );
        }
    }

    public function testRootRequestErrorUsesRootField(): void
    {
        $error = new JsonSchemaError(
            '',
            '',
            'Value must be an object.',
            new ConstraintViolation('type', ['type' => 'object']),
        );

        try {
            $this->dispatch([$error]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(['_root' => ['Value must be an object.']], $e->getErrors());
        }
    }

    public function testEmptyRequestErrorsRethrowsOriginalException(): void
    {
        $original = new JsonSchemaRequestException('rethrow-marker');
        $handler = new JsonSchemaRequestExceptionHandler();
        $ro = new class extends ResourceObject {
        };

        $this->expectException(JsonSchemaRequestException::class);
        $this->expectExceptionMessage('rethrow-marker');
        $handler->handleRequestException([], $ro, $original, '/tmp/request-schema.json');
    }

    /** @param list<JsonSchemaError> $errors */
    private function dispatch(array $errors): void
    {
        $handler = new JsonSchemaRequestExceptionHandler();
        $ro = new class extends ResourceObject {
        };
        $handler->handleRequestException(
            [],
            $ro,
            new JsonSchemaRequestException('triggered', 400, new JsonSchemaErrors($errors)),
            '/tmp/request-schema.json',
        );
    }
}
