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
 * HTML-context throwable handler.
 *
 * Known domain exceptions render an inline HTML error page so browser users
 * see HTML rather than the JSON body AppThrowableHandler emits. Unexpected
 * throwables are delegated to the framework ErrorInterface, preserving the
 * dev handler's HTML stack trace and the prod handler's generic page.
 */
final class HtmlThrowableHandler implements ThrowableHandlerInterface
{
    private bool $delegated = false;
    private int $status = Code::ERROR;
    private string $statusText = 'Internal Server Error';
    private string $message = '';

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
        $this->status = $status;
        $this->statusText = $this->mapper->statusText($status);
        $this->message = $this->mapper->message($e, $status);

        return $this;
    }

    #[Override]
    public function transfer(): void
    {
        if ($this->delegated) {
            $this->fallback->transfer();

            return;
        }

        ($this->responder)(new HtmlErrorPage($this->status, $this->statusText, $this->message), []);
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
