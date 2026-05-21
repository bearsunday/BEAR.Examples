<?php

declare(strict_types=1);

namespace MyVendor\Cms\Auth;

interface AdminUserInterface extends UserInterface
{
    public function authorId(): int;
}
