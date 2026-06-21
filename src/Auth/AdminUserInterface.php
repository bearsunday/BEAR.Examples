<?php

declare(strict_types=1);

namespace BEAR\Kata\Auth;

interface AdminUserInterface extends UserInterface
{
    public function authorId(): int;
}
