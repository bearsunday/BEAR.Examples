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
 * Per-field shape (`slug` regex, `status` enum, length bounds, …) is
 * validated by `#[JsonSchema(params: 'article_create.json')]` on
 * `Article::onPost`; the matching schema lives in
 * `var/json_validate/article_create.json`.
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
