<?php

declare(strict_types=1);

namespace MyVendor\Cms\Matcher;

use Override;
use Ray\Aop\AbstractMatcher;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

use function assert;
use function is_string;

final class ValidateParameterMatcher extends AbstractMatcher
{
    #[Override]
    public function matchesClass(ReflectionClass $class, array $arguments): bool
    {
        unset($class, $arguments);

        return true;
    }

    #[Override]
    public function matchesMethod(ReflectionMethod $method, array $arguments): bool
    {
        [$attribute] = $arguments;
        assert(is_string($attribute));

        foreach ($method->getParameters() as $parameter) {
            if ($parameter->getAttributes($attribute, ReflectionAttribute::IS_INSTANCEOF) !== []) {
                return true;
            }
        }

        return false;
    }
}
