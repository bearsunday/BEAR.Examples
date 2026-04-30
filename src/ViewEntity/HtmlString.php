<?php

declare(strict_types=1);

namespace MyVendor\Cms\ViewEntity;

final readonly class HtmlString
{
    public function __construct(
        public string $html,
    ) {
    }
}
