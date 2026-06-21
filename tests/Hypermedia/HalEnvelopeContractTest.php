<?php

declare(strict_types=1);

namespace BEAR\Kata\Hypermedia;

use function array_diff;
use function array_keys;
use function array_values;
use function json_decode;

/**
 * Contract pin (not a story): Taxonomy nouns belong under `_embedded`,
 * Choreography verbs under `_links`. Lives apart from the workflow
 * stories so a rename on either side surfaces as a single isolated
 * failure (see docs/conventions.md §3).
 */
final class HalEnvelopeContractTest extends AbstractWorkflowTestCase
{
    public function testArticleSplitsTaxonomyAndChoreography(): void
    {
        $articles = $this->resource->get('app://self/articles', ['perPage' => 1]);
        $article = $this->follow($articles, 'goArticle', ['id' => $articles->body['items'][0]['id']]);
        $rendered = json_decode((string) $article, true);

        $this->assertIsArray($rendered);
        $this->assertSame(['author', 'category', 'tagList'], array_keys($rendered['_embedded']));
        // `self` is added by the HAL renderer; the rest are the resource's own choreography.
        $rels = array_values(array_diff(array_keys($rendered['_links']), ['self']));
        $this->assertSame(['goArticleList', 'goAuthor', 'goCategory'], $rels);
    }
}
