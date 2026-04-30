<?php

declare(strict_types=1);

namespace MyVendor\Cms\Entity;

use League\CommonMark\CommonMarkConverter;
use MyVendor\Cms\Exception\MissingMarkdownRendererException;
use MyVendor\Cms\Service\CommonMarkRenderer;
use PHPUnit\Framework\TestCase;

final class ArticleTest extends TestCase
{
    private function make(string $status = 'published', int $authorId = 1, int $categoryId = 2): Article
    {
        return new Article(
            id: 100,
            slug: 'sample',
            title: 'T',
            body: 'B',
            excerpt: null,
            status: $status,
            publishedAt: $status === 'published' ? '2026-01-01T00:00:00Z' : null,
            authorId: $authorId,
            categoryId: $categoryId,
        );
    }

    public function testIsPublishedAndIsDraft(): void
    {
        $this->assertTrue($this->make('published')->isPublished());
        $this->assertFalse($this->make('published')->isDraft());

        $this->assertTrue($this->make('draft')->isDraft());
        $this->assertFalse($this->make('draft')->isPublished());
    }

    public function testBelongsToCategoryAndAuthor(): void
    {
        $a = $this->make(authorId: 7, categoryId: 3);
        $this->assertTrue($a->belongsToCategory(3));
        $this->assertFalse($a->belongsToCategory(99));
        $this->assertTrue($a->isWrittenBy(7));
        $this->assertFalse($a->isWrittenBy(99));
    }

    public function testRenderHtmlThrowsWithoutInjectedRenderer(): void
    {
        $a = $this->make();
        $this->expectException(MissingMarkdownRendererException::class);
        $a->renderHtml();
    }

    public function testRenderHtmlConvertsMarkdownWhenRendererInjected(): void
    {
        $renderer = new CommonMarkRenderer(new CommonMarkConverter());
        $a = new Article(
            id: 1,
            slug: 's',
            title: 't',
            body: '# Hello' . "\n\n" . 'A paragraph.',
            excerpt: null,
            status: 'published',
            publishedAt: '2026-01-01T00:00:00Z',
            authorId: 1,
            categoryId: 1,
            renderer: $renderer,
        );
        $html = $a->renderHtml();
        $this->assertStringContainsString('<h1>Hello</h1>', $html);
        $this->assertStringContainsString('<p>A paragraph.</p>', $html);
    }
}
