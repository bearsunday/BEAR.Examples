<?php

declare(strict_types=1);

namespace BEAR\Examples\Entity;

final readonly class Category
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public string|null $description,
        public int|null $parentId,
    ) {
    }

    public function isTopLevel(): bool
    {
        return $this->parentId === null;
    }

    public function hasParent(): bool
    {
        return $this->parentId !== null;
    }
}
