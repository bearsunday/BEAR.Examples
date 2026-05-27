<?php

declare(strict_types=1);

namespace MyVendor\Cms\Error;

use BEAR\Resource\ResourceObject;

final class LoginRedirectPage extends ResourceObject
{
    public function __construct()
    {
        $this->code = 303;
        $this->headers = [
            'Location' => '/admin/login',
            'content-type' => 'text/html; charset=utf-8',
        ];
        $this->body = [];
        $this->view = '';
    }
}
