<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\App;

use BEAR\Defer\Attribute\Defer;
use BEAR\Defer\DeferInterceptor;
use BEAR\Defer\DeferInterceptorInterface;
use BEAR\Defer\DeferInterface;
use BEAR\Kata\Fake\Defer\Resource\App\ArticleRepository;
use BEAR\Kata\Fake\Defer\Resource\App\CallLog;
use BEAR\Kata\Fake\Defer\SpyDefer;
use BEAR\Resource\Module\ResourceModule;
use BEAR\Resource\RequestInterface;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\Di\Scope;

use function assert;

/**
 * C1 `defer-resource-request` + C2 `defer-conditional` — tests.
 *
 * C1: #[Defer] + #[Link] declaratively enqueues follow-up resources
 *     after the response (202 Accepted) is returned.
 * C2: DeferInterface::add() manually enqueues conditional follow-ups.
 */
final class DeferTest extends TestCase
{
    private function injector(): Injector
    {
        return new Injector(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->install(new ResourceModule('BEAR\\Kata\\Fake\\Defer'));
                $this->bind(ArticleRepository::class)->in(Scope::SINGLETON);
                $this->bind(CallLog::class)->in(Scope::SINGLETON);
                $this->bind(DeferInterface::class)->to(SpyDefer::class)->in(Scope::SINGLETON);
                $this->bind(DeferInterceptorInterface::class)->to(DeferInterceptor::class);
                $this->bindInterceptor(
                    $this->matcher->any(),
                    $this->matcher->annotatedWith(Defer::class),
                    [DeferInterceptorInterface::class],
                );
            }
        });
    }

    // ── C1: Declarative defer with #[Defer] + #[Link] ──

    public function testDeferEnqueuesResolvedRequests(): void
    {
        $injector = $this->injector();
        $resource = $injector->getInstance(ResourceInterface::class);
        $spy = $injector->getInstance(DeferInterface::class);
        assert($spy instanceof SpyDefer);

        $ro = $resource->post('app://self/article', ['title' => 'Hi', 'body' => 'Body']);

        $this->assertSame(202, $ro->code);
        $this->assertSame(['id' => 100], $ro->body);

        // The interceptor should have enqueued 2 deferred requests
        $this->assertCount(2, $spy->added);

        $publish = $spy->added[0];
        $note = $spy->added[1];
        assert($publish instanceof RequestInterface);
        assert($note instanceof RequestInterface);
        $this->assertStringContainsString('publish', $publish->toUri());
        $this->assertStringContainsString('id=100', $publish->toUri());
        $this->assertStringContainsString('note', $note->toUri());
    }

    public function testFlushExecutesDeferredRequests(): void
    {
        $injector = $this->injector();
        $resource = $injector->getInstance(ResourceInterface::class);
        $spy = $injector->getInstance(DeferInterface::class);
        $log = $injector->getInstance(CallLog::class);
        assert($spy instanceof SpyDefer);

        $resource->post('app://self/article', ['title' => 'Hi', 'body' => 'Body']);
        $this->assertCount(2, $spy->added);

        // Flush should execute the deferred requests
        $spy->flush();

        $this->assertCount(2, $log->calls);
        $this->assertSame('publish:100', $log->calls[0]);
        $this->assertSame('note:100', $log->calls[1]);
    }

    // ── C2: Conditional defer with DeferInterface::add() ──

    public function testConditionalDeferSkipsWhenPublishIsFalse(): void
    {
        $injector = $this->injector();
        $resource = $injector->getInstance(ResourceInterface::class);
        $spy = $injector->getInstance(DeferInterface::class);
        assert($spy instanceof SpyDefer);

        $ro = $resource->post('app://self/conditional-article', [
            'title' => 'Hi',
            'body' => 'Body',
            'publish' => false,
        ]);

        $this->assertSame(202, $ro->code);
        $this->assertCount(0, $spy->added, 'No deferred request when publish=false');
    }

    public function testConditionalDeferEnqueuesWhenPublishIsTrue(): void
    {
        $injector = $this->injector();
        $resource = $injector->getInstance(ResourceInterface::class);
        $spy = $injector->getInstance(DeferInterface::class);
        assert($spy instanceof SpyDefer);

        $ro = $resource->post('app://self/conditional-article', [
            'title' => 'Hi',
            'body' => 'Body',
            'publish' => true,
        ]);

        $this->assertSame(202, $ro->code);
        $this->assertCount(1, $spy->added, 'One deferred request when publish=true');
    }
}
