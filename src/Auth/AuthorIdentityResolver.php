<?php

declare(strict_types=1);

namespace MyVendor\Cms\Auth;

use MyVendor\Cms\Query\AuthIdentityCommandInterface;
use MyVendor\Cms\Query\AuthIdentityQueryInterface;
use MyVendor\Cms\Query\AuthorQueryInterface;
use Throwable;

final readonly class AuthorIdentityResolver
{
    public function __construct(
        private AuthIdentityQueryInterface $identity,
        private AuthIdentityCommandInterface $identityCmd,
        private AuthorQueryInterface $author,
    ) {
    }

    public function resolveAuthorId(AuthenticatedUser $user): int|null
    {
        $identity = $this->identity->byProviderSubject($user->provider, $user->subject);
        if ($identity !== null) {
            return $identity->authorId;
        }

        $author = $this->author->byEmail($user->email);
        if ($author === null) {
            return null;
        }

        try {
            $this->identityCmd->add($user->provider, $user->subject, $author->id, $user->email, $user->name);
        } catch (Throwable $e) {
            $identity = $this->identity->byProviderSubject($user->provider, $user->subject);
            if ($identity !== null) {
                return $identity->authorId;
            }

            throw $e;
        }

        return $author->id;
    }
}
