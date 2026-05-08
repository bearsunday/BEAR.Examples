<?php

declare(strict_types=1);

namespace MyVendor\Cms\Smoke;

use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final class FakePostQueryRows implements PostQueryInterface
{
    /** @param list<mixed> $rows */
    public function __construct(
        public readonly array $rows,
    ) {
    }

    public static function fromContext(PostQueryContext $context): static
    {
        return new static($context->rows);
    }
}
