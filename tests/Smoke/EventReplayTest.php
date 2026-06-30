<?php

declare(strict_types=1);

namespace BEAR\Kata\Smoke;

use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\SemanticLogExtractor;
use BEAR\Kata\Fake\FakeResourceRequestContext;
use BEAR\Kata\Fake\FakeResourceResponseContext;
use Koriym\SemanticLogger\SemanticLogger;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

/**
 * B2 `event-filter-replay` — filter Events by URI / params / method and replay.
 *
 * Demonstrates using PHP standard iterators (CallbackFilterIterator)
 * to select events from an Events collection without adding query methods.
 */
final class EventReplayTest extends TestCase
{
    public function testFilterByUserId(): void
    {
        $logger = new SemanticLogger();

        $u1 = $logger->open(new FakeResourceRequestContext(
            uri: 'app://self/users',
            method: 'POST',
            query: ['id' => 'koriym', 'name' => 'Akihito'],
            timestamp: '2026-06-10T12:00:00.000000+00:00',
        ));
        $logger->close(new FakeResourceResponseContext(201, ['id' => 'koriym']), $u1);

        $o1 = $logger->open(new FakeResourceRequestContext(
            uri: 'app://self/orders',
            method: 'POST',
            query: ['order_id' => 'O-1000', 'user_id' => 'koriym'],
            timestamp: '2026-06-10T12:30:00.000000+00:00',
        ));
        $logger->close(new FakeResourceResponseContext(201, ['order_id' => 'O-1000']), $o1);

        $o2 = $logger->open(new FakeResourceRequestContext(
            uri: 'app://self/orders',
            method: 'POST',
            query: ['order_id' => 'O-2000', 'user_id' => 'other'],
            timestamp: '2026-06-10T13:00:00.000000+00:00',
        ));
        $logger->close(new FakeResourceResponseContext(201, ['order_id' => 'O-2000']), $o2);

        $events = (new SemanticLogExtractor())->extract($logger->flush());

        $forUser = new \CallbackFilterIterator(
            $events->getIterator(),
            static fn ($e): bool => ($e->params['user_id'] ?? null) === 'koriym',
        );

        $filtered = iterator_to_array($forUser, false);
        $this->assertCount(1, $filtered);
        $this->assertSame('O-1000', $filtered[0]->params['order_id']);
    }

    public function testFilterByUriPrefix(): void
    {
        $logger = new SemanticLogger();

        $u = $logger->open(new FakeResourceRequestContext(
            uri: 'app://self/users/koriym',
            method: 'POST',
            query: ['id' => 'koriym'],
            timestamp: '2026-06-10T12:00:00.000000+00:00',
        ));
        $logger->close(new FakeResourceResponseContext(201, ['id' => 'koriym']), $u);

        $o = $logger->open(new FakeResourceRequestContext(
            uri: 'app://self/orders',
            method: 'POST',
            query: ['id' => 'O-1'],
            timestamp: '2026-06-10T12:30:00.000000+00:00',
        ));
        $logger->close(new FakeResourceResponseContext(201, ['id' => 'O-1']), $o);

        $events = (new SemanticLogExtractor())->extract($logger->flush());

        $orderEvents = new \CallbackFilterIterator(
            $events->getIterator(),
            static fn ($e): bool => str_starts_with($e->uri, 'app://self/orders'),
        );

        $filtered = iterator_to_array($orderEvents, false);
        $this->assertCount(1, $filtered);
        $this->assertSame('app://self/orders', $filtered[0]->uri);
    }

    public function testStackedFilters(): void
    {
        $logger = new SemanticLogger();

        $o1 = $logger->open(new FakeResourceRequestContext(
            uri: 'app://self/orders',
            method: 'POST',
            query: ['order_id' => 'O-1000', 'user_id' => 'koriym'],
            timestamp: '2026-06-10T12:30:00.000000+00:00',
        ));
        $logger->close(new FakeResourceResponseContext(201, ['order_id' => 'O-1000']), $o1);

        $d1 = $logger->open(new FakeResourceRequestContext(
            uri: 'app://self/orders',
            method: 'DELETE',
            query: ['order_id' => 'O-1000', 'user_id' => 'koriym'],
            timestamp: '2026-06-10T14:00:00.000000+00:00',
        ));
        $logger->close(new FakeResourceResponseContext(204, null), $d1);

        $events = (new SemanticLogExtractor())->extract($logger->flush());

        $forUser = new \CallbackFilterIterator(
            $events->getIterator(),
            static fn ($e): bool => ($e->params['user_id'] ?? null) === 'koriym',
        );
        $writesOnly = new \CallbackFilterIterator(
            $forUser,
            static fn ($e): bool => $e->method === 'POST',
        );

        $filtered = iterator_to_array($writesOnly, false);
        $this->assertCount(1, $filtered);
        $this->assertSame('POST', $filtered[0]->method);
    }
}
