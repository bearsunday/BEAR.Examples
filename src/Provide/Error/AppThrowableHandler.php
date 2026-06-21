<?php

declare(strict_types=1);

namespace BEAR\Kata\Provide\Error;

use BEAR\Resource\Code;
use BEAR\Sunday\Extension\Error\ErrorInterface;
use BEAR\Sunday\Extension\Error\ThrowableHandlerInterface;
use BEAR\Sunday\Extension\Router\RouterMatch as Request;
use BEAR\Sunday\Extension\Transfer\TransferInterface;
use ErrorException;
use Exception;
use Override;
use Throwable;

use const E_ERROR;

/**
 * Default (JSON) throwable handler.
 *
 * Maps known domain exceptions to an HTTP status via ExceptionStatusMapper
 * and emits an AppErrorPage JSON body. Unexpected throwables are delegated
 * to the framework ErrorInterface (dev: stack trace, prod: generic page).
 *
 * The html context overrides this binding with HtmlThrowableHandler, which
 * shares the same ExceptionStatusMapper but renders HTML.
 */
final class AppThrowableHandler implements ThrowableHandlerInterface
{
    private AppErrorPage|null $errorPage = null;
    private bool $delegated = false;

    public function __construct(
        private readonly TransferInterface $responder,
        private readonly ErrorInterface $fallback,
        private readonly ExceptionStatusMapper $mapper,
    ) {
    }

    #[Override]
    public function handle(Throwable $e, Request $request): self
    {
        $status = $this->mapper->status($e);
        if ($status === null) {
            $this->delegated = true;
            $this->fallback->handle($this->asException($e), $request);

            return $this;
        }

        $this->delegated = false;
        $this->errorPage = new AppErrorPage($status, ['message' => $this->mapper->message($e, $status)]);

        return $this;
    }

    #[Override]
    public function transfer(): void
    {
        if ($this->delegated) {
            $this->fallback->transfer();

            return;
        }

        ($this->responder)($this->errorPage ?? new AppErrorPage(Code::ERROR, [
            'message' => $this->mapper->statusText(Code::ERROR),
        ]), []);
    }

    private function asException(Throwable $e): Exception
    {
        if ($e instanceof Exception) {
            return $e;
        }

        return new ErrorException(
            $e->getMessage(),
            (int) $e->getCode(),
            E_ERROR,
            $e->getFile(),
            $e->getLine(),
            $e,
        );
    }
}
