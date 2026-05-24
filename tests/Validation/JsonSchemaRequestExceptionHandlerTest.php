<?php

declare(strict_types=1);

namespace MyVendor\Cms\Validation;

use BEAR\Resource\Exception\JsonSchemaException;
use BEAR\Resource\JsonSchema\ConstraintViolation;
use BEAR\Resource\JsonSchema\JsonSchemaError;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Drives the handler directly so the `required` / `errorMessage` mapping path
 * is exercised independently of `JsonSchemaInterceptor`. Through the App
 * resource boundary, schema-required failures cannot reach the interceptor
 * (Article's DTO and Author's typed parameters both reject missing values
 * before the validator sees them), so this is the only place the
 * required-message path is verifiable in isolation. End-to-end wiring is
 * covered by `tests/Resource/App/ArticleTest.php`.
 *
 * DTOs are hand-constructed here to keep the unit test hermetic from
 * justinrainbow's validator. The DTO shapes mirror what the upstream
 * `JsonSchemaErrorMapper` produces from the validator's raw error rows.
 */
final class JsonSchemaRequestExceptionHandlerTest extends TestCase
{
    private string $schemaFile = '';

    protected function tearDown(): void
    {
        if ($this->schemaFile === '') {
            return;
        }

        unlink($this->schemaFile);
        $this->schemaFile = '';
    }

    public function testMissingRequiredFieldResolvesErrorMessage(): void
    {
        $this->writeSchema(<<<'JSON'
{
  "type": "object",
  "required": ["slug", "title"],
  "errorMessage": {
    "required": {
      "slug": "Slug is required.",
      "title": "Title is required."
    }
  },
  "properties": {
    "slug":  {"type": "string"},
    "title": {"type": "string"}
  }
}
JSON);

        // justinrainbow reports `required` failures with the parent object
        // as `property` (empty at root) and the missing field name in
        // `constraint.params.property`.
        $error = new JsonSchemaError(
            '',
            '',
            'The property slug is required',
            new ConstraintViolation('required', ['property' => 'slug']),
        );

        try {
            $this->dispatch([$error]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('slug', $errors);
            $this->assertSame('Slug is required.', $errors['slug'][0]);
        }
    }

    public function testPerKeywordErrorMessageIsApplied(): void
    {
        $this->writeSchema(<<<'JSON'
{
  "type": "object",
  "properties": {
    "slug": {
      "type": "string",
      "pattern": "^[a-z]+$",
      "errorMessage": {
        "pattern": "Slug must be lowercase letters only."
      }
    }
  }
}
JSON);

        $error = new JsonSchemaError(
            'slug',
            '/slug',
            'Does not match the regex pattern ^[a-z]+$',
            new ConstraintViolation('pattern', ['pattern' => '^[a-z]+$']),
        );

        try {
            $this->dispatch([$error]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertSame('Slug must be lowercase letters only.', $errors['slug'][0]);
        }
    }

    public function testStringErrorMessageActsAsFallback(): void
    {
        // ajv-errors permits `errorMessage` as a plain string — used for any
        // failure on this property. This pins the fallback path that the
        // property-level lookup leans on when no per-keyword key matches.
        $this->writeSchema(<<<'JSON'
{
  "type": "object",
  "properties": {
    "slug": {
      "type": "string",
      "minLength": 3,
      "errorMessage": "Slug is invalid."
    }
  }
}
JSON);

        $error = new JsonSchemaError(
            'slug',
            '/slug',
            'Must be at least 3 characters long',
            new ConstraintViolation('minLength', ['minLength' => 3]),
        );

        try {
            $this->dispatch([$error]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame('Slug is invalid.', $e->getErrors()['slug'][0]);
        }
    }

    public function testValidatorDefaultMessageIsUsedWhenErrorMessageMissing(): void
    {
        $this->writeSchema(<<<'JSON'
{
  "type": "object",
  "properties": {
    "slug": {"type": "string", "pattern": "^[a-z]+$"}
  }
}
JSON);

        $error = new JsonSchemaError(
            'slug',
            '/slug',
            'Does not match the regex pattern ^[a-z]+$',
            new ConstraintViolation('pattern', ['pattern' => '^[a-z]+$']),
        );

        try {
            $this->dispatch([$error]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            // Falls back to whatever the upstream emits — the assertion is
            // only that some message survives, not its exact wording.
            $this->assertNotEmpty($e->getErrors()['slug'][0]);
        }
    }

    public function testEmptyErrorsRethrowsOriginalException(): void
    {
        // Defence: a JsonSchemaException carrying no structured errors
        // (manual throws, future paths bypassing the upstream mapper) is
        // surfaced as-is rather than packed into an empty ValidationException.
        $this->writeSchema(<<<'JSON'
{"type": "object"}
JSON);

        $original = new JsonSchemaException('rethrow-marker');
        $handler = new JsonSchemaRequestExceptionHandler();
        $ro = new class extends ResourceObject {
        };

        $this->expectException(JsonSchemaException::class);
        $this->expectExceptionMessage('rethrow-marker');
        $handler->handleRequestException([], $ro, $original, $this->schemaFile);
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
            new JsonSchemaException('triggered', 0, $errors),
            $this->schemaFile,
        );
    }

    private function writeSchema(string $json): void
    {
        $path = tempnam(sys_get_temp_dir(), 'cms-schema-');
        self::assertNotFalse($path);
        $written = file_put_contents($path, $json);
        self::assertNotFalse($written);
        $this->schemaFile = $path;
    }
}
