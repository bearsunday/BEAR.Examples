<?php

declare(strict_types=1);

namespace BEAR\Kata\Service;

use League\CommonMark\CommonMarkConverter;

final class CommonMarkRenderer implements MarkdownRendererInterface
{
    public function __construct(
        private readonly CommonMarkConverter $converter,
    ) {
    }

    public function render(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }
}
