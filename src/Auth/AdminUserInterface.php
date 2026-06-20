<?php

declare(strict_types=1);

namespace BEAR\Examples\Auth;

interface AdminUserInterface extends UserInterface
{
    public function authorId(): int;
}
