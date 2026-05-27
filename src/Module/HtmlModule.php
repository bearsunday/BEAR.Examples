<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use BEAR\QiqModule\QiqErrorPage;
use BEAR\QiqModule\QiqErrorPageRenderer;
use BEAR\QiqModule\QiqModule;
use BEAR\Resource\RenderInterface;
use BEAR\Sunday\Extension\Error\ErrorInterface;
use MyVendor\Cms\Error\HtmlErrorHandler;
use MyVendor\Cms\Renderer\CmsQiqRenderer;
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
        $this->bind()->annotatedWith('qiq_error_view_name')->toInstance('Error');
        $this->bind(RenderInterface::class)->annotatedWith('error_page')->to(QiqErrorPageRenderer::class);
        $this->bind(QiqErrorPage::class);
        $this->bind(ErrorInterface::class)->to(HtmlErrorHandler::class);
    }
}
