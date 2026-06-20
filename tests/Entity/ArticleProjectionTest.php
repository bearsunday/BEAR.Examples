<?php

declare(strict_types=1);

namespace BEAR\Examples\Entity;

use PHPUnit\Framework\TestCase;

use function str_repeat;

final class ArticleProjectionTest extends TestCase
{
    private function article(
        string $body = 'Body content',
        string|null $excerpt = 'Excerpt text',
        ArticleStatus $status = ArticleStatus::Published,
        string|null $publishedAt = '2026-01-01T10:07:00Z',
    ): Article {
        return new Article(
            123,
            'sample-article',
            'Sample Article',
            $body,
            $excerpt,
            $status,
            $publishedAt,
            10,
            20,
        );
    }

    public function testStatusClass(): void
    {
        $this->assertSame('is-published', $this->article()->statusClass());
        $this->assertSame('is-draft', $this->article(status: ArticleStatus::Draft)->statusClass());
    }

    public function testPublishedAtLabel(): void
    {
        $this->assertSame('2026-01-01', $this->article()->publishedAtLabel());
        $this->assertNull($this->article(status: ArticleStatus::Draft, publishedAt: null)->publishedAtLabel());
        $this->assertNull($this->article(publishedAt: null)->publishedAtLabel());
    }

    public function testSummaryUsesExcerptWhenPresent(): void
    {
        $this->assertSame('Excerpt text', $this->article(excerpt: 'Excerpt text')->summary());
    }

    public function testSummaryFallsBackToPlainBodyPrefix(): void
    {
        $body = '<p>' . str_repeat('a', 130) . '</p>';
        $this->assertSame(str_repeat('a', 120) . '…', $this->article(body: $body, excerpt: null)->summary());
    }

    public function testSummaryReturnsNullForEmptyBodyWithoutExcerpt(): void
    {
        $this->assertNull($this->article(body: '<p> </p>', excerpt: null)->summary());
    }

    public function testUrl(): void
    {
        $this->assertSame('/article?id=123', $this->article()->url());
    }

    public function testTagsJoined(): void
    {
        $tags = [
            ['id' => 1, 'slug' => 'php', 'name' => 'PHP'],
            ['id' => 2, 'slug' => 'bear', 'name' => 'BEAR.Sunday'],
            ['id' => 3, 'slug' => 'hal', 'name' => 'HAL'],
        ];

        $this->assertSame('PHP, BEAR.Sunday, HAL', $this->article()->tagsJoined($tags));
    }
}
