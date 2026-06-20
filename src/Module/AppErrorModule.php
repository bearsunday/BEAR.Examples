<?php

declare(strict_types=1);

namespace BEAR\Examples\Module;

use BEAR\Sunday\Extension\Error\ThrowableHandlerInterface;
use BEAR\Examples\Provide\Error\AppThrowableHandler;
use BEAR\Examples\Provide\Error\ExceptionStatusMapper;
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
