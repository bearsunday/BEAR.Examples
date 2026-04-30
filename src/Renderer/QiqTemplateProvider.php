<?php

declare(strict_types=1);

namespace MyVendor\Cms\Renderer;

use Override;
use Qiq\Template;
use Ray\Di\ProviderInterface;

use function dirname;

/** @implements ProviderInterface<Template> */
final class QiqTemplateProvider implements ProviderInterface
{
    #[Override]
    public function get(): Template
    {
        return Template::new(dirname(__DIR__, 2) . '/templates', '.qiq.php');
    }
}
