<?php

declare(strict_types=1);

namespace BEAR\Examples\Service;

interface MarkdownRendererInterface
{
    public function render(string $markdown): string;
}
