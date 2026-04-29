<?php

declare(strict_types=1);

namespace MyVendor\Cms\Input;

use BEAR\Resource\Exception\ParameterException;
use Ray\InputQuery\Attribute\Input;

use function array_values;
use function is_array;

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
 * `tagIds` is declared `mixed` and gated through an `is_array` check
 * because `JsonSchemaInterceptor` validates after DTO hydration; see
 * `ArticleCreateInput` for the rationale.
 *
 * @psalm-suppress PossiblyUnusedProperty resolved at the resource layer
 */
final readonly class ArticleUpdateInput
{
    /** @var list<int>|null null = leave existing links, [] = clear, non-empty list = replace. */
    public array|null $tagIds;

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
        mixed $tagIds = null,
    ) {
        if ($tagIds !== null && ! is_array($tagIds)) {
            throw new ParameterException('tagIds must be an array of integers or null');
        }

        if ($tagIds === null) {
            $this->tagIds = null;

            return;
        }

        /** @var list<int> $normalised */
        $normalised = array_values($tagIds);
        $this->tagIds = $normalised;
    }
}
