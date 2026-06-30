<?php

declare(strict_types=1);

namespace BEAR\Kata\Smoke;

use BEAR\EventSourcing\Event;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\SemanticLogExtractor;
use DateTimeImmutable;
use Koriym\SemanticLogger\SemanticLogger;
use BEAR\Kata\Fake\FakeResourceRequestContext;
use BEAR\Kata\Fake\FakeResourceResponseContext;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

/**
 * B1 `event-extraction` — Semantic Logger observation → immutable Event.
 *
 * Demonstrates extracting state-changing events from a Semantic Logger
 * open/close tree without adding event-dispatch code to the domain.
 */
final class EventExtractionTest extends TestCase
{
    public function testExtractsWriteEventsOnlyByDefault(): void
    {
        $logger = new SemanticLogger();

        // POST — state-changing, should be extracted
        $post = $logger->open(new FakeResourceRequestContext(
            uri: 'app://self/articles',
            method: 'POST',
            query: ['title' => 'Hello'],
            timestamp: '2026-06-10T12:34:56.123456+00:00',
        ));
        $logger->close(new FakeResourceResponseContext(201, ['id' => 1]), $post);

        // GET — observation only, should be skipped by default
        $get = $logger->open(new FakeResourceRequestContext(
            uri: 'app://self/articles',
            method: 'GET',
            query: ['id' => 1],
            timestamp: '2026-06-10T12:35:00.000000+00:00',
        ));
        $logger->close(new FakeResourceResponseContext(200, ['id' => 1, 'title' => 'Hello']), $get);

        $events = (new SemanticLogExtractor())->extract($logger->flush());

        $this->assertCount(1, $events);
        $event = iterator_to_array($events)[0];
        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('POST', $event->method);
        $this->assertSame('app://self/articles', $event->uri);
        $this->assertSame(['title' => 'Hello'], $event->params);
        $this->assertSame(['id' => 1], $event->result);
        $this->assertEquals(
            new DateTimeImmutable('2026-06-10T12:34:56.123456+00:00'),
            $event->timestamp,
        );
    }

    public function testIncludesReadsWhenConfigured(): void
    {
        $logger = new SemanticLogger();

        $post = $logger->open(new FakeResourceRequestContext(
            uri: 'app://self/articles',
            method: 'POST',
            query: [],
            timestamp: '2026-06-10T12:34:56.000000+00:00',
        ));
        $logger->close(new FakeResourceResponseContext(201, ['id' => 1]), $post);

        $get = $logger->open(new FakeResourceRequestContext(
            uri: 'app://self/articles',
            method: 'GET',
            query: ['id' => 1],
            timestamp: '2026-06-10T12:35:00.000000+00:00',
        ));
        $logger->close(new FakeResourceResponseContext(200, ['id' => 1]), $get);

        $events = (new SemanticLogExtractor(
            new RecordedMethods(RecordedMethods::WITH_READS),
        ))->extract($logger->flush());

        $this->assertCount(2, $events);
    }

    public function testSkipsUnsuccessfulOperations(): void
    {
        $logger = new SemanticLogger();

        $delete = $logger->open(new FakeResourceRequestContext(
            uri: 'app://self/articles',
            method: 'DELETE',
            query: ['id' => 1],
            timestamp: '2026-06-10T12:36:00.000000+00:00',
        ));
        $logger->close(new FakeResourceResponseContext(409, ['message' => 'Conflict']), $delete);

        $events = (new SemanticLogExtractor())->extract($logger->flush());

        $this->assertCount(0, $events);
    }
}
