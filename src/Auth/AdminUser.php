<?php

declare(strict_types=1);

namespace MyVendor\Cms\Auth;

final readonly class AdminUser implements AdminUserInterface
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
        public int $authorId,
    ) {
    }

    public function authorId(): int
    {
        return $this->authorId;
    }
}
