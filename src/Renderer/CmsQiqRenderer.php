<?php

declare(strict_types=1);

namespace MyVendor\Cms\Renderer;

use BEAR\Resource\RenderInterface;
use BEAR\Resource\ResourceObject;
use Override;
use Qiq\Template;
use Ray\Aop\WeavedInterface;
use ReflectionClass;
use Throwable;

use function array_key_exists;
use function http_build_query;
use function in_array;
use function is_array;
use function str_replace;
use function strpos;
use function substr;

final readonly class CmsQiqRenderer implements RenderInterface
{
    private const int DEFAULT_CSS_LEVEL = 3;
    private const array CSS_LEVELS = [1, 2, 3];
    private const int RESOURCE_DIR_LEN = 13;

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
            $template = clone $this->template;
            $template->setData($vars);
            $template->setView($this->templateName($ro));
            $ro->view = ($template)();

            return $ro->view;
        } catch (Throwable $e) {
            $ro->code = 500;

            return $this->renderError($ro, ['message' => $e->getMessage()] + $vars);
        }
    }

    private function templateName(ResourceObject $ro): string
    {
        $reflection = $ro instanceof WeavedInterface
            ? (new ReflectionClass($ro))->getParentClass()
            : new ReflectionClass($ro);
        $fileName = (string) $reflection->getFileName();
        $pos = strpos($fileName, 'src/Resource/');
        $relativePath = substr($fileName, (int) $pos + self::RESOURCE_DIR_LEN);

        return str_replace('.php', '', $relativePath);
    }

    /** @param array<string, mixed> $vars */
    private function renderError(ResourceObject $ro, array $vars): string
    {
        $ro->view = $this->template->render('Error', [
            'code' => $ro->code,
            'message' => (string) ($vars['message'] ?? 'An error occurred'),
        ]);

        return $ro->view;
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
}
