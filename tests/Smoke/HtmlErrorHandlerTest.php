<?php

declare(strict_types=1);

namespace MyVendor\Cms\Smoke;

use BEAR\QiqModule\QiqErrorPage;
use BEAR\Resource\ResourceObject;
use BEAR\Sunday\Extension\Router\RouterMatch;
use BEAR\Sunday\Extension\Transfer\TransferInterface;
use MyVendor\Cms\Error\HtmlErrorHandler;
use MyVendor\Cms\Error\LoginRedirectPage;
use MyVendor\Cms\Exception\UnauthenticatedException;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class HtmlErrorHandlerTest extends TestCase
{
    public function testUnauthenticatedAdminPageRedirectsToLogin(): void
    {
        $transfer = new class implements TransferInterface {
            public ResourceObject|null $ro = null;

            /** @param array<string, mixed> $server */
            public function __invoke(ResourceObject $ro, array $server): void
            {
                unset($server);
                $this->ro = $ro;
            }
        };
        $handler = new HtmlErrorHandler(new QiqErrorPage(), $transfer, new NullLogger());

        $handler
            ->handle(new UnauthenticatedException(), new RouterMatch('GET', 'page://self/admin/index'))
            ->transfer();

        $this->assertInstanceOf(LoginRedirectPage::class, $transfer->ro);
        $this->assertSame(303, $transfer->ro->code);
        $this->assertSame('/admin/login', $transfer->ro->headers['Location']);
    }
}
