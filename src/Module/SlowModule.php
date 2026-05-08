<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use MyVendor\Cms\Resource\App\Author;
use MyVendor\Cms\Resource\App\Category;
use MyVendor\Cms\Resource\App\Tags;
use Ray\Di\AbstractModule;

/**
 * Demo-only latency module for async timing.
 *
 * It slows the three resources embedded by Article::onGet so the async demo
 * can prove the context swap without relying on database latency.
 */
final class SlowModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind()->annotatedWith('slow_embed_delay_us')->toInstance(150_000);

        $embeddedResources = $this->matcher->logicalOr(
            $this->matcher->subclassesOf(Author::class),
            $this->matcher->logicalOr(
                $this->matcher->subclassesOf(Category::class),
                $this->matcher->subclassesOf(Tags::class),
            ),
        );

        $this->bindInterceptor(
            $embeddedResources,
            $this->matcher->startsWith('onGet'),
            [SlowEmbedInterceptor::class],
        );
    }
}
