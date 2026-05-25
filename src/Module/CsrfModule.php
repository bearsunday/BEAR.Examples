<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Attribute\CsrfToken;
use MyVendor\Cms\Attribute\SameOrigin;
use MyVendor\Cms\Auth\CsrfTokenInterface;
use MyVendor\Cms\Auth\SessionCsrfToken;
use MyVendor\Cms\Http\AllowedOrigin;
use MyVendor\Cms\Http\RequestBodyTokenInterface;
use MyVendor\Cms\Http\RequestOriginInterface;
use MyVendor\Cms\Http\ServerRequestBodyToken;
use MyVendor\Cms\Http\ServerRequestOrigin;
use MyVendor\Cms\Interceptor\CsrfTokenInterceptor;
use MyVendor\Cms\Interceptor\SameOriginInterceptor;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

/** @SuppressWarnings("PHPMD.CouplingBetweenObjects") composition root */
final class CsrfModule extends AbstractModule
{
    public function __construct(private readonly string|null $allowedOrigin = null)
    {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->bind(AllowedOrigin::class)->toInstance(new AllowedOrigin($this->allowedOrigin));

        $this->bind(RequestOriginInterface::class)->to(ServerRequestOrigin::class);
        $this->bindInterceptor(
            $this->matcher->subclassesOf(ResourceObject::class),
            $this->matcher->annotatedWith(SameOrigin::class),
            [SameOriginInterceptor::class],
        );

        $this->bind(CsrfTokenInterface::class)->to(SessionCsrfToken::class)->in(Scope::SINGLETON);
        $this->bind(RequestBodyTokenInterface::class)->to(ServerRequestBodyToken::class);
        $this->bindInterceptor(
            $this->matcher->subclassesOf(ResourceObject::class),
            $this->matcher->annotatedWith(CsrfToken::class),
            [CsrfTokenInterceptor::class],
        );
    }
}
