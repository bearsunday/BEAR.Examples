<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use MyVendor\Cms\AbstractPageTestCase;

use function assert;

final class ArticleTest extends AbstractPageTestCase
{
    public function testOnGetReturnsHtml(): void
    {
        $ro = $this->resource->get('page://self/article', ['id' => 1]);
        assert($ro instanceof Article);

        $this->assertSame(200, $ro->code);

        $html = $ro->toString();
        $this->assertSame($html, $ro->view);
    }

    public function testHtmlStructureFollowsAlpsConventions(): void
    {
        $ro = $this->resource->get('page://self/article', ['id' => 1]);
        $html = $ro->toString();

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<link rel="profile" href="/profile/alps.json">', $html);
        $this->assertStringContainsString('<link rel="stylesheet" href="/css/level3.css">', $html);
        $this->assertStringContainsString('<header>', $html);
        $this->assertStringContainsString('<main>', $html);
        $this->assertStringContainsString('<nav>', $html);
        $this->assertStringContainsString('<footer>', $html);
    }

    public function testArticleAlpsClassesArePresent(): void
    {
        $ro = $this->resource->get('page://self/article', ['id' => 1]);
        $html = $ro->toString();

        $this->assertStringContainsString('<article class="Article">', $html);
        $this->assertStringContainsString('class="title"', $html);
        $this->assertStringContainsString('class="status"', $html);
        $this->assertStringContainsString('class="publishedAt"', $html);
        $this->assertStringContainsString('class="slug"', $html);
        $this->assertStringContainsString('class="body"', $html);
        $this->assertStringContainsString('class="Author"', $html);
        $this->assertStringContainsString('class="Category"', $html);
    }

    public function testNavLinksUseAlpsTransitionIds(): void
    {
        $ro = $this->resource->get('page://self/article', ['id' => 1]);
        $html = $ro->toString();

        $this->assertStringContainsString('class="goArticleList"', $html);
        $this->assertStringContainsString('class="goAuthor"', $html);
        $this->assertStringContainsString('class="goCategory"', $html);
    }

    public function testArticleFieldsAreEscaped(): void
    {
        $ro = $this->resource->get('page://self/article', ['id' => 51]);
        $html = $ro->toString();

        $this->assertStringNotContainsString('<script>alert("xss")</script>Bad', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('Excerpt with &lt;em&gt;html&lt;/em&gt; and &amp; ampersand.', $html);
    }

    public function testBodyHtmlIsRawOutput(): void
    {
        $ro = $this->resource->get('page://self/article', ['id' => 1]);
        $html = $ro->toString();

        $this->assertStringContainsString('<div class="body"><p>Lorem ipsum', $html);
    }

    public function testNotFoundReturns404(): void
    {
        $ro = $this->resource->get('page://self/article', ['id' => 99999]);

        $this->assertSame(404, $ro->code);
    }

    public function testNotFoundRendersErrorTemplate(): void
    {
        $ro = $this->resource->get('page://self/article', ['id' => 99999]);
        $html = $ro->toString();

        $this->assertStringContainsString('<h1>Error 404</h1>', $html);
        $this->assertStringContainsString('An unexpected error occurred.', $html);
        $this->assertStringNotContainsString('<article class="Article">', $html);
    }

    public function testCssLevelDefaultsToThreeAndIsOverridableByQuery(): void
    {
        $defaultRo = $this->resource->get('page://self/article', ['id' => 1]);
        $defaultHtml = $defaultRo->toString();
        $this->assertStringContainsString('href="/css/level3.css"', $defaultHtml);
        $this->assertStringContainsString('<strong>level3</strong>', $defaultHtml);

        $level2Ro = $this->resource->get('page://self/article', ['id' => 1, 'css' => 2]);
        $level2Html = $level2Ro->toString();
        $this->assertStringContainsString('href="/css/level2.css"', $level2Html);
        $this->assertStringContainsString('<strong>level2</strong>', $level2Html);
    }

    public function testInvalidCssLevelFallsBackToDefault(): void
    {
        $ro = $this->resource->get('page://self/article', ['id' => 1, 'css' => 99]);
        $html = $ro->toString();

        $this->assertStringContainsString('href="/css/level3.css"', $html);
    }
}
