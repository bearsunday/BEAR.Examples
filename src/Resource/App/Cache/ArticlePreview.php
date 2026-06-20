<?php

declare(strict_types=1);

namespace BEAR\Examples\Resource\App\Cache;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\RepositoryModule\Annotation\DonutCache;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\Examples\Query\ArticleQueryInterface;

/**
 * Explicit `#[DonutCache]` example for a stable article preview.
 *
 * The HAL API keeps this example scalar-only: donut-hole placeholders are
 * string-renderer oriented and would be obscured by HAL's object renderer.
 */
#[Alps('CacheArticlePreview')]
#[DonutCache]
class ArticlePreview extends ResourceObject
{
    public function __construct(
        private readonly ArticleQueryInterface $article,
    ) {
    }

    #[Alps('goCacheArticlePreview')]
    #[JsonSchema('cache_article_preview.json')]
    public function onGet(int $id): static
    {
        $article = $this->article->item($id);
        if ($article === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Article not found', 'id' => $id];

            return $this;
        }

        $this->body = [
            'id' => $article->id,
            'title' => $article->title,
            'status' => $article->status->value,
            'authorId' => $article->authorId,
            'cachePattern' => 'DonutCache explicit HAL preview',
        ];

        return $this;
    }
}
