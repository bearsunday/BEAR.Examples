<?php

declare(strict_types=1);

namespace BEAR\Kata\Module;

use BEAR\Kata\Provide\Error\AppThrowableHandler;
use BEAR\Kata\Provide\Error\ExceptionStatusMapper;
use BEAR\Sunday\Extension\Error\ThrowableHandlerInterface;
use Override;
use Ray\Di\AbstractModule;

final class AppErrorModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->bind(ExceptionStatusMapper::class);
        $this->bind(ThrowableHandlerInterface::class)->to(AppThrowableHandler::class);
    }
}
