<?php

declare(strict_types=1);

namespace MyVendor\Cms\Hypermedia;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\AbstractAppTestCase;

use function parse_str;
use function parse_url;

use const PHP_URL_QUERY;

/**
 * Base for one-story-per-class hypermedia workflow tests.
 *
 * Each subclass tells one user story as a chain of `#[Depends]`-linked
 * test methods: the first test performs the entry GET (or POST) and
 * returns the `ResourceObject`; each follow-up declares
 * `#[Depends('previousStep')]`, receives that object, and follows a
 * rel from it via `follow()`. The class name is the story title; the
 * method names are the steps. PHPUnit's testdox output reads top to
 * bottom as the user story.
 *
 * See docs/conventions.md §7.1 for the rules these stories follow.
 */
abstract class AbstractWorkflowTestCase extends AbstractAppTestCase
{
    /**
     * Follow a `_links` rel from the previous response. The only way
     * stories transition between resources — never by hard-coded URI.
     * Asserts the transition succeeded so steps stay one-liners and
     * every link in every story is exercised, not just the last one.
     *
     * @param array<string, mixed> $vars
     */
    protected function follow(ResourceObject $ro, string $rel, array $vars = []): ResourceObject
    {
        $next = $this->resource->href($rel, $vars, $ro);
        $this->assertSame(200, $next->code, "Following rel `{$rel}` should return 200");

        return $next;
    }

    protected function idFromLocation(string $location): int
    {
        parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $query);

        return (int) ($query['id'] ?? 0);
    }
}
