<?php

declare(strict_types=1);

namespace MyVendor\Cms\Hypermedia;

use BEAR\Resource\ResourceObject;
use PHPUnit\Framework\Attributes\Depends;

/** A reader picks a tag, opens one of its articles, and looks up the author. */
final class ReaderBrowsesByTagTest extends AbstractWorkflowTestCase
{
    public function testOpensTagList(): ResourceObject
    {
        $tags = $this->resource->get('app://self/tags');
        $this->assertSame(200, $tags->code);

        return $tags;
    }

    #[Depends('testOpensTagList')]
    public function testPicksATag(ResourceObject $tags): ResourceObject
    {
        return $this->follow($tags, 'goTag', ['id' => $tags->body['items'][0]['id']]);
    }

    #[Depends('testPicksATag')]
    public function testViewsArticlesUnderThatTag(ResourceObject $tag): ResourceObject
    {
        return $this->follow($tag, 'goArticleList', ['tagId' => $tag->body['id']]);
    }

    #[Depends('testViewsArticlesUnderThatTag')]
    public function testOpensAnArticle(ResourceObject $articles): ResourceObject
    {
        return $this->follow($articles, 'goArticle', ['id' => $articles->body['items'][0]['id']]);
    }

    #[Depends('testOpensAnArticle')]
    public function testLooksUpTheAuthor(ResourceObject $article): void
    {
        $author = $this->follow($article, 'goAuthor', ['id' => $article->body['authorId']]);

        $this->assertSame($article->body['authorId'], $author->body['id']);
    }
}
