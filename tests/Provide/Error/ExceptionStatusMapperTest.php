<?php

declare(strict_types=1);

namespace BEAR\Kata\Provide\Error;

use BEAR\Resource\Code;
use BEAR\Resource\Exception\JsonSchemaRequestException;
use BEAR\Resource\Exception\JsonSchemaResponseException;
use BEAR\Kata\Exception\ArticleNotFoundException;
use BEAR\Kata\Exception\AuthorNotFoundException;
use BEAR\Kata\Exception\CategoryNotFoundException;
use BEAR\Kata\Exception\ForbiddenException;
use BEAR\Kata\Exception\TagNotFoundException;
use BEAR\Kata\Exception\UnauthenticatedException;
use BEAR\Kata\Exception\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExceptionStatusMapperTest extends TestCase
{
    private ExceptionStatusMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ExceptionStatusMapper();
    }

    /** @return array<string, array{0: \Throwable, 1: int}> */
    public static function statusProvider(): array
    {
        return [
            'ValidationException → 400'          => [new ValidationException([]), Code::BAD_REQUEST],
            'JsonSchemaRequestException → 400'   => [new JsonSchemaRequestException('bad'), Code::BAD_REQUEST],
            'UnauthenticatedException → 401'     => [new UnauthenticatedException(), Code::UNAUTHORIZED],
            'ForbiddenException → 403'           => [new ForbiddenException(), Code::FORBIDDEN],
            'ArticleNotFoundException → 404'     => [new ArticleNotFoundException(), Code::NOT_FOUND],
            'AuthorNotFoundException → 404'      => [new AuthorNotFoundException(), Code::NOT_FOUND],
            'CategoryNotFoundException → 404'    => [new CategoryNotFoundException(), Code::NOT_FOUND],
            'TagNotFoundException → 404'         => [new TagNotFoundException(), Code::NOT_FOUND],
            'RuntimeException → null (delegate)' => [new RuntimeException(), null],
        ];
    }

    #[DataProvider('statusProvider')]
    public function testStatus(\Throwable $e, int|null $expected): void
    {
        $this->assertSame($expected, $this->mapper->status($e));
    }

    public function testJsonSchemaResponseExceptionIsUnmapped(): void
    {
        $e = new JsonSchemaResponseException('schema mismatch');
        $this->assertNull($this->mapper->status($e));
    }

    public function testMessageForValidationException(): void
    {
        $e = new ValidationException([]);
        $message = $this->mapper->message($e, Code::BAD_REQUEST);
        $this->assertSame('Validation failed.', $message);
    }

    public function testErrorsForValidationException(): void
    {
        $e = new ValidationException(['email' => ['Invalid email address.']]);
        $this->assertSame(['email' => ['Invalid email address.']], $this->mapper->errors($e));
    }

    public function testErrorsAreEmptyForNonValidationException(): void
    {
        $this->assertSame([], $this->mapper->errors(new RuntimeException()));
    }

    public function testStatusText(): void
    {
        $this->assertSame('Not Found', $this->mapper->statusText(Code::NOT_FOUND));
    }
}
