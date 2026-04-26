<?php

declare(strict_types=1);

namespace MyVendor\Cms\Input;

use Ray\InputQuery\Attribute\Input;

/**
 * Input DTO for `POST app://self/article`.
 *
 * Demonstrates the Ray.InputQuery pattern adopted on Article + Auth as a
 * deliberate counterpoint to the scalar `#[JsonSchema(params:)]` style still
 * used on Author / Tag / Category / Media. BEAR.Resource resolves a parameter
 * carrying `#[Input]` by handing the flat request array to
 * `InputQueryInterface::newInstance()`, which materialises this object before
 * the resource method runs.
 *
 * Field set mirrors the previous flat signature of `Article::onPost`; see
 * `var/json_validate/article_create.json` for the matching shape contract
 * (left in place for documentation, currently not auto-attached because
 * `#[JsonSchema(params:)]` cannot validate Input DTO arguments today).
 *
 * @psalm-suppress PossiblyUnusedProperty resolved at the resource layer
 */
final readonly class ArticleCreateInput
{
    /** @param list<int> $tagIds */
    public function __construct(
        #[Input]
        public string $slug,
        #[Input]
        public string $title,
        #[Input]
        public string $body,
        #[Input]
        public int $authorId,
        #[Input]
        public int $categoryId,
        #[Input]
        public string|null $excerpt = null,
        #[Input]
        public string $status = 'draft',
        #[Input]
        public string|null $publishedAt = null,
        #[Input]
        public array $tagIds = [],
    ) {
    }
}
