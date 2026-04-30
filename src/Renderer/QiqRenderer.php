<?php

declare(strict_types=1);

namespace MyVendor\Cms\Renderer;

use BEAR\Resource\RenderInterface;
use BEAR\Resource\ResourceObject;
use Override;
use Qiq\Template;
use Throwable;

use function array_key_exists;
use function http_build_query;
use function in_array;
use function is_array;
use function ltrim;

final readonly class QiqRenderer implements RenderInterface
{
    private const int DEFAULT_CSS_LEVEL = 3;
    private const array CSS_LEVELS = [1, 2, 3];

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
        $vars += $this->cssVars($ro);
        if ($ro->code >= 400) {
            return $this->renderError($ro, $vars);
        }

        try {
            $ro->view = $this->template->render($this->templateName($ro), $vars);

            return $ro->view;
        } catch (Throwable $e) {
            $ro->code = 500;

            return $this->renderError($ro, ['message' => $e->getMessage()] + $vars);
        }
    }

    /** @return array{cssLevel: int, cssLinks: array<int, string>} */
    private function cssVars(ResourceObject $ro): array
    {
        $query = $ro->uri->query;
        $requested = isset($query['css']) ? (int) $query['css'] : self::DEFAULT_CSS_LEVEL;
        $level = in_array($requested, self::CSS_LEVELS, true) ? $requested : self::DEFAULT_CSS_LEVEL;

        $base = $query;
        unset($base['css']);
        $path = $ro->uri->path;
        if ($path === '' || $path === '/index') {
            $path = '/';
        }

        $links = [];
        foreach (self::CSS_LEVELS as $n) {
            $merged = $base + ['css' => $n];
            $links[$n] = $path . '?' . http_build_query($merged);
        }

        return ['cssLevel' => $level, 'cssLinks' => $links];
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
