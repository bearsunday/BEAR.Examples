<?php

declare(strict_types=1);

namespace MyVendor\Cms\Input;

use BEAR\Resource\Exception\ParameterException;
use Ray\InputQuery\Attribute\Input;

use function array_values;
use function is_array;

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
 * `tagIds` is declared `mixed` and gated through an `is_array` check because
 * `JsonSchemaInterceptor` validates the params *after* DTO hydration: a
 * scalar `tagIds` would otherwise hit the typed `array` property as a
 * `TypeError` (→ 5xx) rather than the intended `ParameterException` (→ 400).
 * See docs/conventions.md §4 "Input DTO pitfalls" for the broader note.
 *
 * @psalm-suppress PossiblyUnusedProperty resolved at the resource layer
 */
final readonly class ArticleCreateInput
{
    /** @var list<int> */
    public array $tagIds;

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
        mixed $tagIds = [],
    ) {
        // Ray.InputQuery applies the declared default only when the parameter
        // disallows null. `mixed` always allows null, so an omitted `tagIds`
        // arrives here as null — coalesce to the empty list.
        if ($tagIds === null) {
            $tagIds = [];
        }

        if (! is_array($tagIds)) {
            throw new ParameterException('tagIds must be an array of integers');
        }

        /** @var list<int> $normalised */
        $normalised = array_values($tagIds);
        $this->tagIds = $normalised;
    }
}
