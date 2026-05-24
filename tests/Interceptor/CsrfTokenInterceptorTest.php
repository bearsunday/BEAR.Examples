<?php

declare(strict_types=1);

namespace MyVendor\Cms\Interceptor;

use MyVendor\Cms\Exception\ForbiddenException;
use MyVendor\Cms\Fake\FakeCsrfToken;
use MyVendor\Cms\Fake\FakeRequestBodyToken;
use PHPUnit\Framework\TestCase;
use Ray\Aop\ReflectiveMethodInvocation;

/**
 * Unit tests for `CsrfTokenInterceptor` — covers the verification
 * algorithm directly. End-to-end wiring is `CsrfTokenWiringTest`.
 * Mirrors the unit / wiring split in `SameOriginInterceptorTest`.
 */
final class CsrfTokenInterceptorTest extends TestCase
{
    public const string PROCEED_SENTINEL = 'proceeded';

    public function testMatchingTokenProceeds(): void
    {
        $interceptor = new CsrfTokenInterceptor(
            new FakeCsrfToken('session-token'),
            new FakeRequestBodyToken('session-token'),
        );

        $this->assertSame(self::PROCEED_SENTINEL, $interceptor->invoke($this->invocation()));
    }

    public function testMissingSubmittedTokenThrowsForbidden(): void
    {
        $interceptor = new CsrfTokenInterceptor(
            new FakeCsrfToken('session-token'),
            new FakeRequestBodyToken(null),
        );

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('CSRF token missing.');
        $interceptor->invoke($this->invocation());
    }

    public function testMismatchedTokenThrowsForbidden(): void
    {
        $interceptor = new CsrfTokenInterceptor(
            new FakeCsrfToken('session-token'),
            new FakeRequestBodyToken('different-token'),
        );

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('CSRF token invalid.');
        $interceptor->invoke($this->invocation());
    }

    public function testEmptyTokensVerifyAsMismatchedNotMatch(): void
    {
        // Defence: a session that issued an empty token (impossible via
        // SessionCsrfToken, but exercised by FakeCsrfToken to pin the
        // contract) must not pretend an empty submitted value matches.
        $interceptor = new CsrfTokenInterceptor(
            new FakeCsrfToken(''),
            new FakeRequestBodyToken('any-value'),
        );

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('CSRF token invalid.');
        $interceptor->invoke($this->invocation());
    }

    /** @return ReflectiveMethodInvocation<object> */
    private function invocation(): ReflectiveMethodInvocation
    {
        $target = new CsrfTokenInterceptorTestTarget();

        return $this->makeInvocation($target);
    }

    /** @return ReflectiveMethodInvocation<object> */
    private function makeInvocation(object $target): ReflectiveMethodInvocation
    {
        return new ReflectiveMethodInvocation($target, 'onPost', []);
    }
}
