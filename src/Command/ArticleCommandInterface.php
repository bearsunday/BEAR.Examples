<?php

declare(strict_types=1);

namespace MyVendor\Cms\Command;

use Ray\MediaQuery\Annotation\DbQuery;

interface ArticleCommandInterface
{
    #[DbQuery('create_article')]
    public function create(
        string $slug,
        string $title,
        string $body,
        string|null $excerpt,
        string $status,
        string|null $publishedAt,
        int $authorId,
        int $categoryId,
    ): void;

    #[DbQuery('update_article')]
    public function update(
        int $id,
        string $title,
        string $body,
        string|null $excerpt,
        string $status,
        string|null $publishedAt,
    ): void;

    #[DbQuery('delete_article')]
    public function delete(int $id): void;
}
