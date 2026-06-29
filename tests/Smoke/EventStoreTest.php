<?php

declare(strict_types=1);

namespace BEAR\Kata\Smoke;

use BEAR\EventSourcing\Event;
use BEAR\EventSourcing\Events;
use BEAR\EventSourcing\EventsInterface;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\SemanticLogExtractor;
use BEAR\EventSourcing\Store\InMemoryEventStore;
use DateTimeImmutable;
use Koriym\SemanticLogger\SemanticLogger;
use BEAR\Kata\Fake\FakeResourceRequestContext;
use BEAR\Kata\Fake\FakeResourceResponseContext;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

/**
 * B3 `event-store-persistence` — persist extracted Events via EventStoreInterface.
 *
 * Demonstrates the InMemoryEventStore for tests and development.
 * The MediaQueryEventStore (SQL-backed) follows the same interface.
 */
final class EventStoreTest extends TestCase
{
    private function exampleEvents(): EventsInterface
    {
        $logger = new SemanticLogger();

        $post = $logger->open(new FakeResourceRequestContext(
            uri: 'app://self/articles',
            method: 'POST',
            query: ['title' => 'Hello'],
            timestamp: '2026-06-10T12:34:56.000000+00:00',
        ));
        $logger->close(new FakeResourceResponseContext(201, ['id' => 1]), $post);

        return (new SemanticLogExtractor())->extract($logger->flush());
    }

    public function testAppendAndRetrieveAll(): void
    {
        $store = new InMemoryEventStore();
        $store->appendAll($this->exampleEvents());

        $stored = $store->all();
        $this->assertCount(1, $stored);

        $events = iterator_to_array($stored, false);
        $this->assertInstanceOf(Event::class, $events[0]);
        $this->assertSame('POST', $events[0]->method);
        $this->assertSame('app://self/articles', $events[0]->uri);
    }

    public function testAppendSingle(): void
    {
        $store = new InMemoryEventStore();
        $event = new Event(
            uri: 'app://self/articles',
            method: 'POST',
            timestamp: new DateTimeImmutable('2026-06-10T12:00:00+00:00'),
            params: ['title' => 'Test'],
            result: ['id' => 42],
        );
        $store->append($event);

        $stored = iterator_to_array($store->all(), false);
        $this->assertCount(1, $stored);
        $this->assertSame('Test', $stored[0]->params['title']);
    }

    public function testAppendAllFromEventsInterface(): void
    {
        $events = new Events([
            new Event(
                uri: 'app://self/a',
                method: 'POST',
                timestamp: new DateTimeImmutable('2026-06-10T12:00:00+00:00'),
                params: [],
                result: null,
            ),
            new Event(
                uri: 'app://self/b',
                method: 'DELETE',
                timestamp: new DateTimeImmutable('2026-06-10T13:00:00+00:00'),
                params: [],
                result: null,
            ),
        ]);

        $store = new InMemoryEventStore();
        $store->appendAll($events);

        $stored = iterator_to_array($store->all(), false);
        $this->assertCount(2, $stored);
        $this->assertSame('POST', $stored[0]->method);
        $this->assertSame('DELETE', $stored[1]->method);
    }

    public function testEmptyStoreReturnsEmptyEvents(): void
    {
        $store = new InMemoryEventStore();

        $this->assertCount(0, $store->all());
    }
}
