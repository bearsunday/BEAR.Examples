<?php

declare(strict_types=1);

namespace BEAR\Kata\Fake\Defer\Resource\App;

/**
 * Minimal fake repository for the defer example resources.
 */
final class ArticleRepository
{
    private int $nextId = 100;

    public function save(string $title, string $body): int
    {
        return $this->nextId++;
    }
}
