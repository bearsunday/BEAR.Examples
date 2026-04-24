<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;

class Index extends ResourceObject
{
    #[Link(rel: 'articles', href: 'app://self/articles{?page,perPage,categoryId,tagId,status}')]
    #[Link(rel: 'article', href: 'app://self/article{?id}')]
    #[Link(rel: 'categories', href: 'app://self/categories')]
    #[Link(rel: 'tags', href: 'app://self/tags')]
    #[Link(rel: 'author', href: 'app://self/author{?id}')]
    #[Link(rel: 'media', href: 'app://self/media{?id}')]
    public function onGet(): static
    {
        $this->body = [
            'name' => 'BEAR.Cms',
            'description' => 'Reference CMS built on BEAR.Sunday. App-resource only.',
        ];

        return $this;
    }
}
