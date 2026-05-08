<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Resource\ResourceInterface;
use MyVendor\Cms\Injector;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

use function json_decode;

final class ArticleAsyncTest extends TestCase
{
    #[RequiresPhpExtension('parallel')]
    public function testAsyncContextRendersSameArticleRepresentation(): void
    {
        $sync = Injector::getInstance('test-hal-api-app')->getInstance(ResourceInterface::class);
        $async = Injector::getInstance('async-test-hal-api-app')->getInstance(ResourceInterface::class);

        $syncData = json_decode((string) $sync->get('app://self/article', ['id' => 1]), true);
        $asyncData = json_decode((string) $async->get('app://self/article', ['id' => 1]), true);

        $this->assertSame($syncData, $asyncData);
        $this->assertArrayHasKey('_embedded', $asyncData);
        $this->assertCount(3, $asyncData['_embedded']);
    }
}
