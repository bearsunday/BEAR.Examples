<?php

declare(strict_types=1);

namespace BEAR\Kata\Provide\Error;

use BEAR\Resource\ResourceObject;

use function htmlspecialchars;

use const ENT_QUOTES;

/**
 * Pre-rendered HTML error page.
 *
 * Assigned to $view so the responder emits it verbatim with text/html and
 * never round-trips through the Qiq renderer — a failure while building
 * the error page cannot therefore recurse back through rendering.
 */
final class HtmlErrorPage extends ResourceObject
{
    /** @param array<string, list<string>> $errors field path => messages */
    public function __construct(int $code, string $statusText, string $message, array $errors = [])
    {
        $this->code = $code;
        $this->headers = ['Content-Type' => 'text/html; charset=utf-8'];
        $escapedMessage = htmlspecialchars($message, ENT_QUOTES);
        $list = $this->renderErrors($errors);
        $this->view = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="utf-8"><title>{$code} {$statusText}</title></head>
        <body><h1>{$code} {$statusText}</h1><p>{$escapedMessage}</p>{$list}</body>
        </html>
        HTML;
    }

    /**
     * Render the field map as an escaped `<ul>`, or '' when there are none.
     *
     * @param array<string, list<string>> $errors
     */
    private function renderErrors(array $errors): string
    {
        if ($errors === []) {
            return '';
        }

        $items = '';
        foreach ($errors as $messages) {
            foreach ($messages as $message) {
                $items .= '<li>' . htmlspecialchars($message, ENT_QUOTES) . '</li>';
            }
        }

        return "<ul>{$items}</ul>";
    }
}
