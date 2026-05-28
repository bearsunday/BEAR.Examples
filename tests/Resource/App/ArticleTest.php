<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Resource\Exception\ParameterException;
use MyVendor\Cms\AbstractAppTestCase;
use MyVendor\Cms\Exception\ValidationException;

use function array_column;
use function json_decode;
use function sort;
use function uniqid;

final class ArticleTest extends AbstractAppTestCase
{
    public function testOnGetReturnsEmbeddedAuthorCategoryTags(): void
    {
        $ro = $this->resource->get('app://self/article', ['id' => 1]);

        $this->assertSame(200, $ro->code);
        $this->assertSame(1, $ro->body['id']);
        $this->assertSame('getting-started-with-bear-sunday', $ro->body['slug']);

        // The HAL renderer materialises #[Embed] requests under _embedded.
        $rendered = json_decode((string) $ro, true);
        $this->assertSame($ro->body['authorId'], $rendered['_embedded']['author']['id']);
        $this->assertSame($ro->body['categoryId'], $rendered['_embedded']['category']['id']);
        $this->assertIsArray($rendered['_embedded']['tagList']['items']);

        // _links carry the URI templates expanded with request arguments.
        $this->assertArrayHasKey('goArticleList', $rendered['_links']);
        $this->assertArrayHasKey('goAuthor', $rendered['_links']);
        $this->assertArrayHasKey('goCategory', $rendered['_links']);
    }

    public function testOnGetMissingReturns404(): void
    {
        $ro = $this->resource->get('app://self/article', ['id' => 99999]);

        $this->assertSame(404, $ro->code);
        $this->assertSame('Article not found', $ro->body['message']);
    }

    public function testCreateUpdateDeleteRoundTrip(): int
    {
        $post = $this->resource->post('app://self/article', [
            'slug' => 'test-article-' . uniqid(),
            'title' => 'Test Article',
            'body' => 'Body content.',
            'authorId' => 1,
            'categoryId' => 1,
            'status' => 'draft',
        ]);
        $this->assertSame(201, $post->code);
        $this->assertIsInt($post->body['id']);
        $this->assertStringStartsWith('/article?id=', (string) $post->headers['Location']);

        $id = $post->body['id'];
        $get = $this->resource->get('app://self/article', ['id' => $id]);
        $this->assertSame(200, $get->code);
        $this->assertSame('Test Article', $get->body['title']);

        $put = $this->resource->put('app://self/article', [
            'id' => $id,
            'title' => 'Updated Title',
            'body' => 'Updated body.',
            'status' => 'published',
        ]);
        $this->assertSame(200, $put->code);

        $getAfter = $this->resource->get('app://self/article', ['id' => $id]);
        $this->assertSame('Updated Title', $getAfter->body['title']);
        $this->assertSame('published', $getAfter->body['status']);

        $delete = $this->resource->delete('app://self/article', ['id' => $id]);
        $this->assertSame(204, $delete->code);

        $getMissing = $this->resource->get('app://self/article', ['id' => $id]);
        $this->assertSame(404, $getMissing->code);

        return $id;
    }

    public function testPutOnMissingReturns404(): void
    {
        $ro = $this->resource->put('app://self/article', [
            'id' => 99999,
            'title' => 't',
            'body' => 'b',
            'status' => 'draft',
        ]);
        $this->assertSame(404, $ro->code);
    }

    public function testDeleteOnMissingReturns404(): void
    {
        $ro = $this->resource->delete('app://self/article', ['id' => 99999]);
        $this->assertSame(404, $ro->code);
    }

    public function testPostMissingRequiredFieldsRejected(): void
    {
        // BEAR.Resource's `InputParam` raises InvalidArgumentException for
        // missing required built-in fields when materialising the Input DTO.
        // The framework wraps it as ParameterException at the resource boundary.
        $this->expectException(ParameterException::class);
        $this->resource->post('app://self/article', [
            'slug' => 'valid-slug',
            'title' => 'T',
            // body, authorId, categoryId all missing
        ]);
    }

    public function testPostRejectsInvalidSlugPattern(): void
    {
        try {
            $this->resource->post('app://self/article', [
                'slug' => 'INVALID Slug With Spaces',
                'title' => 'Title',
                'body' => 'Body',
                'authorId' => 1,
                'categoryId' => 1,
                'status' => 'draft',
            ]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->errors;
            $this->assertArrayHasKey('slug', $errors);
            // The pattern message in article_create.json is the source of truth for the wire copy.
            $this->assertSame(
                'Slug must contain only lowercase letters, digits and hyphens.',
                $errors['slug'][0],
            );
        }
    }

    public function testPostRejectsInvalidStatusEnum(): void
    {
        try {
            $this->resource->post('app://self/article', [
                'slug' => 'valid-slug-' . uniqid(),
                'title' => 'Title',
                'body' => 'Body',
                'authorId' => 1,
                'categoryId' => 1,
                'status' => 'invalid-status-value',
            ]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->errors;
            $this->assertArrayHasKey('status', $errors);
            $this->assertSame(
                "Status must be either 'draft' or 'published'.",
                $errors['status'][0],
            );
        }
    }

    /**
     * BEAR.Resource 1.x-dev delegates native array DTO inputs to
     * Ray.InputQuery 1.1, so malformed `tagIds` shapes are wrapped as
     * ParameterException at the resource boundary rather than leaking as
     * constructor TypeError.
     */
    public function testPostRejectsScalarTagIds(): void
    {
        $this->expectException(ParameterException::class);
        $this->resource->post('app://self/article', [
            'slug' => 'valid-slug-' . uniqid(),
            'title' => 'Title',
            'body' => 'Body',
            'authorId' => 1,
            'categoryId' => 1,
            'status' => 'draft',
            'tagIds' => 1,
        ]);
    }

    public function testPutRejectsScalarTagIds(): void
    {
        $this->expectException(ParameterException::class);
        $this->resource->put('app://self/article', [
            'id' => 1,
            'title' => 'T',
            'body' => 'B',
            'status' => 'draft',
            'tagIds' => 'not-an-array',
        ]);
    }

    public function testCreateWithTagsLinksThemAndUpdateReplaces(): void
    {
        $slug = 'tagged-' . uniqid();
        $post = $this->resource->post('app://self/article', [
            'slug' => $slug,
            'title' => 'Tagged title',
            'body' => 'Body content for tagged article.',
            'authorId' => 1,
            'categoryId' => 1,
            'status' => 'published',
            'tagIds' => [1, 2, 3],
        ]);
        $this->assertSame(201, $post->code);
        $id = $post->body['id'];

        $get = $this->resource->get('app://self/article', ['id' => $id]);
        $rendered = json_decode((string) $get, true);
        $tagsAfterCreate = array_column($rendered['_embedded']['tagList']['items'], 'id');
        sort($tagsAfterCreate);
        $this->assertSame([1, 2, 3], $tagsAfterCreate);

        $put = $this->resource->put('app://self/article', [
            'id' => $id,
            'title' => 'Tagged update',
            'body' => 'Body content updated.',
            'status' => 'published',
            'tagIds' => [4, 5],
        ]);
        $this->assertSame(200, $put->code);

        $getAgain = $this->resource->get('app://self/article', ['id' => $id]);
        $renderedAgain = json_decode((string) $getAgain, true);
        $tagsAfterUpdate = array_column($renderedAgain['_embedded']['tagList']['items'], 'id');
        sort($tagsAfterUpdate);
        $this->assertSame([4, 5], $tagsAfterUpdate);

        $this->resource->delete('app://self/article', ['id' => $id]);
    }
}
