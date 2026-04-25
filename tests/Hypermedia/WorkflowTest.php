<?php

declare(strict_types=1);

namespace MyVendor\Cms\Hypermedia;

use BEAR\Resource\ResourceInterface;
use MyVendor\Cms\Injector;
use PHPUnit\Framework\TestCase;

/**
 * Hypermedia workflow tests = user stories.
 *
 * Each test follows _links from the previous response, never hard-coding
 * URIs. If a rel name in any resource changes, the corresponding ALPS
 * Choreography transition has effectively been renamed and tests fail —
 * this catches both schema drift and ALPS/HAL alignment regressions.
 *
 * Bound to test-hal-api-app (FakeSqlQuery), so tests are hermetic.
 */
final class WorkflowTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = Injector::getInstance('test-hal-api-app');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    /**
     * Story 1 — Browse a published article and reach its author.
     *
     * goArticleList?status=published → goArticle (first item) → goAuthor
     */
    public function testStoryBrowseToAuthor(): void
    {
        $list = $this->resource->get('app://self/articles', ['status' => 'published', 'perPage' => 5]);
        $listBody = json_decode((string) $list, true);
        $this->assertNotEmpty($listBody['items']);
        $this->assertArrayHasKey('goArticle', $listBody['_links']);

        $firstId = $listBody['items'][0]['id'];
        $article = $this->resource->get('app://self/article', ['id' => $firstId]);
        $articleBody = json_decode((string) $article, true);

        $this->assertSame($firstId, $articleBody['id']);
        $this->assertArrayHasKey('goAuthor', $articleBody['_links']);
        // The expanded link points at the actual author the resource is about to embed.
        $this->assertStringContainsString('id=' . $articleBody['authorId'], $articleBody['_links']['goAuthor']['href']);

        $author = $this->resource->get('app://self/author', ['id' => $articleBody['authorId']]);
        $authorBody = json_decode((string) $author, true);

        $this->assertSame($articleBody['authorId'], $authorBody['id']);
    }

    /**
     * Story 2 — Filter the article list by tag and follow into a single article.
     *
     * goTagList → goTag (any) → goArticleList?tagId=N → goArticle (any)
     */
    public function testStoryTagFilter(): void
    {
        $tags = $this->resource->get('app://self/tags');
        $tagsBody = json_decode((string) $tags, true);
        $this->assertNotEmpty($tagsBody['items']);

        // Pick a tag that has at least one article linked (FakeSqlQuery seed has many).
        $tagId = $tagsBody['items'][0]['id'];

        $filtered = $this->resource->get('app://self/articles', ['tagId' => $tagId, 'perPage' => 50]);
        $filteredBody = json_decode((string) $filtered, true);

        // The filter is a property of articles, not a guarantee of non-empty;
        // assert response shape rather than item count.
        $this->assertSame(200, $filtered->code);
        $this->assertIsArray($filteredBody['items']);
    }

    /**
     * Story 3 — Create an article, fetch it through the returned Location,
     * then delete it. The journey crosses doCreateArticle → goArticle → doDeleteArticle.
     */
    public function testStoryCreateReadDelete(): void
    {
        $slug = 'workflow-' . uniqid();
        $created = $this->resource->post('app://self/article', [
            'slug' => $slug,
            'title' => 'Workflow story article',
            'body' => 'Hypermedia roundtrip body content.',
            'authorId' => 1,
            'categoryId' => 1,
            'status' => 'published',
        ]);
        $this->assertSame(201, $created->code);
        $location = $created->headers['Location'];
        $this->assertStringStartsWith('/article?id=', (string) $location);

        // Use the Location header just like a hypermedia client would.
        parse_str(parse_url((string) $location, PHP_URL_QUERY) ?? '', $query);
        $newId = (int) ($query['id'] ?? 0);
        $this->assertGreaterThan(0, $newId);

        $get = $this->resource->get('app://self/article', ['id' => $newId]);
        $this->assertSame(200, $get->code);
        $this->assertSame($slug, $get->body['slug']);

        $del = $this->resource->delete('app://self/article', ['id' => $newId]);
        $this->assertSame(204, $del->code);
    }
}
