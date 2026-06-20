<?php

declare(strict_types=1);

namespace BEAR\Examples\Input;

use Ray\InputQuery\Attribute\Input;

/**
 * Input DTO for `POST app://self/auth`.
 *
 * Carries the OAuth `code` + `state` round-tripped from the authorization
 * server. The exchange is action-style POST (200 + body, no Location) per
 * conventions §4: it does not create a new addressable resource, it returns
 * the authenticated user.
 *
 * Validated by `#[JsonSchema(params: 'auth_exchange.json')]` on
 * `Auth::onPost`; the schema lives in `var/json_validate/auth_exchange.json`.
 *
 * @psalm-suppress PossiblyUnusedProperty resolved at the resource layer
 */
final readonly class AuthExchangeInput
{
    public function __construct(
        #[Input]
        public string $code,
        #[Input]
        public string $state,
    ) {
    }
}
