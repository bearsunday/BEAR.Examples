<?php

declare(strict_types=1);

namespace BEAR\Kata\Provide\Error;

use BEAR\Kata\Exception\ValidationException;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\Sunday\Extension\Error\ErrorInterface;
use BEAR\Sunday\Extension\Error\ThrowableHandlerInterface;
use BEAR\Sunday\Extension\Router\RouterMatch as Request;
use BEAR\Sunday\Extension\Transfer\TransferInterface;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Integration tests: ValidationException → handler → response body.
 *
 * Verifies that the errors field map reaches the response in both
 * the JSON (AppThrowableHandler) and HTML (HtmlThrowableHandler) paths.
 */
final class ThrowableHandlerTest extends TestCase
{
    public function testAppThrowableHandlerSurfacesErrorsInJsonBody(): void
    {
        $transfer = new FakeTransfer();
        $handler = new AppThrowableHandler($transfer, new FakeErrorInterface(), new ExceptionStatusMapper());

        $errors = ['email' => ['Invalid email address.']];
        $handler->handle(new ValidationException($errors), new Request());

        $handler->transfer();

        $this->assertNotNull($transfer->captured);
        $this->assertSame(Code::BAD_REQUEST, $transfer->captured->code);
        $this->assertSame('Validation failed.', $transfer->captured->body['message']);
        $this->assertSame($errors, $transfer->captured->body['errors']);
    }

    public function testAppThrowableHandlerOmitsErrorsWhenEmpty(): void
    {
        $transfer = new FakeTransfer();
        $handler = new AppThrowableHandler($transfer, new FakeErrorInterface(), new ExceptionStatusMapper());

        $handler->handle(new ValidationException([]), new Request());
        $handler->transfer();

        $this->assertNotNull($transfer->captured);
        $this->assertArrayNotHasKey('errors', $transfer->captured->body);
    }

    public function testHtmlThrowableHandlerSurfacesErrorsInHtml(): void
    {
        $transfer = new FakeTransfer();
        $handler = new HtmlThrowableHandler($transfer, new FakeErrorInterface(), new ExceptionStatusMapper());

        $handler->handle(
            new ValidationException(['email' => ['Invalid email address.']]),
            new Request(),
        );
        $handler->transfer();

        $this->assertNotNull($transfer->captured);
        $this->assertSame(Code::BAD_REQUEST, $transfer->captured->code);
        $view = (string) $transfer->captured->view;
        $this->assertStringContainsString('Validation failed.', $view);
        $this->assertStringContainsString('<li>Invalid email address.</li>', $view);
    }
}

/** Fake TransferInterface that captures the transferred ResourceObject. */
final class FakeTransfer implements TransferInterface
{
    public ResourceObject|null $captured = null;

    public function __invoke(ResourceObject $ro, array $server): void
    {
        $this->captured = $ro;
    }
}

/** Minimal fake ErrorInterface for the fallback path. */
final class FakeErrorInterface implements ErrorInterface
{
    public function handle(Throwable $e, Request $request): ThrowableHandlerInterface
    {
        return new class implements ThrowableHandlerInterface {
            public function handle(Throwable $e, Request $request): self
            {
                return $this;
            }

            public function transfer(): void
            {
            }
        };
    }

    public function transfer(): void
    {
    }
}
