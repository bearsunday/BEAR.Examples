<?php

declare(strict_types=1);

namespace MyVendor\Cms\Service;

use MyVendor\Cms\Auth\AuthenticatedUser;
use MyVendor\Cms\Auth\AuthorIdentityResolver;
use MyVendor\Cms\Entity\AuthIdentity;
use MyVendor\Cms\Entity\Author;
use MyVendor\Cms\Query\AuthIdentityCommandInterface;
use MyVendor\Cms\Query\AuthIdentityQueryInterface;
use MyVendor\Cms\Query\AuthorQueryInterface;
use PHPUnit\Framework\TestCase;
use Ray\MediaQuery\Exception\PdoPerformException;

final class AuthorIdentityResolverTest extends TestCase
{
    public function testDuplicateIdentityCreateFallsBackToCreatedMapping(): void
    {
        $identity = new class implements AuthIdentityQueryInterface {
            private int $calls = 0;

            public function byProviderSubject(string $provider, string $subject): AuthIdentity|null
            {
                $this->calls++;

                return $this->calls === 1 ? null : new AuthIdentity(1, $provider, $subject, 7, 'editor@example.com', 'Editor');
            }
        };
        $command = new class implements AuthIdentityCommandInterface {
            public function add(
                string $provider,
                string $subject,
                int $authorId,
                string $email,
                string $name,
            ): void {
                throw new PdoPerformException('SQLSTATE[23000]: Integrity constraint violation: duplicate entry');
            }
        };
        $author = new class implements AuthorQueryInterface {
            public function item(int $id): Author|null
            {
                return null;
            }

            public function byEmail(string $email): Author
            {
                return new Author(7, 'Editor', $email, 'Bio');
            }

            /** @return list<Author> */
            public function list(): array
            {
                return [];
            }
        };

        $resolver = new AuthorIdentityResolver($identity, $command, $author);

        $this->assertSame(7, $resolver->resolveAuthorId($this->user()));
    }

    public function testNonUniqueIdentityWriteFailureBubbles(): void
    {
        $identity = new class implements AuthIdentityQueryInterface {
            public function byProviderSubject(string $provider, string $subject): AuthIdentity|null
            {
                return null;
            }
        };
        $command = new class implements AuthIdentityCommandInterface {
            public function add(
                string $provider,
                string $subject,
                int $authorId,
                string $email,
                string $name,
            ): void {
                throw new PdoPerformException('SQLSTATE[HY000]: database unavailable');
            }
        };
        $author = new class implements AuthorQueryInterface {
            public function item(int $id): Author|null
            {
                return null;
            }

            public function byEmail(string $email): Author
            {
                return new Author(7, 'Editor', $email, 'Bio');
            }

            /** @return list<Author> */
            public function list(): array
            {
                return [];
            }
        };

        $resolver = new AuthorIdentityResolver($identity, $command, $author);

        $this->expectException(PdoPerformException::class);
        $resolver->resolveAuthorId($this->user());
    }

    private function user(): AuthenticatedUser
    {
        return new AuthenticatedUser(
            id: 'google-subject',
            email: 'editor@example.com',
            name: 'Editor',
            provider: 'google',
            subject: 'google-subject',
        );
    }
}
