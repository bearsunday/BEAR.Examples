<?php

declare(strict_types=1);

namespace MyVendor\Cms\Interceptor;

use MyVendor\Cms\Exception\ForbiddenException;
use MyVendor\Cms\Fake\FakeCsrfToken;
use MyVendor\Cms\Fake\FakeRequestBodyToken;
use MyVendor\Cms\Http\AllowedOrigin;
use PHPUnit\Framework\TestCase;
use Ray\Aop\ReflectiveMethodInvocation;

/**
 * Unit tests for `CsrfTokenInterceptor` — covers the verification
 * algorithm directly. End-to-end wiring is `CsrfTokenWiringTest`.
 */
final class CsrfTokenInterceptorTest extends TestCase
{
    public const string PROCEED_SENTINEL = 'proceeded';

    private const string ALLOWED_ORIGIN = 'https://cms.example.com';

    public function testAllowedOriginNullShortCircuits(): void
    {
        // Mirrors SameOriginInterceptor's short-circuit — same on/off knob
        // means dev / CLI / test environments skip the gate even with a
        // missing or mismatched token in the body.
        $interceptor = new CsrfTokenInterceptor(
            new FakeCsrfToken('session-token'),
            new FakeRequestBodyToken(null),
            new AllowedOrigin(null),
        );

        $this->assertSame(self::PROCEED_SENTINEL, $interceptor->invoke($this->invocation()));
    }

    public function testMatchingTokenProceeds(): void
    {
        $interceptor = new CsrfTokenInterceptor(
            new FakeCsrfToken('session-token'),
            new FakeRequestBodyToken('session-token'),
            new AllowedOrigin(self::ALLOWED_ORIGIN),
        );

        $this->assertSame(self::PROCEED_SENTINEL, $interceptor->invoke($this->invocation()));
    }

    public function testMissingSubmittedTokenThrowsForbidden(): void
    {
        $interceptor = new CsrfTokenInterceptor(
            new FakeCsrfToken('session-token'),
            new FakeRequestBodyToken(null),
            new AllowedOrigin(self::ALLOWED_ORIGIN),
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
            new AllowedOrigin(self::ALLOWED_ORIGIN),
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
            new AllowedOrigin(self::ALLOWED_ORIGIN),
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
