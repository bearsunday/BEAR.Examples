<?php

declare(strict_types=1);

namespace MyVendor\Cms\Validation;

use BEAR\Resource\Exception\JsonSchemaException;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

use function dirname;
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
 * required-message path is verifiable.
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

        try {
            $this->dispatch(['title' => 'present']);
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

        try {
            $this->dispatch(['slug' => 'BAD-Slug']);
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

        try {
            $this->dispatch(['slug' => 'a']);
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

        try {
            $this->dispatch(['slug' => '123']);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            // Falls back to whatever justinrainbow emits — the assertion is
            // only that some message survives, not its exact wording.
            $this->assertNotEmpty($e->getErrors()['slug'][0]);
        }
    }

    /** @param array<string, mixed> $arguments */
    private function dispatch(array $arguments): void
    {
        $handler = new JsonSchemaRequestExceptionHandler();
        $ro = new class extends ResourceObject {
        };
        $handler->handleRequestException(
            $arguments,
            $ro,
            new JsonSchemaException('triggered'),
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
        // dirname() reference satisfies static analysis that the var is used
        // even if PHPUnit short-circuits before tearDown.
        self::assertSame(dirname($path), dirname($path));
    }
}
