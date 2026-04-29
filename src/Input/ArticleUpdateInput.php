<?php

declare(strict_types=1);

namespace MyVendor\Cms\Input;

use Ray\InputQuery\Attribute\Input;

/**
 * Input DTO for `PUT app://self/article`.
 *
 * `tagIds` is tri-state: `null` (or omitted) leaves the existing tag
 * links untouched, `[]` clears all tag links, a non-empty list replaces
 * the tag set with the given ids. The schema in
 * `var/json_validate/article_update.json` encodes the same shape and is
 * attached via `#[JsonSchema(params:)]` on `Article::onPut`; see
 * `ArticleCreateInput` for the broader DTO rationale.
 *
 * @psalm-suppress PossiblyUnusedProperty resolved at the resource layer
 */
final readonly class ArticleUpdateInput
{
    /** @param list<int>|null $tagIds null (or omitted) = leave existing links, [] = clear, non-empty list = replace. */
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
