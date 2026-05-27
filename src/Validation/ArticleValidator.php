<?php

declare(strict_types=1);

namespace MyVendor\Cms\Validation;

use MyVendor\Cms\Input\ArticleCreateInput;
use MyVendor\Cms\Query\ArticleQueryInterface;

final readonly class ArticleValidator
{
    public function __construct(
        private ArticleQueryInterface $article,
    ) {
    }

    public function create(ArticleCreateInput $input): ValidationErrors
    {
        $errors = new ValidationErrors();
        if ($this->article->bySlug($input->slug) === null) {
            return $errors;
        }

        $errors->add('slug', 'This slug is already in use.');

        return $errors;
    }
}
