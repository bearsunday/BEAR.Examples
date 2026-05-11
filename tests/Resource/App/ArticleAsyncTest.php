<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Async\Module\ParallelRuntimeModule;
use BEAR\Resource\ResourceInterface;
use MyVendor\Cms\Injector;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

use function json_decode;

final class ArticleAsyncTest extends TestCase
{
    #[RequiresPhpExtension('parallel')]
    public function testParallelOverlayRendersSameArticleRepresentation(): void
    {
        $context = 'test-hal-api-app';
        $sync = Injector::getInstance($context)->getInstance(ResourceInterface::class);
        $async = Injector::getOverrideInstance($context, new ParallelRuntimeModule($context))
            ->getInstance(ResourceInterface::class);

        $syncData = json_decode((string) $sync->get('app://self/article', ['id' => 1]), true);
        $asyncData = json_decode((string) $async->get('app://self/article', ['id' => 1]), true);

        $this->assertSame($syncData, $asyncData);
        $this->assertArrayHasKey('_embedded', $asyncData);
        $this->assertCount(3, $asyncData['_embedded']);
    }
}
