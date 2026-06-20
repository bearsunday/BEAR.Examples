<?php

declare(strict_types=1);

namespace BEAR\Examples\Fake;

use BEAR\Examples\Auth\AdminUser;
use BEAR\Examples\Auth\AuthenticatedUser;
use BEAR\Examples\Auth\AuthSessionInterface;
use BEAR\Examples\Auth\UserInterface;
use BEAR\Examples\Auth\Visitor;

final class FakeAuthSession implements AuthSessionInterface
{
    private UserInterface $user;
    private string|null $state = null;

    public function __construct(UserInterface|null $user = null)
    {
        $this->user = $user ?? new Visitor();
    }

    public function currentUser(): UserInterface
    {
        return $this->user;
    }

    public function issueState(): string
    {
        $this->state = 'fake-state';

        return $this->state;
    }

    public function consumeState(string $state): bool
    {
        $expected = $this->state;
        $this->state = null;

        return $expected !== null && $state === $expected;
    }

    public function login(AuthenticatedUser $user, int $authorId): void
    {
        $this->user = new AdminUser(
            id: $user->id,
            email: $user->email,
            name: $user->name,
            authorId: $authorId,
        );
    }

    public function logout(): void
    {
        $this->user = new Visitor();
        $this->state = null;
    }
}
