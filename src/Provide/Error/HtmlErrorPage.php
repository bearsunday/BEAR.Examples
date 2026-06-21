<?php

declare(strict_types=1);

namespace BEAR\Kata\Provide\Error;

use BEAR\Resource\ResourceObject;

/**
 * Pre-rendered HTML error page.
 *
 * Assigned to $view so the responder emits it verbatim with text/html and
 * never round-trips through the Qiq renderer — a failure while building
 * the error page cannot therefore recurse back through rendering.
 */
final class HtmlErrorPage extends ResourceObject
{
    public function __construct(int $code, string $statusText, string $message)
    {
        $this->code = $code;
        $this->headers = ['Content-Type' => 'text/html; charset=utf-8'];
        $this->view = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="utf-8"><title>{$code} {$statusText}</title></head>
        <body><h1>{$code} {$statusText}</h1><p>{$message}</p></body>
        </html>
        HTML;
    }
}
