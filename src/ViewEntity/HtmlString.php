<?php

declare(strict_types=1);

namespace MyVendor\Cms\ViewEntity;

use Stringable;

final readonly class HtmlString implements Stringable
{
    public function __construct(
        public string $html,
    ) {
    }

    public function __toString(): string
    {
        return $this->html;
    }
}
