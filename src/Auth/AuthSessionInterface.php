<?php

declare(strict_types=1);

namespace MyVendor\Cms\Auth;

interface AuthSessionInterface
{
    public function currentUser(): UserInterface;

    public function issueState(): string;

    public function consumeState(string $state): bool;

    public function login(AuthenticatedUser $user, int $authorId): void;

    public function logout(): void;
}
