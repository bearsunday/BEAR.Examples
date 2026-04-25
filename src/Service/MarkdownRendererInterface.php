<?php

declare(strict_types=1);

namespace MyVendor\Cms\Service;

interface MarkdownRendererInterface
{
    public function render(string $markdown): string;
}
