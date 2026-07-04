<?php

declare(strict_types=1);

namespace BEAR\Kata\Fake;

use Koriym\SemanticLogger\AbstractContext;

/**
 * Minimal resource response context for Semantic Logger observation in tests.
 * Mirrors BEAR.EventSourcing\Tests\Fixture\ResourceResponseContext.
 */
final class FakeResourceResponseContext extends AbstractContext
{
    public const string TYPE = 'resource_response';

    public const string SCHEMA_URL = 'https://bearsunday.github.io/schemas/semantic-logger/resource-response.json';

    public function __construct(
        public int $code,
        public mixed $body,
    ) {
    }
}
