<?php

declare(strict_types=1);

namespace BEAR\Examples\Module;

use BEAR\QiqModule\QiqModule;
use BEAR\Resource\RenderInterface;
use BEAR\Sunday\Extension\Error\ThrowableHandlerInterface;
use BEAR\Examples\Provide\Error\HtmlThrowableHandler;
use BEAR\Examples\Renderer\CmsQiqRenderer;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

use function dirname;

final class HtmlModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(new QiqModule(dirname(__DIR__, 2) . '/templates'));
        $this->bind(RenderInterface::class)->to(CmsQiqRenderer::class)->in(Scope::SINGLETON);
        $this->bind(ThrowableHandlerInterface::class)->to(HtmlThrowableHandler::class);
    }
}
