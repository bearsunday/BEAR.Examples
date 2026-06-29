<?php

declare(strict_types=1);

namespace BEAR\Kata\Fake;

use Koriym\SemanticLogger\AbstractContext;

/**
 * Minimal resource request context for Semantic Logger observation in tests.
 * Mirrors BEAR\EventSourcing\Tests\Fixture\ResourceRequestContext.
 */
final class FakeResourceRequestContext extends AbstractContext
{
    public const TYPE = 'resource_request';

    public const SCHEMA_URL = 'https://bearsunday.github.io/schemas/semantic-logger/resource-request.json';

    /** @param array<string, mixed> $query */
    public function __construct(
        public string $uri,
        public string $method,
        public array $query = [],
        public string $timestamp = '',
    ) {
    }
}
