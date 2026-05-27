<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Attribute\Validate;
use MyVendor\Cms\Interceptor\ValidationInterceptor;
use MyVendor\Cms\Matcher\ValidateParameterMatcher;
use MyVendor\Cms\Validation\ArticleValidator;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class ValidationModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->bind(ArticleValidator::class)->in(Scope::SINGLETON);
        $this->bindInterceptor(
            $this->matcher->subclassesOf(ResourceObject::class),
            new ValidateParameterMatcher(Validate::class),
            [ValidationInterceptor::class],
        );
    }
}
