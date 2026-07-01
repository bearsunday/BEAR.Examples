<?php

declare(strict_types=1);

namespace BEAR\Kata\Provide\Error;

use PHPUnit\Framework\TestCase;

final class HtmlErrorPageTest extends TestCase
{
    public function testRendersStatusAndMessage(): void
    {
        $page = new HtmlErrorPage(400, 'Bad Request', 'Validation failed.');

        $this->assertSame(400, $page->code);
        $view = (string) $page->view;
        $this->assertStringContainsString('400 Bad Request', $view);
        $this->assertStringContainsString('Validation failed.', $view);
        $this->assertStringNotContainsString('<ul>', $view);
    }

    public function testRendersPerFieldErrorList(): void
    {
        $page = new HtmlErrorPage(400, 'Bad Request', 'Validation failed.', [
            'email' => ['Invalid email address.'],
            'name' => ['Name is required.'],
        ]);

        $view = (string) $page->view;
        $this->assertStringContainsString('<ul>', $view);
        $this->assertStringContainsString('<li>Invalid email address.</li>', $view);
        $this->assertStringContainsString('<li>Name is required.</li>', $view);
    }

    public function testEscapesErrorMessages(): void
    {
        $page = new HtmlErrorPage(400, 'Bad Request', 'Validation failed.', [
            'bio' => ['<script>alert(1)</script>'],
        ]);

        $view = (string) $page->view;
        $this->assertStringNotContainsString('<script>alert(1)</script>', $view);
        $this->assertStringContainsString('&lt;script&gt;', $view);
    }
}
