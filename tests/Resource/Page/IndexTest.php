<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use MyVendor\Cms\AbstractPageTestCase;
use MyVendor\Cms\Auth\AdminUser;

use function assert;

class IndexTest extends AbstractPageTestCase
{
    public function testOnGetRendersArticleList(): void
    {
        $ro = $this->resource->get('page://self/index');
        assert($ro instanceof Index);

        $this->assertSame(200, $ro->code);

        $html = $ro->toString();
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<a class="goArticle" href="/article?id=', $html);
        $this->assertStringContainsString('<a class="goSignIn" href="/admin/login">Sign in</a>', $html);
        $this->assertStringNotContainsString('href="/admin/index"', $html);
        $this->assertSame($html, $ro->view);
    }

    public function testAdminUserSeesAdminLink(): void
    {
        $resource = $this->resourceWithUser(new AdminUser(
            id: 'test-admin-1',
            email: 'evelyn.moore1@example.com',
            name: 'Evelyn Moore',
            authorId: 1,
        ));

        $ro = $resource->get('page://self/index');

        $this->assertSame(200, $ro->code);
        $this->assertStringContainsString('<a class="goAdminIndex" href="/admin/index">Admin</a>', $ro->toString());
        $this->assertStringContainsString('<form class="doLogout" method="post" action="/admin/logout"><button type="submit">Sign out</button></form>', $ro->toString());
    }
}
