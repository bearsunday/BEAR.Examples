<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\Page;

use BEAR\Resource\Annotation\Embed;
use BEAR\Resource\Request;
use BEAR\Resource\ResourceObject;

/** @property array{authorList: Request} $body */
class AuthorList extends ResourceObject
{
    /**
     * Reachability principle: the author collection lives in
     * `app://self/authors`, not here. A normal `#[Embed]` (rel: 'authorList')
     * places the App resource under the `authorList` key as a lazy Request.
     * The renderer draws it with the App's own representation — the HTML
     * template templates/App/Authors.php under html-hal-app, or
     * `_embedded.authorList` (JSON) under hal-api-app. The App owns the
     * representation; this Page only lays it out. The `authorList` key keeps the
     * embedded resource a named, DTO-like unit (`{authorList}`) rather than
     * flattening its fields into the page body. See docs/conventions.md —
     * Reachability.
     */
    #[Embed(rel: 'authorList', src: 'app://self/authors')]
    public function onGet(): static
    {
        return $this;
    }
}
