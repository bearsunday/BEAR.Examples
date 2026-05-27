<?php

declare(strict_types=1);

namespace MyVendor\Cms\Smoke;

use PHPUnit\Framework\TestCase;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function assert;
use function file_get_contents;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function sprintf;
use function str_starts_with;
use function substr;

use const JSON_THROW_ON_ERROR;

final class AlpsProfileTest extends TestCase
{
    private const string PROFILE = __DIR__ . '/../../var/alps/profile.json';

    public function testProfileDescribesWholeCmsSurface(): void
    {
        $profile = $this->profile();
        $doc = $profile['alps']['doc']['value'];
        assert(is_string($doc));
        $ids = $this->ids($profile);

        $this->assertStringContainsString('public HTML', $doc);
        $this->assertStringContainsString('Page/Admin', $doc);
        $this->assertStringContainsString('HAL App resource API', $doc);
        $expectedIds = [
            'Home',
            'AuthStart',
            'AuthenticatedUser',
            'AdminLogin',
            'OAuthProvider',
            'OAuthConfigurationError',
            'AdminIndex',
            'AdminArticleList',
            'AdminArticleForm',
            'AdminArticleDeleteConfirm',
            'goAdminLogin',
            'doCompleteLogin',
            'doLogout',
            'doAdminCreateArticle',
            'doAdminUpdateArticle',
            'doAdminDeleteArticle',
        ];
        foreach ($expectedIds as $id) {
            $this->assertContains($id, $ids, sprintf('Missing ALPS descriptor: %s', $id));
        }
    }

    public function testProfileReferencesAreResolvable(): void
    {
        $profile = $this->profile();
        $ids = $this->ids($profile);

        $this->assertSame($ids, array_unique($ids), 'ALPS descriptor ids must be unique.');
        foreach ($profile['alps']['descriptor'] as $descriptor) {
            assert(is_array($descriptor));
            $this->assertResolvableRt($descriptor, $ids);
            foreach ($descriptor['descriptor'] ?? [] as $child) {
                assert(is_array($child));
                $this->assertResolvableHref($child, $ids);
            }
        }
    }

    /** @return array<string, mixed> */
    private function profile(): array
    {
        /** @var array<string, mixed> $profile */
        $profile = json_decode((string) file_get_contents(self::PROFILE), true, 512, JSON_THROW_ON_ERROR);

        return $profile;
    }

    /**
     * @param array<string, mixed> $profile
     *
     * @return list<string>
     */
    private function ids(array $profile): array
    {
        assert(isset($profile['alps']) && is_array($profile['alps']));
        assert(isset($profile['alps']['descriptor']) && is_array($profile['alps']['descriptor']));

        /** @var list<string> $ids */
        $ids = array_values(array_filter(
            array_map(
                static fn (mixed $descriptor): string|null => is_array($descriptor) && isset($descriptor['id'])
                    ? (string) $descriptor['id']
                    : null,
                $profile['alps']['descriptor'],
            ),
        ));

        return $ids;
    }

    /**
     * @param array<string, mixed> $descriptor
     * @param list<string>         $ids
     */
    private function assertResolvableRt(array $descriptor, array $ids): void
    {
        if (! isset($descriptor['rt'])) {
            return;
        }

        $rt = (string) $descriptor['rt'];
        $this->assertTrue(str_starts_with($rt, '#'), sprintf('rt must be local: %s', $rt));
        $this->assertTrue(in_array(substr($rt, 1), $ids, true), sprintf('Broken rt reference: %s', $rt));
    }

    /**
     * @param array<string, mixed> $descriptor
     * @param list<string>         $ids
     */
    private function assertResolvableHref(array $descriptor, array $ids): void
    {
        if (! isset($descriptor['href'])) {
            return;
        }

        $href = (string) $descriptor['href'];
        $this->assertTrue(str_starts_with($href, '#'), sprintf('href must be local: %s', $href));
        $this->assertTrue(in_array(substr($href, 1), $ids, true), sprintf('Broken href reference: %s', $href));
    }
}
