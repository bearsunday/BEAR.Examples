<?php

declare(strict_types=1);

namespace MyVendor\Cms\Http;

interface RequestBodyTokenInterface
{
    /** Submitted CSRF token (empty string normalised to `null`). */
    public function submitted(): string|null;
}
