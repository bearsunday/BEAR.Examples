<?php

declare(strict_types=1);

namespace MyVendor\Cms\Interceptor;

use MyVendor\Cms\Attribute\Validate;
use MyVendor\Cms\Exception\InvalidValidatorException;
use MyVendor\Cms\Exception\ValidationFailedException;
use MyVendor\Cms\Validation\ValidationErrors;
use Override;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\Di\InjectorInterface;
use ReflectionAttribute;

use function call_user_func;
use function get_debug_type;
use function is_callable;
use function sprintf;

final readonly class ValidationInterceptor implements MethodInterceptor
{
    public function __construct(
        private InjectorInterface $injector,
    ) {
    }

    #[Override]
    public function invoke(MethodInvocation $invocation)
    {
        $errors = new ValidationErrors();
        $arguments = $invocation->getArguments();
        foreach ($invocation->getMethod()->getParameters() as $parameter) {
            $attributes = $parameter->getAttributes(Validate::class, ReflectionAttribute::IS_INSTANCEOF);
            if ($attributes === []) {
                continue;
            }

            $value = $arguments[$parameter->getPosition()];
            foreach ($attributes as $attribute) {
                $errors->merge($this->validate($attribute, $value));
            }
        }

        if (! $errors->isEmpty()) {
            throw new ValidationFailedException($errors);
        }

        return $invocation->proceed();
    }

    /** @param ReflectionAttribute<Validate> $attribute */
    private function validate(ReflectionAttribute $attribute, mixed $value): ValidationErrors
    {
        $validate = $attribute->newInstance();
        $validator = $this->injector->getInstance($validate->service);
        $callable = [$validator, $validate->method];
        if (! is_callable($callable)) {
            throw new InvalidValidatorException(sprintf(
                'Validator method %s::%s() is not callable',
                $validate->service,
                $validate->method,
            ));
        }

        $errors = call_user_func($callable, $value);
        if (! $errors instanceof ValidationErrors) {
            throw new InvalidValidatorException(sprintf(
                'Validator method %s::%s() must return %s; got %s',
                $validate->service,
                $validate->method,
                ValidationErrors::class,
                get_debug_type($errors),
            ));
        }

        return $errors;
    }
}
