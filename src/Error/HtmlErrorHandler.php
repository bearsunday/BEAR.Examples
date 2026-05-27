<?php

declare(strict_types=1);

namespace MyVendor\Cms\Error;

use BEAR\QiqModule\QiqErrorPage;
use BEAR\Resource\Code;
use BEAR\Resource\Exception\BadRequestException;
use BEAR\Resource\Exception\ResourceNotFoundException;
use BEAR\Resource\ResourceObject;
use BEAR\Sunday\Extension\Error\ErrorInterface;
use BEAR\Sunday\Extension\Router\RouterMatch as Request;
use BEAR\Sunday\Extension\Transfer\TransferInterface;
use MyVendor\Cms\Exception\UnauthenticatedException;
use Psr\Log\LoggerInterface;
use Throwable;

use function crc32;
use function sprintf;
use function str_starts_with;

final class HtmlErrorHandler implements ErrorInterface
{
    private ResourceObject|null $page = null;

    public function __construct(
        private readonly QiqErrorPage $errorPage,
        private readonly TransferInterface $transfer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(Throwable $e, Request $request): ErrorInterface
    {
        if ($e instanceof UnauthenticatedException && $this->isAdminPage($request->path)) {
            $this->page = new LoginRedirectPage();

            return $this;
        }

        $code = $this->statusCode($e);
        $logRef = (string) crc32((string) $e);
        if ($code >= 500) {
            $this->logger->error(sprintf('logref:%s %s', $logRef, (string) $e));
        }

        $this->errorPage->code = $code;
        $this->errorPage->body = [
            'status' => [
                'code' => $code,
                'message' => (new Code())->statusText[$code],
            ],
            'e' => [
                'code' => $e->getCode(),
                'class' => $e::class,
                'message' => $e->getMessage(),
            ],
            'logref' => $logRef,
        ];
        $this->page = $this->errorPage;

        return $this;
    }

    public function transfer(): void
    {
        ($this->transfer)($this->page ?? $this->errorPage, []);
    }

    private function isAdminPage(string $path): bool
    {
        return str_starts_with($path, 'page://self/admin/')
            && $path !== 'page://self/admin/login'
            && $path !== 'page://self/admin/callback';
    }

    private function statusCode(Throwable $e): int
    {
        if ($e instanceof ResourceNotFoundException || $e instanceof BadRequestException) {
            return $e->getCode();
        }

        return Code::SERVICE_UNAVAILABLE;
    }
}
