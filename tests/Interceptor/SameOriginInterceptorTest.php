<?php

declare(strict_types=1);

namespace MyVendor\Cms\Interceptor;

use BEAR\Resource\Exception\BadRequestException;
use MyVendor\Cms\Exception\ForbiddenException;
use MyVendor\Cms\Fake\FakeRequestOrigin;
use MyVendor\Cms\Http\AllowedOrigin;
use PHPUnit\Framework\TestCase;
use Ray\Aop\ReflectiveMethodInvocation;

/**
 * Unit tests for `SameOriginInterceptor` — exercises the validation
 * algorithm directly. End-to-end wiring is covered separately by
 * `SameOriginWiringTest` (resource layer) so this file stays pure unit:
 * one invocation, hand-built fakes, no module / DI / route setup.
 */
final class SameOriginInterceptorTest extends TestCase
{
    public const string PROCEED_SENTINEL = 'proceeded';

    public function testAllowedOriginNullShortCircuits(): void
    {
        // No env configured → gate is off. Origin/Referer ignored entirely.
        $interceptor = new SameOriginInterceptor(
            new FakeRequestOrigin(fetchSite: 'cross-site', origin: 'https://attacker.example'),
            new AllowedOrigin(null),
        );

        $result = $interceptor->invoke($this->invocation());

        $this->assertSame(self::PROCEED_SENTINEL, $result);
    }

    public function testSecFetchSiteSameOriginProceeds(): void
    {
        $interceptor = $this->withRequest(new FakeRequestOrigin(fetchSite: 'same-origin'));

        $this->assertSame(self::PROCEED_SENTINEL, $interceptor->invoke($this->invocation()));
    }

    public function testSecFetchSiteCrossSiteThrowsForbidden(): void
    {
        $interceptor = $this->withRequest(new FakeRequestOrigin(fetchSite: 'cross-site'));

        $this->expectException(ForbiddenException::class);
        $interceptor->invoke($this->invocation());
    }

    public function testSecFetchSiteSameSiteThrowsForbidden(): void
    {
        // "same-site" is broader than "same-origin" — protocol or subdomain
        // may differ. The gate rejects it for the same defensive reason.
        $interceptor = $this->withRequest(new FakeRequestOrigin(fetchSite: 'same-site'));

        $this->expectException(ForbiddenException::class);
        $interceptor->invoke($this->invocation());
    }

    public function testSecFetchSiteNoneThrowsForbidden(): void
    {
        // Sec-Fetch-Site: none indicates a user-initiated navigation, not
        // a unsafe write from same-origin JS. POST gates reject it.
        $interceptor = $this->withRequest(new FakeRequestOrigin(fetchSite: 'none'));

        $this->expectException(ForbiddenException::class);
        $interceptor->invoke($this->invocation());
    }

    public function testSecFetchSiteUnknownValueThrowsForbidden(): void
    {
        // Future / injected literals are treated as cross-site rather than
        // falling through to Origin/Referer — the header is a forbidden
        // request header so a surprise value is more likely an attack.
        $interceptor = $this->withRequest(new FakeRequestOrigin(fetchSite: 'wat'));

        $this->expectException(ForbiddenException::class);
        $interceptor->invoke($this->invocation());
    }

    public function testOriginMatchProceeds(): void
    {
        $interceptor = $this->withRequest(new FakeRequestOrigin(
            origin: 'https://cms.example.com',
        ));

        $this->assertSame(self::PROCEED_SENTINEL, $interceptor->invoke($this->invocation()));
    }

    public function testOriginMatchWithDefaultPortProceeds(): void
    {
        // https:443 canonicalises to https://cms.example.com.
        $interceptor = $this->withRequest(new FakeRequestOrigin(
            origin: 'https://cms.example.com:443',
        ));

        $this->assertSame(self::PROCEED_SENTINEL, $interceptor->invoke($this->invocation()));
    }

    public function testOriginMatchCaseInsensitive(): void
    {
        // Scheme and host compare case-insensitively per RFC 3986.
        $interceptor = $this->withRequest(new FakeRequestOrigin(
            origin: 'HTTPS://CMS.EXAMPLE.COM',
        ));

        $this->assertSame(self::PROCEED_SENTINEL, $interceptor->invoke($this->invocation()));
    }

    public function testOriginMismatchThrowsForbidden(): void
    {
        $interceptor = $this->withRequest(new FakeRequestOrigin(
            origin: 'https://attacker.example',
        ));

        $this->expectException(ForbiddenException::class);
        $interceptor->invoke($this->invocation());
    }

    public function testOriginMalformedThrowsBadRequest(): void
    {
        // No scheme/host → not a valid origin.
        $interceptor = $this->withRequest(new FakeRequestOrigin(origin: 'not a url'));

        $this->expectException(BadRequestException::class);
        $interceptor->invoke($this->invocation());
    }

    public function testOriginNullLiteralThrowsBadRequest(): void
    {
        // "Origin: null" is a unique opaque origin — fail closed.
        $interceptor = $this->withRequest(new FakeRequestOrigin(origin: 'null'));

        $this->expectException(BadRequestException::class);
        $interceptor->invoke($this->invocation());
    }

    public function testOriginWithPathThrowsBadRequest(): void
    {
        // Origin headers must be bare; a path means the client sent a URL.
        $interceptor = $this->withRequest(new FakeRequestOrigin(
            origin: 'https://cms.example.com/admin',
        ));

        $this->expectException(BadRequestException::class);
        $interceptor->invoke($this->invocation());
    }

    public function testRefererMatchProceedsWhenOriginAbsent(): void
    {
        $interceptor = $this->withRequest(new FakeRequestOrigin(
            referer: 'https://cms.example.com/admin/article?id=1',
        ));

        $this->assertSame(self::PROCEED_SENTINEL, $interceptor->invoke($this->invocation()));
    }

    public function testRefererMismatchThrowsForbidden(): void
    {
        $interceptor = $this->withRequest(new FakeRequestOrigin(
            referer: 'https://attacker.example/article',
        ));

        $this->expectException(ForbiddenException::class);
        $interceptor->invoke($this->invocation());
    }

    public function testRefererMalformedThrowsBadRequest(): void
    {
        $interceptor = $this->withRequest(new FakeRequestOrigin(referer: '////'));

        $this->expectException(BadRequestException::class);
        $interceptor->invoke($this->invocation());
    }

    public function testAllSignalsMissingThrowsForbidden(): void
    {
        // Production fail-closed: with allowedOrigin set, no headers means
        // we can't confirm same-origin so the request is rejected.
        $interceptor = $this->withRequest(new FakeRequestOrigin());

        $this->expectException(ForbiddenException::class);
        $interceptor->invoke($this->invocation());
    }

    public function testMalformedAllowedOriginConfigurationFailsClosed(): void
    {
        $interceptor = new SameOriginInterceptor(
            new FakeRequestOrigin(fetchSite: 'same-origin'),
            new AllowedOrigin('not a url'),
        );

        $this->expectException(ForbiddenException::class);
        $interceptor->invoke($this->invocation());
    }

    private function withRequest(FakeRequestOrigin $request): SameOriginInterceptor
    {
        return new SameOriginInterceptor(
            $request,
            new AllowedOrigin('https://cms.example.com'),
        );
    }

    /**
     * Builds a minimal `MethodInvocation` whose `proceed()` returns a
     * sentinel string. The target argument is typed `object` so the
     * `ReflectiveMethodInvocation` template parameter binds to
     * `<object>` — `Ray\Aop`'s template `T` is invariant, so any
     * tighter type would fail to satisfy `MethodInvocation<object>` at
     * the interceptor call site.
     *
     * @return ReflectiveMethodInvocation<object>
     */
    private function invocation(): ReflectiveMethodInvocation
    {
        $target = new SameOriginInterceptorTestTarget();

        return $this->makeInvocation($target);
    }

    /** @return ReflectiveMethodInvocation<object> */
    private function makeInvocation(object $target): ReflectiveMethodInvocation
    {
        return new ReflectiveMethodInvocation($target, 'onPost', []);
    }
}
