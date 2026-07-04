<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\Page;

use BEAR\Kata\Service\MarkdownRendererInterface;
use BEAR\Resource\Annotation\Embed;
use BEAR\Resource\Code;
use BEAR\Resource\Request;
use BEAR\Resource\ResourceObject;

use function assert;
use function is_array;
use function mb_substr;
use function strip_tags;
use function substr;
use function trim;

/**
 * Reachability: the article graph lives in `app://self/article`, which
 * already composes author / category / tagList via #[Embed]. This Page
 * references that App state instead of re-assembling it from Query
 * interfaces (docs/conventions.md §4 — Page reads App).
 *
 * The self embed (rel: '_self') runs eagerly before onGet: the App body
 * is flattened into this Page's body and the App status code propagates,
 * so App's 404 is this Page's 404. onGet then materialises the child
 * Requests into plain arrays for the Qiq template and adds the pure
 * presentation derivatives the Page is allowed to own: bodyHtml,
 * publishedAtLabel, summary (mirrors of Entity\Article projections,
 * kept here because the App wire format is pinned by JSON Schema).
 *
 * @property array{message: string}|array{id: int, slug: string, title: string, body: string, excerpt: string|null, status: string, publishedAt: string|null, authorId: int, categoryId: int, author: array{id: int, name: string, email: string}|null, category: array{id: int, slug: string, name: string}|null, tags: list<array{id: int, slug: string, name: string}>, bodyHtml: string, publishedAtLabel: string|null, summary: string|null} $body
 */
class Article extends ResourceObject
{
    public function __construct(
        private readonly MarkdownRendererInterface $markdown,
    ) {
    }

    #[Embed(rel: '_self', src: 'app://self/article{?id}')]
    public function onGet(int $id): static
    {
        if ($this->code === Code::NOT_FOUND) {
            $this->body = ['message' => 'Article not found'];

            return $this;
        }

        assert(is_array($this->body));
        $this->body['author'] = $this->materialise($this->body['author']);
        $this->body['category'] = $this->materialise($this->body['category']);
        $tagListBody = $this->materialise($this->body['tagList']);
        $this->body['tags'] = $tagListBody['items'] ?? [];
        unset($this->body['tagList']);

        // Pure presentation derivatives (conventions §4 exception list).
        $body = (string) $this->body['body'];
        $excerpt = $this->body['excerpt'];
        $publishedAt = $this->body['publishedAt'];
        $this->body['bodyHtml'] = $this->markdown->render($body);
        $this->body['publishedAtLabel'] = $this->body['status'] === 'published' && $publishedAt !== null
            ? substr((string) $publishedAt, 0, 10)
            : null;
        $plain = trim(strip_tags($body));
        $this->body['summary'] = $excerpt !== null && $excerpt !== ''
            ? (string) $excerpt
            : ($plain === '' ? null : mb_substr($plain, 0, 120) . '…');

        return $this;
    }

    /**
     * Evaluate an embedded child Request here in the Page so the template
     * receives plain arrays (App resources carry no HTML template of their
     * own; leaving Requests in the body would make the renderer try to
     * stringify them).
     *
     * @return array<string, mixed>|null
     */
    private function materialise(mixed $request): array|null
    {
        assert($request instanceof Request);
        $ro = $request->__invoke();

        return $ro->code === Code::OK && is_array($ro->body) ? $ro->body : null;
    }
}
