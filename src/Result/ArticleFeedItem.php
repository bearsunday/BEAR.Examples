<?php

declare(strict_types=1);

namespace MyVendor\Cms\Result;

final readonly class ArticleFeedItem
{
    public function __construct(
        public int $id,
        public string $url,
        public string $title,
        public string $summary,
        public string $publishedAt,
        public string $publishedAtLabel,
        public string $postedAgoLabel,
    ) {
    }
}
