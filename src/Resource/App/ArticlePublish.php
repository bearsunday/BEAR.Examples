<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Entity\ArticleStatus;
use MyVendor\Cms\Query\ArticleCommandInterface;
use MyVendor\Cms\Query\ArticleQueryInterface;

use function gmdate;

/**
 * State-transition resource: `draft → published` for a single article.
 *
 * Modelled as a separate resource (rather than a method on `Article`) for two
 * reasons. First, the meeting decision in Issue #37 sets confirmation /
 * publication apart as its own resource so the confirm-then-commit flow has a
 * URI to point at. Second, the `Article` resource is verb-rich (POST/PUT/DELETE
 * for full CRUD plus field edits); folding a state transition into `onPut`
 * would lose the distinction between "edit fields" and "move state".
 *
 * Idempotent within a state: publishing an already-published article returns
 * 409 with `{message, status}` and leaves the row untouched. This makes the
 * route safe to retry under network failures yet honest about the state
 * machine — repeating the transition is not the same as moving the state
 * twice.
 *
 * `publishedAt` defaults to the current UTC time when omitted. Callers can
 * supply an explicit ISO-8601 timestamp for backdated publication.
 */
#[Alps('doPublishArticle')]
class ArticlePublish extends ResourceObject
{
    public function __construct(
        private readonly ArticleQueryInterface $article,
        private readonly ArticleCommandInterface $articleCmd,
    ) {
    }

    #[Alps('doPublishArticle')]
    #[JsonSchema(schema: 'write_response.json', params: 'article_publish.json')]
    public function onPost(int $id, string|null $publishedAt = null): static
    {
        $article = $this->article->item($id);
        if ($article === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Article not found', 'id' => $id];

            return $this;
        }

        if ($article->isPublished()) {
            // 409 Conflict: state-machine guard. The transition itself is the
            // resource's contract; "already in target state" is not a success.
            $this->code = 409;
            $this->body = [
                'message' => 'Article is already published',
                'id' => $id,
                'status' => $article->status->value,
            ];

            return $this;
        }

        $effectiveAt = $publishedAt ?? gmdate('Y-m-d\\TH:i:s\\Z');
        $affectedRows = $this->articleCmd->publish($id, ArticleStatus::Published->value, $effectiveAt);
        if (! $affectedRows->isAffected()) {
            return $this->publishConflict($id);
        }

        $this->code = Code::OK;
        $this->body = [
            'id' => $id,
            'slug' => $article->slug,
            'status' => ArticleStatus::Published->value,
            'publishedAt' => $effectiveAt,
        ];

        return $this;
    }

    private function publishConflict(int $id): static
    {
        $article = $this->article->item($id);
        if ($article === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Article not found', 'id' => $id];

            return $this;
        }

        $this->code = 409;
        $this->body = [
            'message' => 'Article is already published',
            'id' => $id,
            'status' => $article->status->value,
        ];

        return $this;
    }
}
