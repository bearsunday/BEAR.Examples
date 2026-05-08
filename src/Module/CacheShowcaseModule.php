<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use BEAR\RepositoryModule\Annotation\ResourceObjectPool;
use Override;
use Ray\Di\AbstractModule;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Enables an in-memory QueryRepository cache for hermetic demos and tests.
 *
 * Non-prod BEAR contexts use a NullAdapter by default. This override keeps the
 * showcase database-less while still exercising ETag and tag invalidation.
 */
final class CacheShowcaseModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->bind(AdapterInterface::class)->annotatedWith(ResourceObjectPool::class)->toInstance(new ArrayAdapter());
    }
}
