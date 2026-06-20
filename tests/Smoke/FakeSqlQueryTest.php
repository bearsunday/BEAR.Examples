<?php

declare(strict_types=1);

namespace BEAR\Examples\Smoke;

use BEAR\Examples\Entity\Article;
use BEAR\Examples\Fake\FakeSqlQuery;
use PHPUnit\Framework\TestCase;
use Ray\MediaQuery\Result\AffectedRows;
use Ray\MediaQuery\Result\InsertedRow;

final class FakeSqlQueryTest extends TestCase
{
    public function testExecPostQueryReturnsInsertedRow(): void
    {
        $query = new FakeSqlQuery();
        $values = [
            'slug' => 'post-query-contract',
            'title' => 'Post Query Contract',
            'body' => 'Body',
            'excerpt' => null,
            'status' => 'draft',
            'publishedAt' => null,
            'authorId' => 1,
            'categoryId' => 1,
        ];

        $result = $query->execPostQuery('article_add', $values, InsertedRow::class);

        $this->assertInstanceOf(InsertedRow::class, $result);
        $this->assertSame($values, $result->values);
        $this->assertNotNull($result->id);

        $article = $query->getRow('article_by_slug', ['slug' => 'post-query-contract']);
        $this->assertInstanceOf(Article::class, $article);
        $this->assertSame((int) $result->id, $article->id);
    }

    public function testExecPostQueryReturnsAffectedRows(): void
    {
        $query = new FakeSqlQuery();

        $deleted = $query->execPostQuery('article_delete', ['id' => 1], AffectedRows::class);
        $missing = $query->execPostQuery('article_delete', ['id' => 99999], AffectedRows::class);

        $this->assertInstanceOf(AffectedRows::class, $deleted);
        $this->assertSame(1, $deleted->count);
        $this->assertTrue($deleted->isAffected());

        $this->assertInstanceOf(AffectedRows::class, $missing);
        $this->assertSame(0, $missing->count);
        $this->assertFalse($missing->isAffected());
    }

    public function testArticlePublishAffectsDraftOnly(): void
    {
        $query = new FakeSqlQuery();

        $published = $query->execPostQuery(
            'article_publish',
            ['id' => 6, 'status' => 'published', 'publishedAt' => '2026-01-01T00:00:00Z'],
            AffectedRows::class,
        );
        $alreadyPublished = $query->execPostQuery(
            'article_publish',
            ['id' => 1, 'status' => 'published', 'publishedAt' => '2026-01-01T00:00:00Z'],
            AffectedRows::class,
        );

        $this->assertInstanceOf(AffectedRows::class, $published);
        $this->assertInstanceOf(AffectedRows::class, $alreadyPublished);
        $this->assertSame(1, $published->count);
        $this->assertTrue($published->isAffected());
        $this->assertSame(0, $alreadyPublished->count);
        $this->assertFalse($alreadyPublished->isAffected());
    }

    public function testExecPostQueryWrapsSelectAssocRows(): void
    {
        $query = new FakeSqlQuery();

        $result = $query->execPostQuery('article_list', ['status' => 'published'], FakePostQueryRows::class);

        $this->assertInstanceOf(FakePostQueryRows::class, $result);
        $this->assertNotSame([], $result->rows);
        $this->assertIsArray($result->rows[0]);
        $this->assertArrayHasKey('published_at', $result->rows[0]);
    }

    public function testExecPostQueryWrapsSelectEntityRows(): void
    {
        $query = new FakeSqlQuery();

        $result = $query->execPostQuery(
            'article_list',
            ['status' => 'published'],
            FakePostQueryRows::class,
            new FakeEntityFetch(),
        );

        $this->assertInstanceOf(FakePostQueryRows::class, $result);
        $this->assertNotSame([], $result->rows);
        $this->assertInstanceOf(Article::class, $result->rows[0]);
    }
}
