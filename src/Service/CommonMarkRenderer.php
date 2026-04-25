<?php

declare(strict_types=1);

namespace MyVendor\Cms\Service;

use League\CommonMark\CommonMarkConverter;

final class CommonMarkRenderer implements MarkdownRendererInterface
{
    private CommonMarkConverter $converter;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter();
    }

    public function render(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }
}
