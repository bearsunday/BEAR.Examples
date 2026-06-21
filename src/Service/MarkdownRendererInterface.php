<?php

declare(strict_types=1);

namespace BEAR\Kata\Service;

interface MarkdownRendererInterface
{
    public function render(string $markdown): string;
}
