<?php

declare(strict_types=1);

namespace BEAR\Examples\Hypermedia;

use BEAR\Resource\ResourceObject;
use PHPUnit\Framework\Attributes\Depends;

/** A reader narrows the article list down by category. */
final class ReaderBrowsesByCategoryTest extends AbstractWorkflowTestCase
{
    public function testOpensCategoryList(): ResourceObject
    {
        $categories = $this->resource->get('app://self/categories');
        $this->assertSame(200, $categories->code);

        return $categories;
    }

    #[Depends('testOpensCategoryList')]
    public function testPicksACategory(ResourceObject $categories): ResourceObject
    {
        return $this->follow($categories, 'goCategory', ['id' => $categories->body['items'][0]['id']]);
    }

    #[Depends('testPicksACategory')]
    public function testListsArticlesUnderThatCategory(ResourceObject $category): void
    {
        $articles = $this->follow($category, 'goArticleList', ['categoryId' => $category->body['id']]);

        $this->assertSame(200, $articles->code);
    }
}
