<?php

declare(strict_types=1);

namespace BEAR\Kata\Fake;

use BEAR\Kata\Auth\AuthSessionInterface;
use BEAR\Kata\Auth\UserInterface;
use Ray\Di\AbstractModule;

final class FakeUserModule extends AbstractModule
{
    public function __construct(
        private readonly UserInterface $user,
        AbstractModule|null $module = null,
    ) {
        parent::__construct($module);
    }

    protected function configure(): void
    {
        $this->bind(AuthSessionInterface::class)->toInstance(new FakeAuthSession($this->user));
    }
}
