<?php

declare(strict_types=1);

namespace BEAR\Examples\Provide\Error;

use BEAR\Resource\Code;
use BEAR\Resource\Exception\JsonSchemaRequestException;
use BEAR\Resource\Exception\JsonSchemaResponseException;
use BEAR\Examples\Exception\ArticleNotFoundException;
use BEAR\Examples\Exception\AuthorNotFoundException;
use BEAR\Examples\Exception\CategoryNotFoundException;
use BEAR\Examples\Exception\ForbiddenException;
use BEAR\Examples\Exception\TagNotFoundException;
use BEAR\Examples\Exception\UnauthenticatedException;
use BEAR\Examples\Exception\ValidationException;
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

    public function testStatusText(): void
    {
        $this->assertSame('Not Found', $this->mapper->statusText(Code::NOT_FOUND));
    }
}
