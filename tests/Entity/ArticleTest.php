<?php

declare(strict_types=1);

namespace BEAR\Kata\Entity;

use PHPUnit\Framework\TestCase;

final class ArticleTest extends TestCase
{
    private function make(ArticleStatus $status = ArticleStatus::Published, int $authorId = 1, int $categoryId = 2): Article
    {
        return new Article(
            id: 100,
            slug: 'sample',
            title: 'T',
            body: 'B',
            excerpt: null,
            status: $status,
            publishedAt: $status === ArticleStatus::Published ? '2026-01-01T00:00:00Z' : null,
            authorId: $authorId,
            categoryId: $categoryId,
        );
    }

    public function testIsPublishedAndIsDraft(): void
    {
        $this->assertTrue($this->make(ArticleStatus::Published)->isPublished());
        $this->assertFalse($this->make(ArticleStatus::Published)->isDraft());

        $this->assertTrue($this->make(ArticleStatus::Draft)->isDraft());
        $this->assertFalse($this->make(ArticleStatus::Draft)->isPublished());
    }

    public function testBelongsToCategoryAndAuthor(): void
    {
        $a = $this->make(authorId: 7, categoryId: 3);
        $this->assertTrue($a->belongsToCategory(3));
        $this->assertFalse($a->belongsToCategory(99));
        $this->assertTrue($a->isWrittenBy(7));
        $this->assertFalse($a->isWrittenBy(99));
    }
}
