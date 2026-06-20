<?php

declare(strict_types=1);

namespace BEAR\Examples\Query;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Result\AffectedRows;

interface ArticleCommandInterface
{
    #[DbQuery('article_add')]
    public function add(
        string $slug,
        string $title,
        string $body,
        string|null $excerpt,
        string $status,
        string|null $publishedAt,
        int $authorId,
        int $categoryId,
    ): void;

    #[DbQuery('article_update')]
    public function update(
        int $id,
        string $title,
        string $body,
        string|null $excerpt,
        string $status,
        string|null $publishedAt,
    ): void;

    /**
     * Apply the draft → published state transition.
     *
     * Distinct from `update()` so the publish flow's audit/lifecycle hooks
     * (and the Page-level confirmation gate) can attach to a method whose
     * sole responsibility is the state move — not arbitrary field edits.
     * Returns the atomic transition's affected row count so callers can
     * distinguish success from a concurrent publish/delete.
     */
    #[DbQuery('article_publish')]
    public function publish(int $id, string $status, string $publishedAt): AffectedRows;

    #[DbQuery('article_delete')]
    public function delete(int $id): void;
}
