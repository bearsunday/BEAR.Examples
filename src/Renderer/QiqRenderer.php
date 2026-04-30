<?php

declare(strict_types=1);

namespace MyVendor\Cms\Renderer;

use BEAR\Resource\RenderInterface;
use BEAR\Resource\ResourceObject;
use Override;
use Qiq\Template;
use Throwable;

use function array_key_exists;
use function is_array;
use function ltrim;

final readonly class QiqRenderer implements RenderInterface
{
    public function __construct(
        private Template $template,
    ) {
    }

    #[Override]
    public function render(ResourceObject $ro): string
    {
        if (! array_key_exists('Content-Type', $ro->headers)) {
            $ro->headers['Content-Type'] = 'text/html; charset=utf-8';
        }

        $vars = is_array($ro->body) ? $ro->body : ['value' => $ro->body];
        if ($ro->code >= 400) {
            return $this->renderError($ro, $vars);
        }

        try {
            $ro->view = $this->template->render($this->templateName($ro), $vars);

            return $ro->view;
        } catch (Throwable $e) {
            $ro->code = 500;

            return $this->renderError($ro, ['message' => $e->getMessage()]);
        }
    }

    /** @param array<string, mixed> $vars */
    private function renderError(ResourceObject $ro, array $vars): string
    {
        $message = (string) ($vars['message'] ?? 'An error occurred');
        $ro->view = $this->template->render('error', [
            'code' => $ro->code,
            'message' => $message,
        ]);

        return $ro->view;
    }

    private function templateName(ResourceObject $ro): string
    {
        $path = ltrim($ro->uri->path, '/');
        if ($path === '') {
            return 'index';
        }

        return $path;
    }
}
