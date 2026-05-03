<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page\Admin;

use BEAR\Resource\ResourceObject;

/**
 * Admin entry point.
 *
 * Demonstrates the BEAR.Sunday Page-resource redirect pattern: setting
 * `code = 302` together with a `Location` header turns a Page resource
 * into a server-side redirect. `/admin/index` is the single canonical
 * URL the SiteNav links to; the redirect target can change later (e.g.
 * to a real dashboard) without touching every caller.
 *
 * @property array{} $body
 */
class Index extends ResourceObject
{
    public function onGet(): static
    {
        $this->code = 302;
        $this->headers['Location'] = '/admin/articlelist';
        $this->body = [];

        return $this;
    }
}
