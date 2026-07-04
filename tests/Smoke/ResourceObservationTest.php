<?php

declare(strict_types=1);

namespace BEAR\Kata\Smoke;

use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\ResourceObservationModule;
use BEAR\EventSourcing\SemanticLogExtractor;
use BEAR\Resource\Module\ResourceModule;
use BEAR\Resource\ResourceInterface;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function assert;
use function iterator_to_array;
use function str_contains;

/**
 * B4 `resource-observation-bridge` — wrap InvokerInterface to observe
 * BEAR.Resource execution and generate Semantic Logger open/close entries.
 *
 * Demonstrates ResourceObservationModule decorating the resource invoker
 * so that resource calls produce a flushable Semantic Logger log, which
 * the SemanticLogExtractor can turn into Events.
 */
final class ResourceObservationTest extends TestCase
{
    public function testObservationProducesExtractableEvents(): void
    {
        // Use a standalone injector with a simple resource that has no DB deps.
        // ResourceObservationModule wraps InvokerInterface to record observations.
        $module = new ResourceObservationModule(
            methods: new RecordedMethods(RecordedMethods::WITH_READS),
            module: new ResourceModule('BEAR\\Kata\\Fake\\Observation'),
        );

        $injector = new Injector($module);
        $resource = $injector->getInstance(ResourceInterface::class);

        // Execute a resource call — the observation bridge records it
        $ro = $resource->get('app://self/hello', ['name' => 'Kata']);
        $this->assertSame(200, $ro->code);
        $this->assertSame('Hello, Kata!', $ro->body['greeting']);

        // Flush the Semantic Logger and extract events
        $logger = $injector->getInstance(SemanticLoggerInterface::class);
        assert($logger instanceof SemanticLogger);
        $log = $logger->flush();

        $events = (new SemanticLogExtractor(
            new RecordedMethods(RecordedMethods::WITH_READS),
        ))->extract($log);

        $eventList = iterator_to_array($events, false);
        $this->assertNotEmpty($eventList, 'Should observe at least one resource call');

        // The GET on hello should appear as an observed event
        $foundGet = false;
        foreach ($eventList as $event) {
            if ($event->method === 'GET' && str_contains($event->uri, 'hello')) {
                $foundGet = true;
                $this->assertSame(['name' => 'Kata'], $event->params);
                break;
            }
        }

        $this->assertTrue($foundGet, 'Should observe the GET hello resource call');
    }
}
