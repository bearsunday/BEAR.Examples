<?php

declare(strict_types=1);

namespace MyVendor\Cms\Http;

interface RequestOriginInterface
{
    /** `Sec-Fetch-Site` (Fetch Metadata) — `null` when absent. */
    public function fetchSite(): string|null;

    /** `Origin` request header — `null` when absent. */
    public function origin(): string|null;

    /** `Referer` request header — `null` when absent. */
    public function referer(): string|null;
}
