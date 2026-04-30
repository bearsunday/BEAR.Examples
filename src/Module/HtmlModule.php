<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use BEAR\Resource\RenderInterface;
use MyVendor\Cms\Renderer\QiqRenderer;
use MyVendor\Cms\Renderer\QiqTemplateProvider;
use Override;
use Qiq\Template;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class HtmlModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->bind(Template::class)->toProvider(QiqTemplateProvider::class)->in(Scope::SINGLETON);
        $this->bind(RenderInterface::class)->to(QiqRenderer::class)->in(Scope::SINGLETON);
    }
}
