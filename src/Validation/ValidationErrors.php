<?php

declare(strict_types=1);

namespace MyVendor\Cms\Validation;

final class ValidationErrors
{
    /** @var array<string, list<string>> */
    private array $errors = [];

    public function add(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function merge(self $errors): void
    {
        foreach ($errors->toArray() as $field => $fieldErrors) {
            foreach ($fieldErrors as $fieldError) {
                $this->add($field, $fieldError);
            }
        }
    }

    public function isEmpty(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string, list<string>> */
    public function toArray(): array
    {
        return $this->errors;
    }
}
