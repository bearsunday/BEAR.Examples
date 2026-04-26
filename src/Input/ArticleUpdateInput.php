<?php

declare(strict_types=1);

namespace MyVendor\Cms\Input;

use Ray\InputQuery\Attribute\Input;

/**
 * Input DTO for `PUT app://self/article`.
 *
 * Mirrors the previous flat signature of `Article::onPut`. `tagIds` retains
 * its tri-state semantics: `null` means "do not touch tag links", `[]` means
 * "clear all tags", a non-empty list means "replace with these".
 *
 * The matching JSON shape is documented in
 * `var/json_validate/article_update.json`; see `ArticleCreateInput` for the
 * rationale behind keeping the schema file but not attaching it via
 * `#[JsonSchema(params:)]` while DTO-aware validation is unavailable.
 *
 * @psalm-suppress PossiblyUnusedProperty resolved at the resource layer
 */
final readonly class ArticleUpdateInput
{
    /** @param list<int>|null $tagIds When provided, replaces the tag set entirely. */
    public function __construct(
        #[Input]
        public int $id,
        #[Input]
        public string $title,
        #[Input]
        public string $body,
        #[Input]
        public string $status,
        #[Input]
        public string|null $excerpt = null,
        #[Input]
        public string|null $publishedAt = null,
        #[Input]
        public array|null $tagIds = null,
    ) {
    }
}
