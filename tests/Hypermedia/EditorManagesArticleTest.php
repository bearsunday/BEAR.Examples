<?php

declare(strict_types=1);

namespace BEAR\Examples\Hypermedia;

use BEAR\Resource\ResourceObject;
use PHPUnit\Framework\Attributes\Depends;

use function uniqid;

/** An editor creates an article, revises it, and finally retires it. */
final class EditorManagesArticleTest extends AbstractWorkflowTestCase
{
    public function testCreatesAnArticle(): ResourceObject
    {
        $created = $this->resource->post('app://self/article', [
            'slug'       => 'workflow-' . uniqid(),
            'title'      => 'First draft',
            'body'       => 'Initial body.',
            'authorId'   => 1,
            'categoryId' => 1,
            'status'     => 'published',
        ]);
        $this->assertSame(201, $created->code);

        return $created;
    }

    #[Depends('testCreatesAnArticle')]
    public function testReadsBackTheNewArticle(ResourceObject $created): ResourceObject
    {
        $id = $this->idFromLocation((string) $created->headers['Location']);
        $read = $this->resource->get('app://self/article', ['id' => $id]);

        $this->assertSame('First draft', $read->body['title']);

        return $read;
    }

    #[Depends('testReadsBackTheNewArticle')]
    public function testRevisesTheArticle(ResourceObject $read): ResourceObject
    {
        $id = $read->body['id'];
        $this->resource->put('app://self/article', [
            'id'     => $id,
            'title'  => 'Revised draft',
            'body'   => 'Revised body.',
            'status' => 'published',
        ]);
        $revised = $this->resource->get('app://self/article', ['id' => $id]);

        $this->assertSame('Revised draft', $revised->body['title']);

        return $revised;
    }

    #[Depends('testRevisesTheArticle')]
    public function testRetiresTheArticle(ResourceObject $revised): void
    {
        $id = $revised->body['id'];
        $this->resource->delete('app://self/article', ['id' => $id]);
        $gone = $this->resource->get('app://self/article', ['id' => $id]);

        $this->assertSame(404, $gone->code);
    }
}
