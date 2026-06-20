<?php

declare(strict_types=1);

namespace BEAR\Examples\Auth;

use BEAR\Examples\Query\AuthIdentityCommandInterface;
use BEAR\Examples\Query\AuthIdentityQueryInterface;
use BEAR\Examples\Query\AuthorQueryInterface;
use Ray\MediaQuery\Exception\PdoPerformException;

use function str_contains;
use function strtolower;

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
        } catch (PdoPerformException $e) {
            if (! $this->isUniqueIdentityViolation($e)) {
                throw $e;
            }

            $identity = $this->identity->byProviderSubject($user->provider, $user->subject);
            if ($identity !== null) {
                return $identity->authorId;
            }

            throw $e;
        }

        return $author->id;
    }

    private function isUniqueIdentityViolation(PdoPerformException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'uq_auth_identities_provider_subject')
            || str_contains($message, 'duplicate')
            || str_contains($message, 'unique constraint')
            || str_contains($message, 'unique violation');
    }
}
